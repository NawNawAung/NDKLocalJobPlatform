<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Region;
use App\Models\Township;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class JobSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'region_id' => ['nullable', 'integer', 'exists:regions,id'],
            'township_id' => ['nullable', 'integer', Rule::exists('townships', 'id')->where(fn ($query) => $query->where('region_id', $request->input('region_id')))],
            'category' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')->where('is_active', true)],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time', 'contract', 'internship', 'temporary'])],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead'])],
            'work_mode' => ['nullable', Rule::in(['on_site', 'hybrid', 'remote'])],
            'salary_min' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'salary_max' => ['nullable', 'integer', 'min:0', 'max:100000000', 'gte:salary_min'],
            'date_posted' => ['nullable', Rule::in(['24h', '7d', '30d'])],
            'sort' => ['nullable', Rule::in(['latest', 'salary_high', 'salary_low'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $employer = $request->user()?->role === 'employer' ? $request->user()->employer : null;
        $jobs = Job::published()->with([
            'jobCategory:id,name,slug',
            'employer' => fn ($query) => $query->with('user:id,name')->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->where('status', 'approved')])->withAvg(['reviews as approved_reviews_avg_rating' => fn ($reviews) => $reviews->where('status', 'approved')], 'rating'),
        ])
            ->when($employer, fn (Builder $query) => $query->where('employer_id', '!=', $employer->id))
            ->with(['employer.user', 'township.region', 'promotions' => fn ($query) => $query->whereIn('status', ['active', 'scheduled'])->where('starts_at', '<=', now())->where('ends_at', '>', now())])
            ->withCount(['promotions as active_featured_promotions_count' => fn ($query) => $query->where('promotion_type', 'featured')->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())])
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where(function (Builder $match) use ($keyword) {
                    $match->where('title', 'like', "%{$keyword}%")
                        ->orWhere('description', 'like', "%{$keyword}%")
                        ->orWhere('requirements', 'like', "%{$keyword}%")
                        ->orWhereHas('employer', fn (Builder $employer) => $employer->where('company_name', 'like', "%{$keyword}%"));
                });
            })
            ->when(isset($filters['region_id']), function (Builder $query) use ($filters) {
                $region = Region::find($filters['region_id']);
                $query->where(function (Builder $location) use ($filters, $region) {
                    $location->whereHas('township', fn (Builder $township) => $township->where('region_id', $filters['region_id']));
                    if ($region) {
                        $legacyName = $region->name === 'Nay Pyi Taw' ? 'Naypyidaw' : $region->name;
                        $location->orWhere('location', 'like', "%{$region->name}%")
                            ->orWhere('location', 'like', "%{$legacyName}%");
                    }
                });
            })
            ->when(isset($filters['township_id']), function (Builder $query) use ($filters) {
                $township = Township::find($filters['township_id']);
                $query->where(function (Builder $location) use ($filters, $township) {
                    $location->where('township_id', $filters['township_id']);
                    if ($township) $location->orWhere('location', 'like', "%{$township->name}%");
                });
            })
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when(! isset($filters['category_id']) && ($filters['category'] ?? null), fn (Builder $query, string $category) => $query->where('category', 'like', "%{$category}%"))
            ->when($filters['employment_type'] ?? null, fn (Builder $query, string $type) => $query->where('employment_type', $type))
            ->when($filters['experience_level'] ?? null, fn (Builder $query, string $level) => $query->where('experience_level', $level))
            ->when($filters['work_mode'] ?? null, fn (Builder $query, string $mode) => $query->where('work_mode', $mode))
            ->when(isset($filters['salary_min']), fn (Builder $query) => $query->whereNotNull('salary_max')->where('salary_max', '>=', $filters['salary_min']))
            ->when(isset($filters['salary_max']), fn (Builder $query) => $query->whereNotNull('salary_min')->where('salary_min', '<=', $filters['salary_max']))
            ->when($filters['date_posted'] ?? null, function (Builder $query, string $range) {
                $cutoff = match ($range) {
                    '24h' => now()->subDay(),
                    '7d' => now()->subDays(7),
                    default => now()->subDays(30),
                };
                $query->where('posted_at', '>=', $cutoff);
            });

        $jobs->orderByDesc('active_featured_promotions_count');
        match ($filters['sort'] ?? 'latest') {
            'salary_high' => $jobs->orderByDesc('salary_max')->orderByDesc('posted_at'),
            'salary_low' => $jobs->orderBy('salary_min')->orderByDesc('posted_at'),
            default => $jobs->orderByDesc('posted_at'),
        };

        $results = $jobs->paginate(12)->withQueryString();
        $applicationStatuses = collect();
        $seeker = $request->user()?->role === 'job_seeker' ? $request->user()->jobSeeker : null;
        if ($seeker && $results->getCollection()->isNotEmpty()) {
            $applicationStatuses = $seeker->applications()
                ->whereIn('job_id', $results->getCollection()->pluck('id'))
                ->pluck('status', 'job_id');
        }

        return response()->json([
            'data' => $results->getCollection()->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->jobCategory?->name ?? $job->category,
                'category_id' => $job->category_id,
                'company' => $job->employer?->company_name ?? $job->employer?->user?->name ?? 'Employer',
                'company_rating' => $job->employer?->approved_reviews_avg_rating !== null ? round((float) $job->employer->approved_reviews_avg_rating, 1) : null,
                'company_review_count' => $job->employer?->approved_reviews_count ?? 0,
                'location' => $job->township
                    ? $job->township->name.', '.$job->township->region->name
                    : ($job->location ?: 'Location not specified'),
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'work_mode' => $job->work_mode,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_currency' => $job->salary_currency,
                'posted_at' => $job->posted_at?->toIso8601String(),
                'application_status' => $applicationStatuses->get($job->id),
                'is_featured' => $job->promotions->contains('promotion_type', 'featured'),
                'promotion_labels' => $job->promotions->pluck('promotion_type')->unique()->values(),
            ]),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'total' => $results->total(),
            ],
        ]);
    }
}
