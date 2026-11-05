<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\User;
use Database\Seeders\DemoMarketplaceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class DemoMarketplaceSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_marketplace_seeder_creates_realistic_relationships_and_is_idempotent(): void
    {
        $this->seed(DemoMarketplaceSeeder::class);

        $this->assertSame(9, Employer::query()->where('company_name', 'like', '%(Demo)')->count());
        $this->assertSame(45, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->count());
        $this->assertSame(9, User::query()->where('role', 'employer')->where('email', 'like', 'demo+%@example.test')->count());
        $this->assertSame(9, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->where('status', 'published')->count());
        $this->assertSame(9, Employer::query()->where('company_name', 'like', '%(Demo)')->get()->sum(fn (Employer $employer) => $employer->jobs()->where('status', 'published')->count()));
        $this->assertSame(1, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->where('status', 'published')->whereNull('salary_min')->whereNull('salary_max')->count());
        $this->assertEqualsCanonicalizing(
            ['contract', 'full_time', 'internship', 'part_time', 'temporary'],
            Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->distinct()->orderBy('employment_type')->pluck('employment_type')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['closed', 'draft', 'paused', 'published'],
            Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->distinct()->orderBy('status')->pluck('status')->all(),
        );

        $this->assertSame(0, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))
            ->where(fn ($query) => $query->whereNull('category_id')->orWhereNull('employer_id')->orWhereNull('application_deadline'))->count());
        $this->assertSame(0, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))
            ->whereNotNull('salary_min')->whereNotNull('salary_max')->whereColumn('salary_min', '>', 'salary_max')->count());
        $this->assertSame(0, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))
            ->whereDate('application_deadline', '<=', today())->count());

        $publishedJob = Job::query()
            ->where('title', 'Junior Software Developer')
            ->whereHas('employer', fn ($query) => $query->where('company_name', 'Irrawaddy Digital Labs (Demo)'))
            ->firstOrFail();
        $this->getJson('/api/jobs/search?keyword=Junior%20Software')
            ->assertOk()
            ->assertJsonPath('data.0.id', $publishedJob->id)
            ->assertJsonPath('data.0.salary_currency', 'MMK');
        $this->getJson('/api/jobs/search?category_id='.$publishedJob->category_id.'&experience_level=junior')
            ->assertOk()
            ->assertJsonPath('data.0.id', $publishedJob->id);
        $this->getJson('/api/jobs/'.$publishedJob->id)
            ->assertOk()
            ->assertJsonPath('job.title', 'Junior Software Developer');
        $this->getJson('/api/salary-insights?currency=MMK')
            ->assertOk()
            ->assertJsonPath('summary.sample_size', 8);

        $this->seed(DemoMarketplaceSeeder::class);

        $this->assertSame(9, Employer::query()->where('company_name', 'like', '%(Demo)')->count());
        $this->assertSame(45, Job::query()->whereHas('employer', fn ($query) => $query->where('company_name', 'like', '%(Demo)'))->count());
    }

    public function test_demo_marketplace_seeder_does_not_change_existing_employers_or_jobs(): void
    {
        $user = User::create([
            'name' => 'Existing Employer Contact',
            'email' => 'existing-employer@example.test',
            'password' => 'existing-password',
            'role' => 'employer',
            'status' => true,
        ]);
        $employer = Employer::create([
            'user_id' => $user->id,
            'company_name' => 'Existing Company',
            'company_description' => 'Existing company description.',
            'location' => 'Yangon, Myanmar',
            'verification_status' => 'unverified',
        ]);
        $category = JobCategory::create(['name' => 'Existing Category', 'slug' => 'existing-category', 'is_active' => true]);
        $job = Job::create([
            'employer_id' => $employer->id,
            'category_id' => $category->id,
            'category' => $category->name,
            'title' => 'Existing Listing',
            'description' => 'Existing listing description.',
            'location' => 'Yangon, Myanmar',
            'employment_type' => 'full_time',
            'status' => 'draft',
        ]);

        $this->seed(DemoMarketplaceSeeder::class);

        $this->assertDatabaseHas('employers', [
            'id' => $employer->id,
            'company_name' => 'Existing Company',
            'company_description' => 'Existing company description.',
            'verification_status' => 'unverified',
        ]);
        $this->assertDatabaseHas('job_listings', [
            'id' => $job->id,
            'title' => 'Existing Listing',
            'description' => 'Existing listing description.',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'email' => 'existing-employer@example.test']);
    }

    public function test_demo_marketplace_seeder_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('must not be seeded into a production environment');
        app(DemoMarketplaceSeeder::class)->run();
    }
}
