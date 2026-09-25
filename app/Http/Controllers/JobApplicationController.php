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
        $job = Job::published()->with(['employer.region:id,name', 'employer.township:id,name', 'township.region:id,name'])
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
                'category' => $job->category,
                'location' => $job->township ? $job->township->name.', '.$job->township->region?->name : $job->location,
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'work_mode' => $job->work_mode,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_currency' => $job->salary_currency,
                'posted_at' => $job->posted_at?->toIso8601String(),
                'application_deadline' => $job->application_deadline?->toIso8601String(),
                'company' => [
                    'name' => $job->employer?->company_name,
                    'description' => $job->employer?->company_description,
                    'location' => collect([$job->employer?->location, $job->employer?->township?->name, $job->employer?->region?->name])->filter()->join(', '),
                    'is_verified' => (bool) $job->employer?->is_verified,
                ],
            ],
            'authenticated' => $request->user() !== null,
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
