<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryInsightsTest extends TestCase
{
    use RefreshDatabase;

    public function test_salary_explorer_calculates_statistics_from_valid_published_monthly_listings(): void
    {
        $employer = $this->employer();
        $category = $this->category('Technology');

        foreach ([100000, 200000, 300000, 400000, 500000] as $minimum) {
            $this->listing($employer, $category, 'Software Engineer', $minimum, $minimum + 1);
        }

        $this->listing($employer, $category, 'Software Engineer', 0, 0);
        $this->listing($employer, $category, 'Draft Engineer', 1000000, 1500000, 'draft');

        $this->getJson('/api/salary-insights?currency=MMK')
            ->assertOk()
            ->assertJsonPath('summary.sample_size', 5)
            ->assertJsonPath('summary.minimum_salary', 100000)
            ->assertJsonPath('summary.maximum_salary', 500001)
            ->assertJsonPath('summary.median_salary', 300000.5)
            ->assertJsonPath('summary.median_available', true)
            ->assertJsonPath('distribution.0.count', 1)
            ->assertJsonPath('jobs.0.title', 'Software Engineer');
    }

    public function test_salary_explorer_filters_records_and_compares_only_the_selected_currency(): void
    {
        $employer = $this->employer();
        $technology = $this->category('Technology');
        $finance = $this->category('Finance');

        foreach ([300000, 400000, 500000, 600000, 700000] as $minimum) {
            $this->listing($employer, $technology, 'Developer', $minimum, $minimum + 100000, 'published', 'MMK', 'mid', 'Yangon');
        }
        foreach ([500, 600, 700, 800, 900] as $minimum) {
            $this->listing($employer, $finance, 'Analyst', $minimum, $minimum + 100, 'published', 'USD', 'senior', 'Remote');
        }

        $this->getJson('/api/salary-insights?currency=MMK&experience_level=mid&location=Yangon&category_id='.$technology->id.'&compare_by=category&compare_a='.$technology->id.'&compare_b='.$finance->id)
            ->assertOk()
            ->assertJsonPath('summary.sample_size', 5)
            ->assertJsonPath('summary.median_salary', 550000)
            ->assertJsonPath('filters.currency', 'MMK')
            ->assertJsonPath('comparison.0.summary.sample_size', 5)
            ->assertJsonPath('comparison.1.summary.sample_size', 0);
    }

    public function test_median_is_hidden_when_there_are_fewer_than_five_comparable_listings(): void
    {
        $employer = $this->employer();
        $category = $this->category('Technology');

        foreach ([100000, 200000, 300000, 400000] as $minimum) {
            $this->listing($employer, $category, 'Developer', $minimum, $minimum + 100000);
        }

        $this->getJson('/api/salary-insights?currency=MMK')
            ->assertOk()
            ->assertJsonPath('summary.sample_size', 4)
            ->assertJsonPath('summary.median_salary', null)
            ->assertJsonPath('summary.median_available', false);
    }

    public function test_salary_explorer_rejects_invalid_currency_experience_and_inactive_category_filters(): void
    {
        $category = $this->category('Archived', false);

        $this->getJson('/api/salary-insights?currency=MMKX')->assertUnprocessable();
        $this->getJson('/api/salary-insights?experience_level=expert')->assertUnprocessable();
        $this->getJson('/api/salary-insights?category_id='.$category->id)->assertUnprocessable();
    }

    private function employer(): Employer
    {
        $user = User::create([
            'name' => 'Salary Data Employer',
            'email' => 'salary-employer@example.test',
            'password' => 'password123',
            'role' => 'employer',
            'status' => true,
        ]);

        return Employer::create(['user_id' => $user->id, 'company_name' => 'Salary Data Company']);
    }

    private function category(string $name, bool $active = true): JobCategory
    {
        return JobCategory::create([
            'name' => $name,
            'slug' => strtolower($name),
            'is_active' => $active,
        ]);
    }

    private function listing(
        Employer $employer,
        JobCategory $category,
        string $title,
        int $minimum,
        int $maximum,
        string $status = 'published',
        string $currency = 'MMK',
        string $experience = 'mid',
        string $location = 'Yangon',
    ): Job {
        return Job::create([
            'employer_id' => $employer->id,
            'category_id' => $category->id,
            'category' => $category->name,
            'title' => $title,
            'description' => 'A role used to test salary insights.',
            'location' => $location,
            'employment_type' => 'full_time',
            'experience_level' => $experience,
            'salary_min' => $minimum,
            'salary_max' => $maximum,
            'salary_currency' => $currency,
            'status' => $status,
            'posted_at' => now(),
        ]);
    }
}
