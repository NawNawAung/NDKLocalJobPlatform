<?php

namespace Database\Seeders;

use App\Models\Employer;
use App\Models\Job;
use App\Models\JobCategory;
use App\Models\Region;
use App\Models\Township;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DemoMarketplaceSeeder extends Seeder
{
    private const DEMO_MARKER = '[FICTIONAL DEMO DATA]';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Fictional demo data must not be seeded into a production environment.');
        }

        DB::transaction(function () {
            $profiles = $this->companies();
            $categories = $this->categories();

            foreach ($profiles as $profile) {
                $employer = $this->employerFor($profile);

                foreach ($profile['jobs'] as $index => $definition) {
                    $this->jobFor($employer, $profile, $definition, $index, $categories);
                }
            }
        });

        $this->command?->info('Fictional demo marketplace data is ready. No existing records were overwritten.');
    }

    private function employerFor(array $profile): Employer
    {
        $email = 'demo+'.Str::slug($profile['name']).'@example.test';
        $employer = Employer::query()->where('company_name', $profile['name'])->first();

        if ($employer) {
            $user = User::find($employer->user_id);
            if ($user?->email !== $email || ! str_starts_with((string) $employer->company_description, self::DEMO_MARKER)) {
                throw new RuntimeException("A non-demo employer already uses the demo company name [{$profile['name']}]. No records were changed.");
            }

            return $employer;
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new RuntimeException("The reserved demo contact email [{$email}] already belongs to another account. No records were changed.");
        }

        $region = Region::query()->where('name', $profile['region'])->first();
        $township = $region
            ? Township::query()->where('region_id', $region->id)->where('name', $profile['township'])->first()
            : null;
        $user = User::create([
            'name' => $profile['name'].' Demo Contact',
            'email' => $email,
            // A throwaway random password satisfies the required account FK.
            // No demo login credential is created or displayed by this seeder.
            'password' => Str::random(64),
            'role' => 'employer',
            'status' => true,
        ]);

        $status = $profile['verification_status'];

        return Employer::create([
            'user_id' => $user->id,
            'region_id' => $region?->id,
            'township_id' => $township?->id,
            'company_name' => $profile['name'],
            'company_description' => self::DEMO_MARKER.' Fictional Myanmar '.$profile['industry'].' company. '.$profile['description'],
            'location' => $profile['city'].', Myanmar',
            'website_url' => 'https://'.Str::slug($profile['name']).'.example',
            'social_links' => null,
            'is_verified' => $status === 'verified',
            'verification_status' => $status,
            'verification_notes' => in_array($status, ['pending', 'rejected'], true) ? self::DEMO_MARKER.' Example moderation state for coursework demonstration.' : null,
            'verification_requested_at' => in_array($status, ['pending', 'rejected'], true) ? now()->subDays(2) : null,
            'verification_reviewed_at' => in_array($status, ['verified', 'rejected'], true) ? now()->subDay() : null,
            'verification_reviewed_by' => null,
        ]);
    }

    private function jobFor(Employer $employer, array $profile, array $definition, int $index, array $categories): Job
    {
        $role = $this->roles()[$definition['role']];
        $title = $role['title'];
        $job = Job::query()->where('employer_id', $employer->id)->where('title', $title)->first();

        if ($job) {
            if (! str_starts_with((string) $job->description, self::DEMO_MARKER)) {
                throw new RuntimeException("A non-demo job already uses [{$title}] for [{$employer->company_name}]. No records were changed.");
            }

            return $job;
        }

        $category = $categories[$role['category']];
        $township = $role['work_mode'] === 'remote'
            ? null
            : Township::query()
                ->where('name', $profile['township'])
                ->whereHas('region', fn ($query) => $query->where('name', $profile['region']))
                ->first();
        $status = $definition['status'];
        $salary = array_key_exists('salary', $definition) ? $definition['salary'] : $role['salary'];
        $postedAt = $status === 'draft' ? null : now()->subDays(($index + 1) * 3);

        try {
            return Job::create([
                'employer_id' => $employer->id,
                'township_id' => $township?->id,
                'category_id' => $category->id,
                'title' => $title,
                'description' => self::DEMO_MARKER.' Fictional demonstration vacancy at '.$profile['name'].'. '.$role['description'],
                'requirements' => $role['requirements'],
                'location' => $role['work_mode'] === 'remote' ? 'Remote, Myanmar' : $profile['city'].', Myanmar',
                'category' => $category->name,
                'employment_type' => $definition['employment_type'] ?? $role['employment_type'],
                'experience_level' => $role['experience_level'],
                'work_mode' => $role['work_mode'],
                'salary_min' => $salary[0] ?? null,
                'salary_max' => $salary[1] ?? null,
                'salary_currency' => 'MMK',
                'status' => $status,
                'posted_at' => $postedAt,
                'application_deadline' => now()->addDays(25 + $index * 5),
            ]);
        } catch (QueryException $exception) {
            throw new RuntimeException("Could not create the demo job [{$title}]. The demo transaction was cancelled.", previous: $exception);
        }
    }

    private function categories(): array
    {
        $names = collect($this->roles())->pluck('category')->unique()->values();
        $categories = [];

        foreach ($names as $index => $name) {
            $category = JobCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if ($category && ! $category->is_active) {
                $name .= ' (Demo)';
                $category = JobCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            }

            if (! $category) {
                $baseSlug = Str::slug($name) ?: 'demo-category';
                $slug = $baseSlug;
                $suffix = 2;
                while (JobCategory::query()->where('slug', $slug)->exists()) {
                    $slug = $baseSlug.'-'.$suffix++;
                }

                $category = JobCategory::create([
                    'name' => $name,
                    'slug' => $slug,
                    'is_active' => true,
                    'sort_order' => 50000 + $index,
                ]);
            }

            $categories[$name] = $category;
            $categories[$this->originalCategoryName($name)] ??= $category;
        }

        return $categories;
    }

    private function originalCategoryName(string $name): string
    {
        return str_ends_with($name, ' (Demo)') ? mb_substr($name, 0, -7) : $name;
    }

    private function companies(): array
    {
        return [
            ['name' => 'Irrawaddy Digital Labs (Demo)', 'industry' => 'Information Technology and Software Development', 'city' => 'Yangon', 'region' => 'Yangon', 'township' => 'Kamayut', 'verification_status' => 'verified', 'description' => 'Builds custom business software and digital products for local organisations.', 'jobs' => [['role' => 'junior_software', 'status' => 'published'], ['role' => 'senior_software', 'status' => 'paused'], ['role' => 'ui_ux', 'status' => 'draft', 'employment_type' => 'internship'], ['role' => 'business_analyst', 'status' => 'closed', 'employment_type' => 'contract'], ['role' => 'data_analyst', 'status' => 'closed']]],
            ['name' => 'Thazin Financial Services (Demo)', 'industry' => 'Banking and Finance', 'city' => 'Yangon', 'region' => 'Yangon', 'township' => 'Kyauktada', 'verification_status' => 'pending', 'description' => 'Provides fictional retail finance, payment support, and bookkeeping services.', 'jobs' => [['role' => 'accountant', 'status' => 'published'], ['role' => 'business_analyst', 'status' => 'paused'], ['role' => 'customer_service', 'status' => 'draft', 'employment_type' => 'temporary'], ['role' => 'sales_rep', 'status' => 'closed', 'employment_type' => 'part_time'], ['role' => 'hr_officer', 'status' => 'closed']]],
            ['name' => 'Golden Signal Networks (Demo)', 'industry' => 'Telecommunications', 'city' => 'Mandalay', 'region' => 'Mandalay', 'township' => 'Chanayethazan', 'verification_status' => 'verified', 'description' => 'Plans and maintains fictional connectivity and network support services.', 'jobs' => [['role' => 'network_engineer', 'status' => 'published'], ['role' => 'customer_service', 'status' => 'paused'], ['role' => 'sales_rep', 'status' => 'draft', 'employment_type' => 'part_time'], ['role' => 'project_manager', 'status' => 'closed'], ['role' => 'data_analyst', 'status' => 'closed']]],
            ['name' => 'Lotus Cart Myanmar (Demo)', 'industry' => 'Retail and E-commerce', 'city' => 'Yangon', 'region' => 'Yangon', 'township' => 'Thingangyun', 'verification_status' => 'unverified', 'description' => 'Operates a fictional online retail service with local fulfilment teams.', 'jobs' => [['role' => 'marketing_exec', 'status' => 'published', 'salary' => null], ['role' => 'sales_rep', 'status' => 'paused', 'employment_type' => 'part_time'], ['role' => 'customer_service', 'status' => 'draft', 'salary' => null, 'employment_type' => 'temporary'], ['role' => 'accountant', 'status' => 'closed'], ['role' => 'operations_officer', 'status' => 'closed']]],
            ['name' => 'Ayeyar Manufacturing Works (Demo)', 'industry' => 'Manufacturing', 'city' => 'Mandalay', 'region' => 'Mandalay', 'township' => 'Chanmyathazi', 'verification_status' => 'verified', 'description' => 'Produces fictional household and packaging goods for regional distribution.', 'jobs' => [['role' => 'operations_officer', 'status' => 'published'], ['role' => 'quality_technician', 'status' => 'paused', 'employment_type' => 'contract'], ['role' => 'accountant', 'status' => 'draft'], ['role' => 'hr_officer', 'status' => 'closed'], ['role' => 'project_manager', 'status' => 'closed']]],
            ['name' => 'Green Valley Health Services (Demo)', 'industry' => 'Healthcare', 'city' => 'Naypyidaw', 'region' => 'Nay Pyi Taw', 'township' => 'Zabuthiri', 'verification_status' => 'pending', 'description' => 'Coordinates fictional outpatient administration and community health programmes.', 'jobs' => [['role' => 'healthcare_operations', 'status' => 'published'], ['role' => 'customer_service', 'status' => 'paused'], ['role' => 'data_analyst', 'status' => 'draft', 'employment_type' => 'internship'], ['role' => 'accountant', 'status' => 'closed'], ['role' => 'hr_officer', 'status' => 'closed']]],
            ['name' => 'Bright Path Learning Group (Demo)', 'industry' => 'Education', 'city' => 'Mandalay', 'region' => 'Mandalay', 'township' => 'Aungmyethazan', 'verification_status' => 'verified', 'description' => 'Delivers fictional professional training and learning support programmes.', 'jobs' => [['role' => 'education_coordinator', 'status' => 'published'], ['role' => 'graphic_designer', 'status' => 'paused'], ['role' => 'marketing_exec', 'status' => 'draft', 'employment_type' => 'internship'], ['role' => 'customer_service', 'status' => 'closed'], ['role' => 'project_manager', 'status' => 'closed']]],
            ['name' => 'Rivergate Logistics (Demo)', 'industry' => 'Logistics and Transportation', 'city' => 'Yangon', 'region' => 'Yangon', 'township' => 'Insein', 'verification_status' => 'rejected', 'description' => 'Coordinates fictional warehouse, delivery, and regional transport operations.', 'jobs' => [['role' => 'logistics_coordinator', 'status' => 'published'], ['role' => 'operations_officer', 'status' => 'paused', 'employment_type' => 'temporary'], ['role' => 'data_analyst', 'status' => 'draft'], ['role' => 'sales_rep', 'status' => 'closed', 'employment_type' => 'part_time'], ['role' => 'accountant', 'status' => 'closed']]],
            ['name' => 'Blue Kite Creative Studio (Demo)', 'industry' => 'Marketing and Advertising', 'city' => 'Yangon', 'region' => 'Yangon', 'township' => 'Bahan', 'verification_status' => 'verified', 'description' => 'Creates fictional campaign, branding, and visual communication projects.', 'jobs' => [['role' => 'graphic_designer', 'status' => 'published'], ['role' => 'marketing_exec', 'status' => 'paused'], ['role' => 'sales_rep', 'status' => 'draft', 'employment_type' => 'part_time'], ['role' => 'ui_ux', 'status' => 'closed'], ['role' => 'business_analyst', 'status' => 'closed']]],
        ];
    }

    private function roles(): array
    {
        return [
            'junior_software' => ['title' => 'Junior Software Developer', 'category' => 'Technology & Software', 'experience_level' => 'junior', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'salary' => [600000, 900000], 'description' => 'Contribute to web application features, resolve defects, and take part in code reviews with a senior engineer.', 'requirements' => 'Required: basic PHP or JavaScript knowledge; familiarity with Git; clear communication. Burmese and English reading ability is useful.'],
            'senior_software' => ['title' => 'Senior Software Engineer', 'category' => 'Technology & Software', 'experience_level' => 'senior', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'salary' => [1800000, 2800000], 'description' => 'Lead implementation of reliable web services, review technical designs, and support mentoring across the engineering team.', 'requirements' => 'Required: substantial production software experience; PHP or JavaScript; API and relational database design; testing and code review experience.'],
            'ui_ux' => ['title' => 'UI/UX Designer', 'category' => 'Design & User Experience', 'experience_level' => 'mid', 'employment_type' => 'contract', 'work_mode' => 'hybrid', 'salary' => [800000, 1400000], 'description' => 'Create accessible interface flows, prototypes, and design specifications in collaboration with product and engineering colleagues.', 'requirements' => 'Required: portfolio of interface work; prototyping tools; responsive design principles; ability to explain design decisions.'],
            'business_analyst' => ['title' => 'Business Analyst', 'category' => 'Business Analysis', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [900000, 1500000], 'description' => 'Document business processes, clarify stakeholder needs, and translate findings into practical requirements and acceptance criteria.', 'requirements' => 'Required: requirements gathering; process mapping; spreadsheet skills; clear written communication; experience working with cross-functional teams.'],
            'project_manager' => ['title' => 'Project Manager', 'category' => 'Project Management', 'experience_level' => 'senior', 'employment_type' => 'contract', 'work_mode' => 'hybrid', 'salary' => [1500000, 2500000], 'description' => 'Coordinate scope, schedules, risks, and stakeholder communication for multi-team projects from planning through delivery.', 'requirements' => 'Required: project planning and reporting; risk tracking; stakeholder management; evidence of delivering projects to agreed milestones.'],
            'accountant' => ['title' => 'Accountant', 'category' => 'Finance & Accounting', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [650000, 1100000], 'description' => 'Maintain accurate ledgers, prepare reconciliations, support month-end reporting, and organise financial documentation.', 'requirements' => 'Required: accounting qualification or equivalent experience; reconciliation skills; spreadsheet proficiency; careful handling of confidential records.'],
            'marketing_exec' => ['title' => 'Marketing Executive', 'category' => 'Marketing & Advertising', 'experience_level' => 'junior', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'salary' => [550000, 950000], 'description' => 'Support campaign planning, prepare channel content, coordinate publication schedules, and report basic campaign results.', 'requirements' => 'Required: clear Burmese writing; basic digital marketing knowledge; organisation skills; familiarity with social content and spreadsheets.'],
            'sales_rep' => ['title' => 'Sales Representative', 'category' => 'Sales & Retail', 'experience_level' => 'entry', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [450000, 800000], 'description' => 'Respond to customer enquiries, explain relevant products or services, maintain accurate records, and follow up on qualified leads.', 'requirements' => 'Required: customer communication; reliable record keeping; willingness to meet clients when needed; sales experience is helpful but not essential.'],
            'hr_officer' => ['title' => 'Human Resources Officer', 'category' => 'Human Resources', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [650000, 1100000], 'description' => 'Support recruitment administration, maintain staff records, coordinate onboarding, and assist with policy and attendance processes.', 'requirements' => 'Required: HR administration experience; confidentiality; document management; professional communication and coordination skills.'],
            'customer_service' => ['title' => 'Customer Service Representative', 'category' => 'Customer Service', 'experience_level' => 'entry', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [400000, 650000], 'description' => 'Handle customer questions through approved service channels, record case details, and follow issues through to resolution.', 'requirements' => 'Required: patient communication; accurate data entry; Burmese fluency; basic computer skills. Shift availability may be required.'],
            'network_engineer' => ['title' => 'Network Engineer', 'category' => 'Telecommunications', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [900000, 1600000], 'description' => 'Assist with network deployment, monitor service health, investigate connectivity incidents, and maintain technical documentation.', 'requirements' => 'Required: networking fundamentals; familiarity with routing and switching; incident documentation; relevant certification or equivalent experience preferred.'],
            'data_analyst' => ['title' => 'Data Analyst', 'category' => 'Data & Analytics', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'salary' => [800000, 1400000], 'description' => 'Prepare clean datasets, build recurring reports, investigate trends, and present findings in a clear format for operational teams.', 'requirements' => 'Required: spreadsheet analysis; SQL fundamentals; attention to data quality; ability to explain findings to non-technical colleagues.'],
            'operations_officer' => ['title' => 'Operations Officer', 'category' => 'Operations', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [550000, 950000], 'description' => 'Coordinate daily operational tasks, monitor service or production records, and escalate delays or quality concerns to the relevant lead.', 'requirements' => 'Required: organisation and follow-through; accurate record keeping; spreadsheet skills; experience coordinating operational work is preferred.'],
            'quality_technician' => ['title' => 'Quality Assurance Technician', 'category' => 'Manufacturing & Quality', 'experience_level' => 'junior', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [550000, 900000], 'description' => 'Complete routine product checks, record inspection results, and report deviations using the company quality process.', 'requirements' => 'Required: careful observation; comfort following written procedures; accurate records; manufacturing or laboratory experience is an advantage.'],
            'healthcare_operations' => ['title' => 'Healthcare Operations Officer', 'category' => 'Healthcare Administration', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [650000, 1050000], 'description' => 'Coordinate appointment and facility administration, maintain service records, and help teams follow patient privacy procedures.', 'requirements' => 'Required: administration experience; confidentiality; organised record keeping; respectful communication. Clinical credentials are not required for this administrative role.'],
            'education_coordinator' => ['title' => 'Education Programme Coordinator', 'category' => 'Education & Training', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [600000, 1000000], 'description' => 'Coordinate class schedules, support instructors, communicate with learners, and maintain programme attendance and progress records.', 'requirements' => 'Required: coordination or education administration experience; clear communication; spreadsheet skills; interest in learner support.'],
            'graphic_designer' => ['title' => 'Graphic Designer', 'category' => 'Marketing & Advertising', 'experience_level' => 'mid', 'employment_type' => 'contract', 'work_mode' => 'remote', 'salary' => [650000, 1100000], 'description' => 'Develop campaign and brand assets for digital channels, prepare production-ready files, and incorporate structured feedback from clients.', 'requirements' => 'Required: portfolio of graphic design work; image and layout tools; typography fundamentals; ability to meet agreed project deadlines.'],
            'logistics_coordinator' => ['title' => 'Logistics Coordinator', 'category' => 'Logistics & Transportation', 'experience_level' => 'mid', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'salary' => [650000, 1100000], 'description' => 'Coordinate dispatch schedules, confirm delivery documentation, communicate with warehouse teams, and track operational exceptions.', 'requirements' => 'Required: coordination and record keeping; spreadsheet proficiency; clear communication; logistics experience is preferred.'],
        ];
    }
}
