<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\File;
use Illuminate\Validation\ValidationException;

class JobApplicationController extends Controller
{
    public function show(Request $request, int $jobId): JsonResponse
    {
        $job = Job::published()->with([
            'jobCategory:id,name', 'township.region:id,name',
            'employer' => fn ($query) => $query->with(['region:id,name', 'township:id,name', 'reviews' => fn ($reviews) => $reviews->where('status', 'approved')->latest()->limit(5)])
                ->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->where('status', 'approved')])
                ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($reviews) => $reviews->where('status', 'approved')], 'rating'),
            'promotions' => fn ($query) => $query->whereIn('status', ['active', 'scheduled'])->where('starts_at', '<=', now())->where('ends_at', '>', now()),
        ])
            ->findOrFail($jobId);
        $seeker = $request->user()?->role === 'job_seeker' ? $request->user()->jobSeeker : null;
        $application = $seeker?->applications()->where('job_id', $job->id)->first();
        $saved = $seeker?->savedJobs()->where('job_id', $job->id)->exists() ?? false;

        return response()->json([
            'job' => [
                'id' => $job->id,
                'title' => $job->title,
                'description' => $job->description,
                'requirements' => $job->requirements,
                'category' => $job->jobCategory?->name ?? $job->category,
                'category_id' => $job->category_id,
                'location' => $job->township ? $job->township->name.', '.$job->township->region?->name : $job->location,
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'work_mode' => $job->work_mode,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_currency' => $job->salary_currency,
                'posted_at' => $job->posted_at?->toIso8601String(),
                'application_deadline' => $job->application_deadline?->toIso8601String(),
                'is_featured' => $job->promotions->contains('promotion_type', 'featured'),
                'promotion_labels' => $job->promotions->pluck('promotion_type')->unique()->values(),
                'company' => [
                    'employer_id' => $job->employer?->id,
                    'name' => $job->employer?->company_name,
                    'description' => $job->employer?->company_description,
                    'website_url' => $job->employer?->website_url,
                    'social_links' => $job->employer?->social_links ?? [],
                    'location' => collect([$job->employer?->location, $job->employer?->township?->name, $job->employer?->region?->name])->filter()->join(', '),
                    'is_verified' => (bool) $job->employer?->is_verified,
                    'average_rating' => $job->employer?->approved_reviews_avg_rating !== null ? round((float) $job->employer->approved_reviews_avg_rating, 1) : null,
                    'review_count' => $job->employer?->approved_reviews_count ?? 0,
                    'reviews' => $job->employer?->reviews?->map(fn ($review) => ['rating' => $review->rating, 'title' => $review->title, 'review' => $review->review, 'created_at' => $review->created_at?->toIso8601String()]) ?? [],
                ],
            ],
            'authenticated' => $request->user() !== null,
            'is_own_listing' => $request->user()?->role === 'employer'
                && $job->employer?->user_id === $request->user()->id,
            'can_apply' => $seeker?->status === true,
            'has_applied' => $application !== null,
            'application_status' => $application?->status,
            'saved' => $saved,
            'profile_active' => $seeker?->status === true,
            'has_resume' => filled($seeker?->cv_path),
            'resume_name' => $seeker?->cv_original_name,
        ]);
    }

    public function apply(Request $request, int $jobId): JsonResponse
    {
        abort_unless($request->user()->role === 'job_seeker', 403, 'Only job seekers can apply for jobs.');
        $seeker = $request->user()->jobSeeker;
        abort_unless($seeker && $seeker->status, 403, 'Complete or reactivate your job seeker profile before applying.');

        $data = $request->validate([
            'cover_letter' => ['nullable', 'string', 'max:5000'],
            'cv' => ['nullable', File::types(['pdf', 'doc', 'docx'])->max(10240)],
        ]);

        $storedPath = null;
        try {
            $application = DB::transaction(function () use ($request, $seeker, $jobId, $data, &$storedPath) {
                $job = Job::published()->whereKey($jobId)->lockForUpdate()->firstOrFail();
                if ($seeker->applications()->where('job_id', $job->id)->exists()) {
                    throw ValidationException::withMessages(['application' => 'You have already applied for this job.']);
                }

                if ($request->hasFile('cv')) $storedPath = $request->file('cv')->store('job-seeker-cvs', 'local');
                $application = $seeker->applyForJob($job, [
                    'cover_letter' => $data['cover_letter'] ?? null,
                    'cv_path' => $storedPath ?: $seeker->cv_path,
                ]);

                if ($job->employer?->user) {
                    Notification::sendNotification($job->employer->user, 'New job application', "{$request->user()->name} applied for {$job->title}.", null, 'application', '/#pipeline');
                }
                return $application;
            });
        } catch (\Throwable $exception) {
            if ($storedPath) Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        return response()->json([
            'message' => 'Your application was submitted successfully.',
            'application' => ['id' => $application->id, 'status' => $application->status, 'submitted_at' => $application->submitted_at?->toIso8601String()],
        ], 201);
    }

    public function save(Request $request, int $jobId): JsonResponse
    {
        abort_unless($request->user()->role === 'job_seeker', 403, 'Only job seekers can save jobs.');
        $seeker = $request->user()->jobSeeker;
        abort_unless($seeker, 403);
        $job = Job::published()->findOrFail($jobId);
        $saved = $seeker->saveJob($job);
        return response()->json(['saved' => true, 'saved_at' => $saved->saved_at?->toIso8601String()]);
    }

    public function unsave(Request $request, int $jobId): JsonResponse
    {
        abort_unless($request->user()->role === 'job_seeker', 403, 'Only job seekers can save jobs.');
        $seeker = $request->user()->jobSeeker;
        abort_unless($seeker, 403);
        $seeker->savedJobs()->where('job_id', $jobId)->delete();
        return response()->json(['saved' => false]);
    }
}
