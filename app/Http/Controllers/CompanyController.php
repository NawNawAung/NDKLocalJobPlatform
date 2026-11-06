<?php

namespace App\Http\Controllers;

use App\Models\Employer;
use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'hiring_only' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $companies = Employer::query()
            ->whereHas('user', fn (Builder $user) => $user->where('status', true))
            ->with(['region:id,name', 'township:id,name,region_id'])
            ->withCount([
                'jobs as open_jobs_count' => fn (Builder $jobs) => $jobs->published(),
                'reviews as approved_reviews_count' => fn (Builder $reviews) => $reviews->where('status', 'approved'),
            ])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn (Builder $reviews) => $reviews->where('status', 'approved')], 'rating')
            ->when($filters['hiring_only'] ?? false, fn (Builder $query) => $query->whereHas('jobs', fn (Builder $jobs) => $jobs->published()))
            ->when($filters['q'] ?? null, function (Builder $query, string $term) {
                $query->where(function (Builder $matches) use ($term) {
                    $matches->where('company_name', 'like', "%{$term}%")
                        ->orWhere('company_description', 'like', "%{$term}%")
                        ->orWhere('location', 'like', "%{$term}%")
                        ->orWhereHas('region', fn (Builder $region) => $region->where('name', 'like', "%{$term}%"))
                        ->orWhereHas('township', fn (Builder $township) => $township->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('open_jobs_count')
            ->orderBy('company_name')
            ->paginate(12)
            ->withQueryString();

        return response()->json([
            'companies' => $companies->through(fn (Employer $employer) => $this->companySummary($employer)),
        ]);
    }

    public function show(Employer $employer): JsonResponse
    {
        abort_unless($employer->user()->where('status', true)->exists(), 404);

        $employer->load(['region:id,name', 'township:id,name,region_id']);
        $employer->loadCount([
            'jobs as open_jobs_count' => fn (Builder $jobs) => $jobs->published(),
            'reviews as approved_reviews_count' => fn (Builder $reviews) => $reviews->where('status', 'approved'),
        ]);
        $employer->loadAvg(['reviews as approved_reviews_avg_rating' => fn (Builder $reviews) => $reviews->where('status', 'approved')], 'rating');

        $jobs = Job::published()
            ->where('employer_id', $employer->id)
            ->with(['jobCategory:id,name', 'township.region:id,name', 'promotions' => fn ($promotions) => $promotions
                ->where('promotion_type', 'featured')->where('status', 'active')->where('starts_at', '<=', now())->where('ends_at', '>', now())])
            ->latest('posted_at')
            ->paginate(10);

        return response()->json([
            'company' => $this->companySummary($employer),
            'jobs' => $jobs->through(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->jobCategory?->name ?? $job->category,
                'location' => $job->township ? $job->township->name.', '.$job->township->region?->name : ($job->location ?: 'Location not specified'),
                'employment_type' => $job->employment_type,
                'experience_level' => $job->experience_level,
                'work_mode' => $job->work_mode,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'salary_currency' => $job->salary_currency,
                'posted_at' => $job->posted_at?->toIso8601String(),
                'is_featured' => $job->promotions->isNotEmpty(),
            ]),
        ]);
    }

    private function companySummary(Employer $employer): array
    {
        return [
            'id' => $employer->id,
            'name' => $employer->company_name,
            'description' => $employer->company_description,
            'location' => collect([$employer->location, $employer->township?->name, $employer->region?->name])->filter()->unique()->join(', '),
            'website_url' => $employer->website_url,
            'social_links' => $employer->social_links ?? [],
            'is_verified' => (bool) $employer->is_verified,
            'open_jobs_count' => $employer->open_jobs_count ?? 0,
            'average_rating' => $employer->approved_reviews_avg_rating !== null ? round((float) $employer->approved_reviews_avg_rating, 1) : null,
            'review_count' => $employer->approved_reviews_count ?? 0,
        ];
    }
}
