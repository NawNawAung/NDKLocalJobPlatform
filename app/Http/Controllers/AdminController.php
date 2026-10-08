<?php

namespace App\Http\Controllers;

use App\Models\Application;
use App\Models\AdminAuditLog;
use App\Models\ContentReport;
use App\Models\Employer;
use App\Models\EmployerReview;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

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
        $data = $request->validate(['status' => ['sometimes', 'required', 'boolean'], 'role' => ['sometimes', 'required', Rule::in(['job_seeker', 'employer'])]]);
        abort_if(! array_key_exists('status', $data) && ! array_key_exists('role', $data), 422, 'Choose an account status or role update.');
        if (isset($data['role'])) {
            $hasProfile = $data['role'] === 'job_seeker' ? $user->jobSeeker()->exists() : $user->employer()->exists();
            abort_unless($hasProfile, 422, 'The account has no profile for the selected role. Role changes cannot create or discard profile data.');
            $otherProfileExists = $data['role'] === 'job_seeker' ? $user->employer()->exists() : $user->jobSeeker()->exists();
            abort_if($otherProfileExists, 422, 'This account has profiles for both roles and cannot be switched safely.');
        }
        $before = ['status' => (bool) $user->status, 'role' => $user->role];
        DB::transaction(function () use ($request, $admin, $user, $data, $before) {
            $user->update(array_intersect_key($data, array_flip(['status', 'role'])));
            if (array_key_exists('status', $data)) $user->jobSeeker?->update(['status' => $data['status']]);
            $this->audit->record($request, $admin, 'user.updated', $user, $before, ['status' => (bool) $user->status, 'role' => $user->role]);
        });
        return response()->json(['message' => 'Account updated.']);
    }

    public function employers(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'verification_status' => ['nullable', Rule::in(['unverified', 'pending', 'verified', 'rejected'])]]);
        $rows = Employer::with('user:id,name,email,status')->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->where('status', 'approved')])->withAvg(['reviews as approved_reviews_avg_rating' => fn ($reviews) => $reviews->where('status', 'approved')], 'rating')->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn (Builder $match) => $match->where('company_name', 'like', "%{$q}%")->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$q}%"))))
            ->when($filters['verification_status'] ?? null, fn (Builder $query, $status) => $query->where('verification_status', $status))->latest()->paginate(25);
        return response()->json(['employers' => $rows->through(fn (Employer $employer) => [
            'id' => $employer->id, 'user_id' => $employer->user_id, 'company_name' => $employer->company_name,
            'description' => $employer->company_description, 'location' => $employer->location, 'website_url' => $employer->website_url,
            'verification_status' => $employer->verification_status ?? ($employer->is_verified ? 'verified' : 'unverified'),
            'is_verified' => $employer->is_verified, 'requested_at' => $employer->verification_requested_at?->toIso8601String(),
            'notes' => $employer->verification_notes, 'created_at' => $employer->created_at?->toIso8601String(), 'user' => ['name' => $employer->user?->name, 'email' => $employer->user?->email, 'active' => (bool) $employer->user?->status],
            'rating' => $employer->approved_reviews_avg_rating !== null ? round((float) $employer->approved_reviews_avg_rating, 1) : null,
            'review_count' => $employer->approved_reviews_count,
        ])]);
    }

    public function reviewEmployer(Request $request, Employer $employer): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject', 'reset'])], 'notes' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject']]);
        $status = match ($data['decision']) { 'approve' => 'verified', 'reject' => 'rejected', default => 'unverified' };
        $before = ['is_verified' => (bool) $employer->is_verified, 'verification_status' => $employer->verification_status];
        DB::transaction(function () use ($request, $admin, $employer, $status, $data, $before) {
            $employer->update([
                'is_verified' => $status === 'verified', 'verification_status' => $status,
                'verification_notes' => $data['notes'] ?? null,
                'verification_reviewed_at' => now(), 'verification_reviewed_by' => $admin->id,
            ]);
            $this->audit->record($request, $admin, 'employer.verification.'.$data['decision'], $employer, $before, ['is_verified' => $employer->is_verified, 'verification_status' => $status, 'notes' => $data['notes'] ?? null]);
        });
        return response()->json(['message' => 'Employer verification updated.', 'verification_status' => $status]);
    }

    public function jobs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(['draft', 'published', 'paused', 'closed', 'expired'])], 'category_id' => ['nullable', 'integer', 'exists:job_categories,id']]);
        $rows = Job::with(['employer:id,company_name', 'jobCategory:id,name'])->withCount('applications')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn ($where) => $where->where('title', 'like', "%{$q}%")->orWhereHas('employer', fn ($e) => $e->where('company_name', 'like', "%{$q}%"))))
            ->when(($filters['status'] ?? null) === 'expired', fn (Builder $query) => $query->where('status', 'published')->whereDate('application_deadline', '<', today()))
            ->when(isset($filters['status']) && $filters['status'] !== 'expired', fn (Builder $query) => $query->where('status', $filters['status']))
            ->when($filters['category_id'] ?? null, fn (Builder $query, $categoryId) => $query->where('category_id', $categoryId))->latest()->paginate(25);
        return response()->json([
            'jobs' => $rows->through(fn (Job $job) => ['id' => $job->id, 'title' => $job->title, 'company' => $job->employer?->company_name, 'category' => $job->jobCategory?->name ?? $job->category, 'category_id' => $job->category_id, 'location' => $job->location, 'description' => $job->description, 'requirements' => $job->requirements, 'salary_min' => $job->salary_min, 'salary_max' => $job->salary_max, 'status' => $job->application_deadline?->isBefore(today()) && $job->status === 'published' ? 'expired' : $job->status, 'applications_count' => $job->applications_count, 'created_at' => $job->created_at?->toIso8601String(), 'deadline' => $job->application_deadline?->toDateString()]),
            'categories' => JobCategory::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function moderateJob(Request $request, Job $job): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['status' => ['required', Rule::in(['draft', 'published', 'paused', 'closed'])]]);
        if ($data['status'] === 'published' && $job->application_deadline?->isBefore(today())) abort(422, 'An expired job cannot be published. Update its expiry date first.');
        $before = ['status' => $job->status];
        DB::transaction(function () use ($request, $admin, $job, $data, $before) {
            $job->update(['status' => $data['status']]);
            $this->audit->record($request, $admin, 'job.moderated', $job, $before, ['status' => $job->status]);
        });
        return response()->json(['message' => 'Job listing status updated.']);
    }

    public function applications(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $rows = Application::with(['job:id,title,employer_id', 'job.employer:id,company_name'])->latest('submitted_at')->paginate(25);
        return response()->json(['applications' => $rows->through(fn (Application $application) => ['id' => $application->id, 'job_title' => $application->job?->title, 'company' => $application->job?->employer?->company_name, 'status' => $application->status, 'submitted_at' => $application->submitted_at?->toIso8601String()])]);
    }

    public function categories(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(['active', 'inactive'])]]);
        $categories = JobCategory::query()->withCount('jobs')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where('name', 'like', "%{$q}%"))
            ->when(isset($filters['status']), fn (Builder $query) => $query->where('is_active', $filters['status'] === 'active'))
            ->orderBy('sort_order')->orderBy('name')->paginate(25);
        return response()->json(['categories' => $categories->through(fn (JobCategory $category) => ['id' => $category->id, 'name' => $category->name, 'slug' => $category->slug, 'is_active' => $category->is_active, 'sort_order' => $category->sort_order, 'jobs_count' => $category->jobs_count, 'created_at' => $category->created_at?->toIso8601String()])]);
    }

    public function createCategory(Request $request): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:job_categories,name'], 'sort_order' => ['nullable', 'integer', 'min:0', 'max:65535'], 'is_active' => ['nullable', 'boolean']]);
        $slug = Str::slug($data['name']);
        abort_if($slug === '', 422, 'Category name must contain letters or numbers.');
        $baseSlug = $slug;
        $suffix = 2;
        while (JobCategory::where('slug', $slug)->exists()) $slug = $baseSlug.'-'.$suffix++;
        $category = DB::transaction(function () use ($request, $admin, $data, $slug) {
            $category = JobCategory::create(['name' => trim($data['name']), 'slug' => $slug, 'sort_order' => $data['sort_order'] ?? 0, 'is_active' => $data['is_active'] ?? true]);
            $this->audit->record($request, $admin, 'job_category.created', $category, null, ['name' => $category->name, 'is_active' => $category->is_active, 'sort_order' => $category->sort_order]);
            return $category;
        });
        return response()->json(['message' => 'Job category created.', 'category' => $category], 201);
    }

    public function updateCategory(Request $request, JobCategory $jobCategory): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('job_categories', 'name')->ignore($jobCategory->id)],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);
        abort_if($data === [], 422, 'Provide a category field to update.');
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            $baseSlug = Str::slug($data['name']);
            abort_if($baseSlug === '', 422, 'Category name must contain letters or numbers.');
            $slug = $baseSlug;
            $suffix = 2;
            while (JobCategory::where('slug', $slug)->where('id', '!=', $jobCategory->id)->exists()) $slug = $baseSlug.'-'.$suffix++;
            $data['slug'] = $slug;
        }
        $before = ['name' => $jobCategory->name, 'is_active' => $jobCategory->is_active, 'sort_order' => $jobCategory->sort_order];
        DB::transaction(function () use ($request, $admin, $jobCategory, $data, $before) {
            $jobCategory->update($data);
            if (isset($data['name'])) Job::where('category_id', $jobCategory->id)->update(['category' => $jobCategory->name]);
            $this->audit->record($request, $admin, 'job_category.updated', $jobCategory, $before, ['name' => $jobCategory->name, 'is_active' => $jobCategory->is_active, 'sort_order' => $jobCategory->sort_order]);
        });
        return response()->json(['message' => 'Job category updated.', 'category' => $jobCategory->fresh()]);
    }

    public function reviews(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:120'], 'status' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])]]);
        $reviews = EmployerReview::with(['employer:id,company_name', 'jobSeeker.user:id,name', 'application.job:id,title'])
            ->when($filters['status'] ?? null, fn (Builder $query, $status) => $query->where('status', $status))
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn (Builder $match) => $match->where('review', 'like', "%{$q}%")->orWhereHas('employer', fn ($employer) => $employer->where('company_name', 'like', "%{$q}%"))->orWhereHas('jobSeeker.user', fn ($user) => $user->where('name', 'like', "%{$q}%"))))
            ->latest()->paginate(25);
        return response()->json(['reviews' => $reviews->through(fn (EmployerReview $review) => ['id' => $review->id, 'employer' => $review->employer?->company_name, 'reviewer' => $review->jobSeeker?->user?->name, 'job_title' => $review->application?->job?->title, 'rating' => $review->rating, 'title' => $review->title, 'review' => $review->review, 'status' => $review->status, 'moderation_notes' => $review->moderation_notes, 'created_at' => $review->created_at?->toIso8601String()])]);
    }

    public function moderateReview(Request $request, EmployerReview $employerReview): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate(['decision' => ['required', Rule::in(['approve', 'reject'])], 'notes' => ['nullable', 'string', 'max:2000', 'required_if:decision,reject']]);
        $before = ['status' => $employerReview->status];
        DB::transaction(function () use ($request, $admin, $data, $employerReview, $before) {
            $employerReview->update(['status' => $data['decision'] === 'approve' ? 'approved' : 'rejected', 'moderation_notes' => $data['notes'] ?? null, 'moderated_by' => $admin->id, 'moderated_at' => now()]);
            $this->audit->record($request, $admin, 'employer_review.'.$data['decision'], $employerReview, $before, ['status' => $employerReview->status, 'notes' => $employerReview->moderation_notes]);
        });
        return response()->json(['message' => 'Employer review moderated.', 'status' => $employerReview->status]);
    }

    public function auditLogs(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'], 'action' => ['nullable', 'string', 'max:100'],
            'administrator_id' => ['nullable', 'integer', 'exists:users,id'], 'target_type' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);
        $logs = AdminAuditLog::with('administrator:id,name,email')
            ->when($filters['q'] ?? null, fn (Builder $query, $q) => $query->where(fn (Builder $match) => $match->where('action', 'like', "%{$q}%")->orWhere('target_type', 'like', "%{$q}%")->orWhere('target_id', 'like', "%{$q}%")))
            ->when($filters['action'] ?? null, fn (Builder $query, $action) => $query->where('action', $action))
            ->when($filters['administrator_id'] ?? null, fn (Builder $query, $id) => $query->where('administrator_id', $id))
            ->when($filters['target_type'] ?? null, fn (Builder $query, $type) => $query->where('target_type', 'like', "%{$type}%"))
            ->when($filters['from'] ?? null, fn (Builder $query, $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, $to) => $query->whereDate('created_at', '<=', $to))
            ->latest('created_at')->paginate(30)->withQueryString();
        return response()->json(['actions' => AdminAuditLog::query()->distinct()->orderBy('action')->pluck('action'), 'administrators' => User::where('role', 'admin')->orderBy('name')->get(['id', 'name']), 'logs' => $logs->through(fn (AdminAuditLog $log) => ['id' => $log->id, 'administrator_id' => $log->administrator_id, 'administrator' => $log->administrator?->name ?? '[Deleted account]', 'action' => $log->action, 'target_type' => $log->target_type, 'target_id' => $log->target_id, 'before' => $log->before, 'after' => $log->after, 'ip_address' => $log->ip_address, 'created_at' => $log->created_at?->toIso8601String()])]);
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
        $before = ['status' => $report->status];
        DB::transaction(function () use ($request, $report, $data, $admin, $before) {
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
            $this->audit->record($request, $admin, 'content_report.'.$data['decision'], $report, $before, ['status' => $report->status, 'target_type' => $report->target_type, 'target_id' => $report->target_id]);
        });
        return response()->json(['message' => 'Report reviewed.']);
    }
}
