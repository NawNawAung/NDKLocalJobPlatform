<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\SalaryInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SalaryInsightsController extends Controller
{
    public function __invoke(Request $request, SalaryInsightsService $insights): JsonResponse
    {
        $filters = $request->validate([
            'keyword' => ['nullable', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:255'],
            'experience_level' => ['nullable', Rule::in(['entry', 'junior', 'mid', 'senior', 'lead', 'executive'])],
            'location' => ['nullable', 'string', 'max:120'],
            'category_id' => ['nullable', 'integer', Rule::exists('job_categories', 'id')->where('is_active', true)],
            'currency' => ['nullable', 'string', 'regex:/^[A-Z]{3}$/'],
            'compare_by' => ['nullable', Rule::in(['title', 'category'])],
            'compare_a' => ['nullable', 'string', 'max:255', 'required_with:compare_b'],
            'compare_b' => ['nullable', 'string', 'max:255', 'required_with:compare_a'],
        ]);

        $options = $insights->options();
        $filters['currency'] ??= in_array('MMK', $options['currencies'], true) ? 'MMK' : ($options['currencies'][0] ?? null);
        $matchingListings = $insights->filters($insights->eligibleListings(), $filters);
        $summary = $insights->summary($matchingListings);
        $comparison = [];

        if (isset($filters['compare_a'], $filters['compare_b'], $filters['compare_by'])) {
            foreach (['compare_a', 'compare_b'] as $key) {
                $comparison[] = [
                    'value' => $filters[$key],
                    'summary' => $insights->summary($insights->matching(clone $matchingListings, $filters['compare_by'], $filters[$key])),
                ];
            }
        }

        $jobs = (clone $matchingListings)
            ->with(['jobCategory:id,name', 'township:id,name,region_id', 'township.region:id,name'])
            ->orderByDesc('posted_at')
            ->limit(6)
            ->get()
            ->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'category' => $job->jobCategory?->name ?? $job->category,
                'location' => $job->township
                    ? $job->township->name.', '.$job->township->region?->name
                    : ($job->location ?: 'Location not specified'),
                'experience_level' => $job->experience_level,
                'salary_min' => $job->salary_min,
                'salary_max' => $job->salary_max,
                'currency' => $job->salary_currency,
                'posted_at' => $job->posted_at?->toIso8601String(),
            ]);

        return response()->json([
            'filters' => $filters,
            'options' => $options,
            'summary' => $summary,
            'distribution' => $insights->distribution(
                $matchingListings,
                $summary['minimum_salary'],
                $summary['maximum_salary'],
                $summary['sample_size'],
            ),
            'comparison' => $comparison,
            'jobs' => $jobs,
        ]);
    }
}
