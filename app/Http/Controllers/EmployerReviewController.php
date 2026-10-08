<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Employer;
use App\Models\EmployerReview;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EmployerReviewController extends Controller
{
    private const REVIEWABLE_APPLICATION_STATUSES = ['shortlisted', 'interview', 'offered', 'hired', 'rejected'];

    public function store(Request $request, Application $application): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->role === 'job_seeker' && $user->jobSeeker?->status, 403, 'An active job seeker account is required to review an employer.');
        abort_unless($application->job_seeker_id === $user->jobSeeker->id, 404);

        $application->loadMissing('job.employer');
        $employer = $application->job?->employer;
        abort_unless($employer, 404);
        abort_if($employer->user_id === $user->id, 403, 'An employer cannot review its own account.');
        abort_unless(in_array($application->status, self::REVIEWABLE_APPLICATION_STATUSES, true), 422, 'A review is available after an application reaches shortlist, interview, offer, hire, or rejection.');

        $data = $request->validate(['rating' => ['required', 'integer', 'between:1,5'], 'title' => ['nullable', 'string', 'max:160'], 'review' => ['required', 'string', 'min:20', 'max:3000']]);
        try {
            $review = DB::transaction(function () use ($employer, $user, $application, $data) {
                Employer::whereKey($employer->id)->lockForUpdate()->firstOrFail();
                $currentApplication = Application::query()->whereKey($application->id)->lockForUpdate()->firstOrFail();
                abort_unless($currentApplication->job_seeker_id === $user->jobSeeker->id && in_array($currentApplication->status, self::REVIEWABLE_APPLICATION_STATUSES, true), 422, 'This application is no longer eligible for an employer review.');
                if (EmployerReview::where('employer_id', $employer->id)->where('job_seeker_id', $user->jobSeeker->id)->exists()) {
                    throw ValidationException::withMessages(['review' => 'A review has already been submitted for this employer.']);
                }
                return EmployerReview::create([
                    ...$data,
                    'employer_id' => $employer->id,
                    'job_seeker_id' => $user->jobSeeker->id,
                    'application_id' => $application->id,
                    'status' => 'pending',
                ]);
            });
        } catch (QueryException $exception) {
            if (in_array($exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages(['review' => 'A review has already been submitted for this employer.']);
            }
            throw $exception;
        }

        return response()->json(['message' => 'Review submitted. It will appear publicly after moderation.', 'review_id' => $review->id, 'status' => $review->status], 201);
    }

    public function publicForEmployer(Employer $employer): JsonResponse
    {
        $reviews = $employer->reviews()->where('status', 'approved')->latest()->limit(10)->get(['id', 'rating', 'title', 'review', 'created_at']);
        return response()->json([
            'average_rating' => round((float) $employer->reviews()->where('status', 'approved')->avg('rating'), 1),
            'review_count' => $employer->reviews()->where('status', 'approved')->count(),
            'reviews' => $reviews,
        ]);
    }
}
