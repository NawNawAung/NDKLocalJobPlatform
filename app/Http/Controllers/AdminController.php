<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\ContentReport;
use App\Models\Employer;
use App\Models\Job;
use App\Models\JobSeeker;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    private function authorizeAdmin(Request $request): User
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status, 403);
        return $request->user();
    }

    public function dashboard(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $activity = collect()
            ->concat(User::latest()->limit(6)->get(['id', 'name', 'role', 'created_at'])->map(fn ($row) => ['type' => 'user', 'label' => "New {$row->role} account", 'detail' => $row->name, 'at' => $row->created_at]))
            ->concat(Job::latest()->limit(6)->get(['id', 'title', 'status', 'created_at'])->map(fn ($row) => ['type' => 'job', 'label' => "Job listing {$row->status}", 'detail' => $row->title, 'at' => $row->created_at]))
            ->concat(Application::latest('submitted_at')->limit(6)->get(['id', 'job_id', 'submitted_at'])->map(fn ($row) => ['type' => 'application', 'label' => 'Application submitted', 'detail' => "Application #{$row->id}", 'at' => $row->submitted_at]))
            ->concat(ContentReport::latest()->limit(6)->get(['id', 'target_type', 'status', 'created_at'])->map(fn ($row) => ['type' => 'report', 'label' => "Content report {$row->status}", 'detail' => ucfirst($row->target_type).' #'.$row->id, 'at' => $row->created_at]))
            ->sortByDesc('at')->take(12)->values();

        return response()->json([
            'stats' => [
                'users' => User::count(), 'job_seekers' => User::where('role', 'job_seeker')->count(),
                'employers' => Employer::count(), 'active_jobs' => Job::published()->count(),
                'pending_jobs' => Job::whereIn('status', ['draft'])->count(), 'applications' => Application::count(),
                'pending_verifications' => Employer::where('verification_status', 'pending')->count(),
                'open_reports' => ContentReport::where('status', 'open')->count(),
            ],
            'job_activity' => Job::query()->selectRaw('DATE(created_at) as date, COUNT(*) as total')->where('created_at', '>=', now()->subDays(13)->startOfDay())->groupBy(DB::raw('DATE(created_at)'))->orderBy('date')->get(),
            'recent_activity' => $activity,
        ]);
    }

    public function users(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'role' => ['nullable', Rule::in(['job_seeker', 'employer', 'admin'])], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $users = User::query()->with(['employer:id,user_id,company_name,verification_status,is_verified', 'jobSeeker:id,user_id,professional_title,years_experience'])
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn ($search) => $search->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%")))
            ->when($filters['role'] ?? null, fn (Builder $query, $role) => $query->where('role', $role))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('status', $filters['status'] === 'active'))
            ->latest()->paginate(25);
        return response()->json(['users' => $users->through(fn (User $user) => [
            'id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role,
            'status' => (bool) $user->status, 'created_at' => $user->created_at?->toIso8601String(),
            'company' => $user->employer?->company_name, 'verification_status' => $user->employer?->verification_status,
            'professional_title' => $user->jobSeeker?->professional_title,
        ])]);
    }

    public function updateUser(Request $request, User $user): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        abort_if($user->id === $admin->id || $user->role === 'admin', 422, 'Administrator accounts cannot be changed here.');
        $data = $request->validate(['status' => ['required', 'boolean']]);
        DB::transaction(function () use ($user, $data) {
            $user->update(['status' => $data['status']]);
            $user->jobSeeker?->update(['status' => $data['status']]);
        });
        return response()->json(['message' => 'Account status updated.']);
    }

    public function employers(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'verification_status' => ['nullable', Rule::in(['unverified', 'pending', 'verified', 'rejected'])]]);
        $rows = Employer::with('user:id,name,email,status')->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn (Builder $match) => $match->where('company_name', 'like', "%{$q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))))
            ->when($filters['verification_status'] ?? null, fn (Builder $query, $status) => $query->where('verification_status', $status))->latest()->paginate(25);
        return response()->json(['employers' => $rows->through(fn (Employer $employer) => [
            'id' => $employer->id, 'user_id' => $employer->user_id, 'company_name' => $employer->company_name,
            'description' => $employer->company_description, 'location' => $employer->location, 'website_url' => $employer->website_url,
            'verification_status' => $employer->verification_status ?? ($employer->is_verified ? 'verified' : 'unverified'),
            'is_verified' => $employer->is_verified, 'requested_at' => $employer->verification_requested_at?->toIso8601String(),
            'notes' => $employer->verification_notes, 'created_at' => $employer->created_at?->toIso8601String(), 'user' => ['name' => $employer->user?->name, 'email' => $employer->user?->email, 'active' => (bool) $employer->user?->status],
            'rating' => null,
        ])]);
    }

    public function reviewEmployer(Request $request, Employer $employer): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject', 'reset'])], 'notes' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject']]);
        $status = match ($data['decision']) { 'approve' => 'verified', 'reject' => 'rejected', default => 'unverified' };
        $employer->update([
            'is_verified' => $status === 'verified', 'verification_status' => $status,
            'verification_notes' => $data['notes'] ?? null,
            'verification_reviewed_at' => now(), 'verification_reviewed_by' => $admin->id,
        ]);
        return response()->json(['message' => 'Employer verification updated.', 'verification_status' => $status]);
    }

    public function jobs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(['draft', 'published', 'paused', 'closed', 'expired'])], 'category' => ['nullable', 'string', 'max:100']]);
        $rows = Job::with('employer:id,company_name')->withCount('applications')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn ($where) => $where->where('title', 'like', "%{$q}%")->orWhereHas('employer', fn ($e) => $e->where('company_name', 'like', "%{$q}%"))))
            ->when(($filters['status'] ?? null) === 'expired', fn (Builder $query) => $query->where('status', 'published')->whereDate('application_deadline', '<', today()))
            ->when(isset($filters['status']) && $filters['status'] !== 'expired', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['category'] ?? null, fn (Builder $query, $category) => $query->where('category', $category))->latest()->paginate(25);
        return response()->json([
            'jobs' => $rows->through(fn (Job $job) => ['id' => $job->id, 'title' => $job->title, 'company' => $job->employer?->company_name, 'category' => $job->category, 'location' => $job->location, 'description' => $job->description, 'requirements' => $job->requirements, 'salary_min' => $job->salary_min, 'salary_max' => $job->salary_max, 'status' => $job->application_deadline?->isBefore(today()) && $job->status === 'published' ? 'expired' : $job->status, 'applications_count' => $job->applications_count, 'created_at' => $job->created_at?->toIso8601String(), 'deadline' => $job->application_deadline?->toDateString()]),
            'categories' => Job::query()->distinct()->orderBy('category')->pluck('category')->filter()->values(),
        ]);
    }

    public function moderateJob(Request $request, Job $job): JsonResponse
    {
        $this->authorizeAdmin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'paused', 'closed'])]]);
        if ($data['status'] === 'published' && $job->application_deadline?->isBefore(today())) abort(422, 'An expired job cannot be published. Update its expiry date first.');
        $job->update(['status' => $data['status']]);
        return response()->json(['message' => 'Job listing status updated.']);
    }

    public function applications(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $rows = Application::with(['job:id,title,employer_id', 'job.employer:id,company_name'])->latest('submitted_at')->paginate(25);
        return response()->json(['applications' => $rows->through(fn (Application $application) => ['id' => $application->id, 'job_title' => $application->job?->title, 'company' => $application->job?->employer?->company_name, 'status' => $application->status, 'submitted_at' => $application->submitted_at?->toIso8601String()])]);
    }

    public function report(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate(['target_type' => ['required', Rule::in(['job', 'employer', 'user'])], 'target_id' => ['required', 'integer', 'min:1'], 'reason' => ['required', Rule::in(['fraud', 'misleading', 'abusive', 'inappropriate', 'other'])], 'details' => ['nullable', 'string', 'max:2000']]);
        if ($data['target_type'] === 'job') Job::findOrFail($data['target_id']);
        elseif ($data['target_type'] === 'employer') Employer::findOrFail($data['target_id']);
        else {
            $target = User::findOrFail($data['target_id']);
            abort_if($target->id === $user->id, 422, 'An account cannot report itself.');
        }
        $report = ContentReport::create([...$data, 'reported_by' => $user->id]);
        return response()->json(['message' => 'Report submitted for review.', 'id' => $report->id], 201);
    }

    public function reports(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['status' => ['nullable', Rule::in(['open', 'actioned', 'dismissed'])]]);
        $rows = ContentReport::with(['reporter:id,name,email'])->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))->latest()->paginate(25);
        return response()->json(['reports' => $rows->through(function (ContentReport $report) {
            $target = match ($report->target_type) {
                'job' => Job::find($report->target_id),
                'employer' => Employer::find($report->target_id),
                default => User::find($report->target_id),
            };
            $label = match ($report->target_type) {
                'job' => $target?->title ?? '[Removed job]',
                'employer' => $target?->company_name ?? '[Removed employer]',
                default => $target?->name ?? '[Removed user]',
            };
            return ['id' => $report->id, 'target_type' => $report->target_type, 'target_id' => $report->target_id, 'target_label' => $label, 'reason' => $report->reason, 'details' => $report->details, 'status' => $report->status, 'created_at' => $report->created_at?->toIso8601String(), 'reporter' => $report->reporter?->name];
        })]);
    }

    public function reviewReport(Request $request, ContentReport $report): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['decision' => ['required', Rule::in(['actioned', 'dismissed'])]]);
        DB::transaction(function () use ($report, $data, $admin) {
            if ($data['decision'] === 'actioned') {
                if ($report->target_type === 'job') Job::whereKey($report->target_id)->update(['status' => 'closed']);
                if (in_array($report->target_type, ['user', 'employer'], true)) {
                    $reportedUser = $report->target_type === 'employer'
                        ? Employer::find($report->target_id)?->user
                        : User::find($report->target_id);
                    if ($reportedUser && $reportedUser->role !== 'admin') {
                        $reportedUser->update(['status' => false]);
                        $reportedUser->jobSeeker?->update(['status' => false]);
                    }
                }
            }
            $report->update(['status' => $data['decision'], 'reviewed_by' => $admin->id, 'reviewed_at' => now()]);
        });
        return response()->json(['message' => 'Report reviewed.']);
    }
}
