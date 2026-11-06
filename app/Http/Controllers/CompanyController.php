<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
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
            'exclude_sponsored' => ['nullable', 'boolean'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $companies = Employer::query()
            ->whereHas('user', fn (Builder $user) => $user->where('status', true))
            ->with(['region:id,name', 'township:id,name,region_id'])
            ->withCount([
                'jobs as open_jobs_count' => fn (Builder $jobs) => $jobs->published(),
                'reviews as approved_reviews_count' => fn (Builder $reviews) => $reviews->where('status', 'approved'),
            ])
            ->withExists(['adCampaigns as is_sponsored' => fn (Builder $campaigns) => $campaigns
                ->where('campaign_type', 'featured_company')->where('placement', AdCampaign::COMPANY_SPOTLIGHT)
                ->where('status', 'active')->where(fn (Builder $active) => $active->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where('ends_at', '>', now())->where(fn (Builder $limit) => $limit->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn (Builder $reviews) => $reviews->where('status', 'approved')], 'rating')
            ->when($filters['hiring_only'] ?? false, fn (Builder $query) => $query->whereHas('jobs', fn (Builder $jobs) => $jobs->published()))
            ->when($filters['exclude_sponsored'] ?? false, fn (Builder $query) => $query->whereDoesntHave('adCampaigns', fn (Builder $campaigns) => $campaigns
                ->where('campaign_type', 'featured_company')->where('placement', AdCampaign::COMPANY_SPOTLIGHT)
                ->where('status', 'active')->where(fn (Builder $active) => $active->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where('ends_at', '>', now())->where(fn (Builder $limit) => $limit->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))))
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
        $employer->loadExists(['adCampaigns as is_sponsored' => fn (Builder $campaigns) => $campaigns
            ->where('campaign_type', 'featured_company')->where('placement', AdCampaign::COMPANY_SPOTLIGHT)
            ->where('status', 'active')->where(fn (Builder $active) => $active->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where('ends_at', '>', now())->where(fn (Builder $limit) => $limit->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))]);
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

    public function sponsoredCompanies(Request $request): JsonResponse
    {
        $filters = $request->validate(['limit' => ['nullable', 'integer', 'min:1', 'max:6']]);
        $displayLimit = (int) ($filters['limit'] ?? 4);
        $candidates = AdCampaign::query()
            ->with(['employer' => fn ($query) => $query->with(['user:id,status', 'region:id,name', 'township:id,name,region_id'])])
            ->where('campaign_type', 'featured_company')->where('placement', AdCampaign::COMPANY_SPOTLIGHT)
            ->where('status', 'active')->where(fn (Builder $active) => $active->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where('ends_at', '>', now())
            ->where(fn (Builder $limit) => $limit->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))
            ->whereHas('employer.user', fn (Builder $user) => $user->where('status', true))
            ->orderByDesc('created_at')->limit($displayLimit * 3)->get()
            ->unique('employer_id')->values();

        $items = [];
        foreach ($candidates as $campaign) {
            if (count($items) >= $displayLimit || ! $campaign->employer?->user?->status) {
                continue;
            }

            $recorded = AdCampaign::query()->whereKey($campaign->id)
                ->where('status', 'active')->where('ends_at', '>', now())
                ->where(fn (Builder $active) => $active->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn (Builder $limit) => $limit->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))
                ->increment('impressions_count');

            if (! $recorded) {
                continue;
            }

            $company = $campaign->employer;
            $company->setAttribute('is_sponsored', true);
            $items[] = [
                'campaign_id' => $campaign->id,
                'company' => $this->companySummary($company),
            ];
        }

        return response()->json(['data' => $items]);
    }

    private function companySummary(Employer $employer): array
    {
        if (! array_key_exists('open_jobs_count', $employer->getAttributes())) {
            $employer->setAttribute('open_jobs_count', $employer->jobs()->published()->count());
        }

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
            'is_sponsored' => (bool) ($employer->is_sponsored ?? false),
        ];
    }
}
