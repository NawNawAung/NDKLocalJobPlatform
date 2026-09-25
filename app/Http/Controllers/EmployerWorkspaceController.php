<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\Interview;
use App\Models\Job;
use App\Models\Notification;
use App\Models\JobSeeker;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class EmployerWorkspaceController extends Controller
{
    private function employer(Request $request)
    {
        abort_unless($request->user()->role === 'employer' && $request->user()->employer, 403);
        return $request->user()->employer;
    }

    public function dashboard(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $employer->loadMissing(['region:id,name', 'township:id,name']);
        $jobs = $employer->jobs()->withCount('applications')->with(['township:id,name'])->latest()->get();
        $applications = Application::query()->whereHas('job', fn ($query) => $query->where('employer_id', $employer->id));
        $interviews = Interview::query()->where('employer_id', $employer->id)->where('status', 'scheduled')
            ->where('interview_at', '>=', now())->with(['application.job:id,title', 'application.jobSeeker.user:id,name'])
            ->orderBy('interview_at')->limit(6)->get();
        $recentApplicants = Application::query()->whereHas('job', fn ($query) => $query->where('employer_id', $employer->id))
            ->with(['job:id,title', 'jobSeeker.user:id,name,email', 'jobSeeker:id,user_id,professional_title,years_experience,skills,cv_path'])
            ->latest('submitted_at')->limit(6)->get();

        return response()->json([
            'profile' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'company_name' => $employer->company_name,
                'company_description' => $employer->company_description,
                'location' => $employer->location,
                'region' => $employer->region?->name,
                'township' => $employer->township?->name,
                'is_verified' => $employer->is_verified,
            ],
            'stats' => [
                'active_jobs' => $jobs->where('status', 'published')->count(),
                'total_applications' => (clone $applications)->whereNotIn('status', ['withdrawn'])->count(),
                'in_review' => (clone $applications)->whereIn('status', ['submitted', 'reviewing', 'shortlisted'])->count(),
                'interviews_upcoming' => Interview::where('employer_id', $employer->id)->where('status', 'scheduled')->where('interview_at', '>=', now())->count(),
            ],
            'jobs' => $jobs->map(fn (Job $job) => [
                'id' => $job->id, 'title' => $job->title, 'location' => $job->location,
                'township' => $job->township?->name, 'status' => $job->status,
                'posted_at' => $job->posted_at?->toIso8601String(), 'applications_count' => $job->applications_count,
                'employment_type' => $job->employment_type, 'category' => $job->category,
                'experience_level' => $job->experience_level, 'work_mode' => $job->work_mode,
                'salary_min' => $job->salary_min, 'salary_max' => $job->salary_max,
                'application_deadline' => $job->application_deadline?->format('Y-m-d'),
                'description' => $job->description, 'requirements' => $job->requirements,
            ]),
            'recent_applicants' => $recentApplicants->map(fn (Application $application) => [
                'id' => $application->id, 'status' => $application->status,
                'submitted_at' => $application->submitted_at?->toIso8601String(), 'job_id' => $application->job_id,
                'job_title' => $application->job?->title, 'name' => $application->jobSeeker?->user?->name,
                'professional_title' => $application->jobSeeker?->professional_title,
                'years_experience' => $application->jobSeeker?->years_experience,
            ]),
            'upcoming_interviews' => $interviews->map(fn (Interview $interview) => [
                'id' => $interview->id, 'interview_at' => $interview->interview_at?->toIso8601String(),
                'type' => $interview->interview_type, 'job_title' => $interview->application?->job?->title,
                'candidate_name' => $interview->application?->jobSeeker?->user?->name,
            ]),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($request->user()->id)],
            'company_name' => ['required', 'string', 'max:255'],
            'company_description' => ['nullable', 'string', 'max:5000'],
            'location' => ['nullable', 'string', 'max:255'],
        ]);
        $request->user()->update(['name' => $data['name'], 'email' => $data['email']]);
        $employer->update(collect($data)->only(['company_name', 'company_description', 'location'])->all());
        return response()->json(['message' => 'Company profile updated.']);
    }

    public function candidates(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'job_id' => ['nullable', 'integer'],
            'status' => ['nullable', Rule::in(Application::STATUSES)],
        ]);
        $applications = Application::query()->whereHas('job', fn ($query) => $query->where('employer_id', $employer->id))
            ->with(['job:id,title', 'jobSeeker.user:id,name,email', 'jobSeeker:id,user_id,professional_title,years_experience,skills,languages,cv_path,region_id,township_id', 'jobSeeker.region:id,name', 'jobSeeker.township:id,name'])
            ->when($data['job_id'] ?? null, fn ($query, $id) => $query->where('job_id', $id))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['search'] ?? null, function ($query, $search) {
                $query->where(function ($matches) use ($search) {
                    $matches->whereHas('jobSeeker.user', fn ($user) => $user->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('jobSeeker', fn ($seeker) => $seeker->where('professional_title', 'like', "%{$search}%"));
                });
            })->latest('submitted_at')->paginate(30);

        return response()->json([
            'jobs' => $employer->jobs()->orderBy('title')->get(['id', 'title']),
            'applications' => $applications->through(fn (Application $application) => [
                'id' => $application->id, 'job_id' => $application->job_id, 'job_title' => $application->job?->title,
                'status' => $application->status, 'submitted_at' => $application->submitted_at?->toIso8601String(),
                'cover_letter' => $application->cover_letter,
                'candidate' => [
                    'name' => $application->jobSeeker?->user?->name,
                    'email' => $application->jobSeeker?->user?->email,
                    'title' => $application->jobSeeker?->professional_title,
                    'experience' => $application->jobSeeker?->years_experience,
                    'skills' => $application->jobSeeker?->skills ?? [],
                    'location' => collect([$application->jobSeeker?->township?->name, $application->jobSeeker?->region?->name])->filter()->join(', '),
                    'has_cv' => filled($application->jobSeeker?->cv_path) || filled($application->cv_path),
                ],
            ]),
        ]);
    }

    public function createJob(Request $request): JsonResponse
    {
        $employer = $this->employer($request);
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'location' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:100'],
            'employment_type' => ['required', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'work_mode' => ['nullable', Rule::in(['on_site', 'hybrid', 'remote'])],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'application_deadline' => ['nullable', 'date', 'after:today'],
        ]);
        if (isset($data['salary_min'], $data['salary_max']) && $data['salary_min'] > $data['salary_max']) {
            return response()->json(['message' => 'Maximum salary must be at least the minimum salary.'], 422);
        }
        $job = $employer->postJob([...$data, 'status' => 'published', 'posted_at' => now(), 'salary_currency' => 'MMK']);
        $this->notifyJobAlertSubscribers($job);
        return response()->json(['message' => 'Job published.', 'job' => $job], 201);
    }

    public function updateJob(Request $request, Job $job): JsonResponse
    {
        $employer = $this->employer($request);
        abort_unless($job->employer_id === $employer->id, 404);
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:20000'],
            'requirements' => ['nullable', 'string', 'max:20000'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'category' => ['sometimes', 'required', 'string', 'max:100'],
            'employment_type' => ['sometimes', Rule::in(['full_time', 'part_time', 'contract', 'temporary', 'internship'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'work_mode' => ['nullable', Rule::in(['on_site', 'hybrid', 'remote'])],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'application_deadline' => ['nullable', 'date', 'after:today'],
            'status' => ['sometimes', Rule::in(['published', 'paused', 'closed', 'draft'])],
        ]);
        if (isset($data['salary_min'], $data['salary_max']) && $data['salary_min'] > $data['salary_max']) {
            return response()->json(['message' => 'Maximum salary must be at least the minimum salary.'], 422);
        }
        $job->update($data);
        return response()->json(['message' => 'Job updated.', 'job' => $job->fresh()]);
    }

    public function deleteJob(Request $request, Job $job): JsonResponse
    {
        abort_unless($job->employer_id === $this->employer($request)->id, 404);
        $job->delete();
        return response()->json(['message' => 'Job removed.']);
    }

    public function updateApplication(Request $request, Application $application): JsonResponse
    {
        $employer = $this->employer($request);
        abort_unless($application->job()->where('employer_id', $employer->id)->exists(), 404);
        $data = $request->validate(['status' => ['required', Rule::in(['reviewing', 'shortlisted', 'interview', 'offered', 'hired', 'rejected'])]]);
        $application->updateStatus($data['status']);
        Notification::sendNotification($application->jobSeeker->user, 'Application update', "Your application for {$application->job->title} is now {$data['status']}.", null, 'application', '/profile');
        return response()->json(['message' => 'Application status updated.', 'status' => $application->status]);
    }

    public function downloadResume(Request $request, Application $application)
    {
        $employer = $this->employer($request);
        abort_unless($application->job()->where('employer_id', $employer->id)->exists(), 404);
        $path = $application->cv_path ?: $application->jobSeeker?->cv_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);
        return Storage::disk('local')->download($path, basename($path));
    }

    public function scheduleInterview(Request $request, Application $application): JsonResponse
    {
        $employer = $this->employer($request);
        abort_unless($application->job()->where('employer_id', $employer->id)->exists(), 404);
        $data = $request->validate([
            'interview_at' => ['required', 'date', 'after:now'],
            'interview_type' => ['required', Rule::in(['online', 'phone', 'in_person'])],
            'meeting_url' => ['nullable', 'url', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $data['interview_at'] = Carbon::parse($data['interview_at'])->utc();
        $interview = $employer->scheduleInterview($application, $data);
        $application->update(['status' => 'interview']);
        Notification::sendNotification($application->jobSeeker->user, 'Interview scheduled', "An interview for {$application->job->title} has been scheduled for {$interview->interview_at->setTimezone('Asia/Yangon')->format('M j, Y g:i A')} Myanmar time.", null, 'interview', '/profile');
        return response()->json(['message' => 'Interview scheduled.', 'interview' => $interview], 201);
    }

    private function notifyJobAlertSubscribers(Job $job): void
    {
        $companyName = $job->employer?->company_name ?: 'An employer';
        $actionUrl = '/jobs?category='.rawurlencode($job->category);

        JobSeeker::query()
            ->where('status', true)
            ->where('job_alerts_enabled', true)
            ->with('user:id,name')
            ->chunkById(200, function ($jobSeekers) use ($job, $companyName, $actionUrl) {
                foreach ($jobSeekers as $jobSeeker) {
                    if (! $jobSeeker->user) continue;
                    Notification::sendNotification(
                        $jobSeeker->user,
                        'New job listing',
                        "{$companyName} published {$job->title} in {$job->location}.",
                        null,
                        'job',
                        $actionUrl,
                    );
                }
            });
    }
}
