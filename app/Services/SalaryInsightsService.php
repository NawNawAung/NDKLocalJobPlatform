<?php

namespace App\Services;

use App\Models\Job;
use Illuminate\Database\Eloquent\Builder;

class SalaryInsightsService
{
    private const MINIMUM_MEDIAN_SAMPLE = 5;

    private const LOWER_SALARY = 'CASE WHEN salary_min > 0 THEN salary_min ELSE salary_max END';

    private const UPPER_SALARY = 'CASE WHEN salary_max > 0 THEN salary_max ELSE salary_min END';

    private const REPRESENTATIVE_SALARY = '((1.0 * (CASE WHEN salary_min > 0 THEN salary_min ELSE salary_max END + CASE WHEN salary_max > 0 THEN salary_max ELSE salary_min END)) / 2)';

    public function eligibleListings(): Builder
    {
        return Job::published()
            ->where(function (Builder $query) {
                $query->where('salary_min', '>', 0)->orWhere('salary_max', '>', 0);
            })
            ->where(function (Builder $query) {
                $query->whereNull('salary_min')
                    ->orWhereNull('salary_max')
                    ->orWhereColumn('salary_max', '>=', 'salary_min');
            });
    }

    public function summary(Builder $listings): array
    {
        $aggregates = (clone $listings)->selectRaw(
            'COUNT(*) AS sample_size, MIN('.self::LOWER_SALARY.') AS minimum_salary, MAX('.self::UPPER_SALARY.') AS maximum_salary'
        )->first();
        $count = (int) ($aggregates->sample_size ?? 0);
        $median = null;

        if ($count >= self::MINIMUM_MEDIAN_SAMPLE) {
            $middle = (clone $listings)
                ->reorder()
                ->selectRaw(self::REPRESENTATIVE_SALARY.' AS representative_salary')
                ->orderByRaw(self::REPRESENTATIVE_SALARY.' ASC')
                ->offset((int) floor(($count - 1) / 2))
                ->limit($count % 2 === 0 ? 2 : 1)
                ->pluck('representative_salary')
                ->map(fn ($salary) => (float) $salary);

            $median = $middle->avg();
        }

        return [
            'sample_size' => $count,
            'minimum_salary' => $aggregates->minimum_salary !== null ? (float) $aggregates->minimum_salary : null,
            'maximum_salary' => $aggregates->maximum_salary !== null ? (float) $aggregates->maximum_salary : null,
            'median_salary' => $median,
            'median_available' => $count >= self::MINIMUM_MEDIAN_SAMPLE,
            'minimum_median_sample' => self::MINIMUM_MEDIAN_SAMPLE,
        ];
    }

    public function distribution(Builder $listings, ?float $minimum, ?float $maximum, int $sampleSize): array
    {
        if ($sampleSize === 0 || $minimum === null || $maximum === null) {
            return [];
        }

        if ($minimum === $maximum) {
            return [['minimum' => $minimum, 'maximum' => $maximum, 'count' => $sampleSize]];
        }

        $width = ($maximum - $minimum) / 5;
        $counts = array_fill(0, 5, 0);
        foreach ((clone $listings)->reorder()->select(['salary_min', 'salary_max'])->cursor() as $listing) {
            $low = $listing->salary_min > 0 ? (float) $listing->salary_min : (float) $listing->salary_max;
            $high = $listing->salary_max > 0 ? (float) $listing->salary_max : (float) $listing->salary_min;
            $representative = ($low + $high) / 2;
            $bucket = min(4, max(0, (int) floor(($representative - $minimum) / $width)));
            $counts[$bucket]++;
        }

        return collect(range(0, 4))->map(function (int $index) use ($minimum, $maximum, $width, $counts) {
            $bucketMinimum = $minimum + $width * $index;
            $bucketMaximum = $index === 4 ? $maximum : $minimum + $width * ($index + 1);

            return [
                'minimum' => $bucketMinimum,
                'maximum' => $bucketMaximum,
                'count' => $counts[$index],
            ];
        })->all();
    }

    public function filters(Builder $listings, array $filters): Builder
    {
        return (clone $listings)
            ->when($filters['keyword'] ?? null, function (Builder $query, string $keyword) {
                $query->where('title', 'like', '%'.$keyword.'%');
            })
            ->when($filters['title'] ?? null, fn (Builder $query, string $title) => $query->where('title', $title))
            ->when($filters['experience_level'] ?? null, fn (Builder $query, string $level) => $query->where('experience_level', $level))
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['currency'] ?? null, fn (Builder $query, string $currency) => $query->where('salary_currency', $currency))
            ->when($filters['location'] ?? null, function (Builder $query, string $location) {
                $query->where(function (Builder $matches) use ($location) {
                    $matches->where('location', 'like', '%'.$location.'%')
                        ->orWhereHas('township', fn (Builder $township) => $township->where('name', 'like', '%'.$location.'%')->orWhereHas('region', fn (Builder $region) => $region->where('name', 'like', '%'.$location.'%')));
                });
            });
    }

    public function options(): array
    {
        $listings = $this->eligibleListings();

        return [
            'titles' => (clone $listings)->whereNotNull('title')->distinct()->orderBy('title')->pluck('title')->values(),
            'categories' => (clone $listings)->whereNotNull('category_id')->with('jobCategory:id,name')->get(['category_id'])->pluck('jobCategory')->filter()->unique('id')->values()->map(fn ($category) => ['id' => $category->id, 'name' => $category->name]),
            'currencies' => (clone $listings)->whereNotNull('salary_currency')->distinct()->orderBy('salary_currency')->pluck('salary_currency')->values(),
        ];
    }

    public function matching(Builder $listings, string $type, string $value): Builder
    {
        return $type === 'category'
            ? $listings->where('category_id', (int) $value)
            : $listings->where('title', $value);
    }
}
