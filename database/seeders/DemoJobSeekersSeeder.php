<?php

namespace Database\Seeders;

use App\Models\JobSeeker;
use App\Models\Region;
use App\Models\Skill;
use App\Models\Township;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class DemoJobSeekersSeeder extends Seeder
{
    private const MARKER = '[FICTIONAL DEMO JOB SEEKER]';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('Fictional demo job seekers cannot be seeded in production.');
        }

        $profiles = $this->profiles();
        $emails = array_column($profiles, 'email');

        DB::transaction(function () use ($profiles, $emails): void {
            $existingUsers = User::query()->with('jobSeeker')->whereIn('email', $emails)->get()->keyBy('email');

            // Validate every reserved address before changing any of the demo records.
            foreach ($profiles as $profile) {
                $existing = $existingUsers->get($profile['email']);
                if ($existing && (
                    $existing->role !== 'job_seeker'
                    || ! $existing->jobSeeker
                    || ! str_starts_with((string) $existing->jobSeeker->bio, self::MARKER)
                )) {
                    throw new RuntimeException("Reserved demo email [{$profile['email']}] belongs to a non-demo account. No records were changed.");
                }

                $region = Region::query()->where('name', $profile['region'])->first();
                $township = $region
                    ? Township::query()->where('region_id', $region->id)->where('name', $profile['township'])->first()
                    : null;
                if (! $region || ! $township) {
                    throw new RuntimeException("Location [{$profile['township']}, {$profile['region']}] is missing. Run MyanmarLocationsSeeder before this seeder.");
                }

                $this->skillIds($profile['hard_skills'], 'hard');
                $this->skillIds($profile['soft_skills'], 'soft');
            }

            foreach ($profiles as $profile) {
                $user = $existingUsers->get($profile['email']);
                if (! $user) {
                    $user = User::create([
                        'name' => $profile['name'],
                        'email' => $profile['email'],
                        // These accounts are for display only; each receives an undisclosed random password.
                        'password' => Hash::make(Str::random(64)),
                        'role' => 'job_seeker',
                        'status' => true,
                    ]);
                } else {
                    $user->update([
                        'name' => $profile['name'],
                        'role' => 'job_seeker',
                        'status' => true,
                    ]);
                }

                $region = Region::query()->where('name', $profile['region'])->firstOrFail();
                $township = Township::query()->where('region_id', $region->id)->where('name', $profile['township'])->firstOrFail();
                $allSkills = [...$profile['hard_skills'], ...$profile['soft_skills']];
                $seeker = $user->jobSeeker()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'region_id' => $region->id,
                        'township_id' => $township->id,
                        'phone' => $profile['phone'],
                        'professional_title' => $profile['professional_title'],
                        'years_experience' => $profile['years_experience'],
                        'desired_job_title' => $profile['desired_job_title'],
                        'employment_type' => $profile['employment_type'],
                        'work_mode' => $profile['work_mode'],
                        'expected_salary_min' => $profile['expected_salary_min'],
                        'expected_salary_max' => $profile['expected_salary_max'],
                        'availability' => $profile['availability'],
                        'profile_searchable' => true,
                        'job_alerts_enabled' => true,
                        'status' => true,
                        'skills' => $allSkills,
                        'languages' => $profile['languages'],
                        'bio' => self::MARKER.' '.$profile['bio'],
                        // Photo and CV paths remain null; both are optional in the current schema.
                    ],
                );

                $skillIds = [
                    ...$this->skillIds($profile['hard_skills'], 'hard'),
                    ...$this->skillIds($profile['soft_skills'], 'soft'),
                ];
                $seeker->skillCatalog()->sync($skillIds);

                foreach ($profile['educations'] as $index => $education) {
                    $seeker->educations()->updateOrCreate(['sort_order' => $index + 1], $education);
                }

                foreach ($profile['experiences'] as $index => $experience) {
                    $seeker->experiences()->updateOrCreate(['sort_order' => $index + 1], $experience);
                }
            }
        });

        $count = User::query()->whereIn('email', $emails)->where('role', 'job_seeker')
            ->whereHas('jobSeeker', fn ($query) => $query->where('bio', 'like', self::MARKER.'%'))
            ->count();
        if ($count !== 10) {
            throw new RuntimeException("Expected exactly 10 fictional demo job seekers, found {$count}.");
        }

        $this->command?->info('Exactly 10 fictional demo job seeker accounts are present. Re-running this seeder updates these marked profiles without adding duplicates.');
    }

    /** @return list<int> */
    private function skillIds(array $names, string $type): array
    {
        $skills = Skill::query()
            ->where('type', $type)
            ->where('is_active', true)
            ->where('is_builtin', true)
            ->whereIn('name', $names)
            ->get(['id', 'name'])
            ->keyBy('name');

        $missing = array_values(array_diff($names, $skills->keys()->all()));
        if ($missing) {
            throw new RuntimeException('Required built-in demo skills are missing: '.implode(', ', $missing).'. Run the skills migrations before this seeder.');
        }

        return collect($names)->map(fn (string $name) => (int) $skills[$name]->id)->all();
    }

    private function profiles(): array
    {
        $summary = fn (string $text) => 'Fictional demonstration profile. '.$text;
        $experience = fn (string $title, string $company, string $location, string $start, string $end, string $description) => [
            'job_title' => $title,
            'employer_name' => '[Fictional Demo] '.$company,
            'location' => $location,
            'started_on' => $start,
            'ended_on' => $end,
            'is_current' => false,
            'description' => '[FICTIONAL DEMO EXPERIENCE] '.$description,
        ];
        $education = fn (string $institution, string $qualification, string $field, int $start, int $end, string $description) => [
            'institution' => '[Fictional Demo] '.$institution,
            'qualification' => $qualification,
            'field_of_study' => $field,
            'started_year' => $start,
            'graduated_year' => $end,
            'description' => '[FICTIONAL DEMO EDUCATION] '.$description,
        ];

        return [
            [
                'name' => '[DEMO] Thiri Aung', 'email' => 'demo.jobseeker.01@example.test', 'phone' => '09 0000 0001',
                'region' => 'Yangon', 'township' => 'Kamayut', 'professional_title' => 'Junior Web Developer', 'years_experience' => 2,
                'desired_job_title' => 'Laravel Developer', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'availability' => 'immediately',
                'expected_salary_min' => 600000, 'expected_salary_max' => 900000,
                'hard_skills' => ['PHP', 'Laravel', 'Vue.js', 'JavaScript', 'MySQL', 'Git', 'REST API Development'],
                'soft_skills' => ['Communication', 'Problem Solving', 'Teamwork'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Builds accessible web features and enjoys solving application problems with PHP, Laravel, Vue.js, and relational databases.'),
                'educations' => [$education('Yangon Digital Technology Institute', 'BSc', 'Computer Science', 2020, 2024, 'Fictional computing degree record.')],
                'experiences' => [$experience('Junior Web Developer', 'Cedar Byte Studio', 'Yangon', '2024-06-01', '2026-05-31', 'Fictional sample work maintaining internal web forms and API integrations.')],
            ],
            [
                'name' => '[DEMO] Min Zaw', 'email' => 'demo.jobseeker.02@example.test', 'phone' => '09 0000 0002',
                'region' => 'Mandalay', 'township' => 'Chanayethazan', 'professional_title' => 'Network Support Engineer', 'years_experience' => 5,
                'desired_job_title' => 'Network Engineer', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'availability' => 'two_weeks',
                'expected_salary_min' => 900000, 'expected_salary_max' => 1500000,
                'hard_skills' => ['Computer Networking', 'TCP/IP', 'Routing and Switching', 'Cisco Networking', 'Linux System Administration', 'Network Troubleshooting', 'Network Monitoring'],
                'soft_skills' => ['Problem Solving', 'Attention to Detail', 'Teamwork'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Supports stable network operations, documents incidents clearly, and works carefully through connectivity faults.'),
                'educations' => [$education('Mandalay Applied Systems College', 'Diploma', 'Networking and Systems Administration', 2017, 2019, 'Fictional technical diploma record.')],
                'experiences' => [$experience('Network Support Engineer', 'North Star Systems Lab', 'Mandalay', '2021-02-01', '2026-01-31', 'Fictional sample work monitoring office networks and documenting support incidents.')],
            ],
            [
                'name' => '[DEMO] Ei Phyu', 'email' => 'demo.jobseeker.03@example.test', 'phone' => '09 0000 0003',
                'region' => 'Yangon', 'township' => 'Bahan', 'professional_title' => 'Digital Marketing Executive', 'years_experience' => 3,
                'desired_job_title' => 'Digital Marketing Specialist', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'availability' => 'immediately',
                'expected_salary_min' => 650000, 'expected_salary_max' => 1000000,
                'hard_skills' => ['Digital Marketing', 'Social Media Management', 'Content Management', 'Data Analysis', 'Google Workspace', 'Microsoft Excel'],
                'soft_skills' => ['Creativity', 'Communication', 'Adaptability'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Plans digital content, reviews campaign results, and coordinates publication calendars for small teams.'),
                'educations' => [$education('Yangon School of Business Studies', 'BA', 'Marketing Communications', 2019, 2023, 'Fictional marketing qualification record.')],
                'experiences' => [$experience('Digital Marketing Executive', 'Golden Kite Media House', 'Yangon', '2023-08-01', '2026-07-31', 'Fictional sample work preparing social content calendars and summarising campaign engagement.')],
            ],
            [
                'name' => '[DEMO] Htet Naing', 'email' => 'demo.jobseeker.04@example.test', 'phone' => '09 0000 0004',
                'region' => 'Nay Pyi Taw', 'township' => 'Zabuthiri', 'professional_title' => 'Accountant', 'years_experience' => 6,
                'desired_job_title' => 'Senior Accountant', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'availability' => 'one_month',
                'expected_salary_min' => 900000, 'expected_salary_max' => 1400000,
                'hard_skills' => ['Accounting', 'Microsoft Excel', 'Financial Reporting', 'Budgeting', 'Data Entry', 'Microsoft Word'],
                'soft_skills' => ['Attention to Detail', 'Organisation', 'Work Ethic'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Focuses on accurate reconciliations, orderly financial records, and clear monthly reporting.'),
                'educations' => [$education('Naypyidaw College of Commerce', 'BCom', 'Accounting', 2016, 2020, 'Fictional commerce degree record.')],
                'experiences' => [$experience('Accounts Assistant', 'Riverbend Ledger Services', 'Nay Pyi Taw', '2020-09-01', '2023-12-31', 'Fictional sample work assisting with reconciliations and monthly statements.'), $experience('Accountant', 'Capital Orchard Trading (Demo)', 'Nay Pyi Taw', '2024-01-01', '2026-06-30', 'Fictional sample work preparing ledger summaries and supporting month-end close.')],
            ],
            [
                'name' => '[DEMO] May Khaing', 'email' => 'demo.jobseeker.05@example.test', 'phone' => '09 0000 0005',
                'region' => 'Yangon', 'township' => 'Thingangyun', 'professional_title' => 'Data Analyst', 'years_experience' => 3,
                'desired_job_title' => 'Business Data Analyst', 'employment_type' => 'full_time', 'work_mode' => 'remote', 'availability' => 'immediately',
                'expected_salary_min' => 800000, 'expected_salary_max' => 1300000,
                'hard_skills' => ['SQL', 'Python', 'Microsoft Power BI', 'Data Analysis', 'Data Visualisation', 'Microsoft Excel'],
                'soft_skills' => ['Critical Thinking', 'Communication', 'Attention to Detail'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Turns structured data into understandable reports and checks source quality before sharing conclusions.'),
                'educations' => [$education('Yangon Institute for Digital Studies', 'BSc', 'Information Systems', 2019, 2023, 'Fictional information systems degree record.')],
                'experiences' => [$experience('Reporting Analyst', 'Blue Lantern Analytics Group', 'Yangon', '2023-07-01', '2026-06-30', 'Fictional sample work producing recurring operational reports and validating source spreadsheets.')],
            ],
            [
                'name' => '[DEMO] Nandar Win', 'email' => 'demo.jobseeker.06@example.test', 'phone' => '09 0000 0006',
                'region' => 'Mon', 'township' => 'Mawlamyine', 'professional_title' => 'Human Resources Officer', 'years_experience' => 4,
                'desired_job_title' => 'HR and Recruitment Officer', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'availability' => 'two_weeks',
                'expected_salary_min' => 700000, 'expected_salary_max' => 1100000,
                'hard_skills' => ['Human Resources', 'Microsoft Excel', 'Microsoft Office', 'Records Management', 'Business Writing', 'Google Workspace'],
                'soft_skills' => ['Communication', 'Conflict Resolution', 'Organisation'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Coordinates recruitment administration, maintains confidential records, and supports organised onboarding.'),
                'educations' => [$education('Mawlamyine Community Business College', 'BBA', 'Human Resource Management', 2018, 2022, 'Fictional business administration degree record.')],
                'experiences' => [$experience('HR Assistant', 'Mon State People Services (Demo)', 'Mawlamyine', '2022-08-01', '2026-05-31', 'Fictional sample work coordinating interview schedules and maintaining personnel documentation.')],
            ],
            [
                'name' => '[DEMO] Kyaw Zin', 'email' => 'demo.jobseeker.07@example.test', 'phone' => '09 0000 0007',
                'region' => 'Yangon', 'township' => 'Insein', 'professional_title' => 'Logistics Coordinator', 'years_experience' => 5,
                'desired_job_title' => 'Supply Chain Coordinator', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'availability' => 'one_month',
                'expected_salary_min' => 700000, 'expected_salary_max' => 1100000,
                'hard_skills' => ['Supply Chain Management', 'Inventory Management', 'Procurement', 'Microsoft Excel', 'Data Entry', 'Project Management'],
                'soft_skills' => ['Teamwork', 'Time Management', 'Problem Solving'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Coordinates dispatch and stock records, follows up on delivery exceptions, and keeps operational data current.'),
                'educations' => [$education('Yangon Institute of Logistics Studies', 'Diploma', 'Logistics and Supply Chain', 2017, 2019, 'Fictional logistics diploma record.')],
                'experiences' => [$experience('Warehouse Operations Assistant', 'South Gate Distribution Demo', 'Yangon', '2019-11-01', '2022-12-31', 'Fictional sample work tracking inventory movements and preparing dispatch paperwork.'), $experience('Logistics Coordinator', 'Delta Route Services (Fictional)', 'Yangon', '2023-01-01', '2026-06-30', 'Fictional sample work coordinating delivery schedules and recording operational exceptions.')],
            ],
            [
                'name' => '[DEMO] Su Mon', 'email' => 'demo.jobseeker.08@example.test', 'phone' => '09 0000 0008',
                'region' => 'Mandalay', 'township' => 'Aungmyethazan', 'professional_title' => 'Graphic and UI Designer', 'years_experience' => 2,
                'desired_job_title' => 'UI Designer', 'employment_type' => 'contract', 'work_mode' => 'remote', 'availability' => 'immediately',
                'expected_salary_min' => 650000, 'expected_salary_max' => 1050000,
                'hard_skills' => ['Figma', 'Adobe Photoshop', 'Adobe Illustrator', 'UI Design', 'UX Research', 'Prototyping', 'Wireframing'],
                'soft_skills' => ['Creativity', 'Collaboration', 'Adaptability'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Creates clear interface layouts and visual assets, using feedback to refine accessible digital experiences.'),
                'educations' => [$education('Mandalay Centre for Creative Arts', 'Diploma', 'Visual Communication Design', 2020, 2023, 'Fictional design diploma record.')],
                'experiences' => [$experience('Graphic Designer', 'Paper Lantern Design Room', 'Mandalay', '2024-02-01', '2026-01-31', 'Fictional sample work creating digital campaign assets and interface prototypes.')],
            ],
            [
                'name' => '[DEMO] Aung Myat', 'email' => 'demo.jobseeker.09@example.test', 'phone' => '09 0000 0009',
                'region' => 'Shan', 'township' => 'Taunggyi', 'professional_title' => 'IT Support Technician', 'years_experience' => 3,
                'desired_job_title' => 'IT Support Engineer', 'employment_type' => 'full_time', 'work_mode' => 'on_site', 'availability' => 'two_weeks',
                'expected_salary_min' => 650000, 'expected_salary_max' => 1000000,
                'hard_skills' => ['Technical Support', 'Computer Hardware Troubleshooting', 'PC Assembly and Repair', 'Windows Server Administration', 'Linux System Administration', 'Help Desk Support'],
                'soft_skills' => ['Customer Focus', 'Teamwork', 'Communication'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Provides practical desktop and account support, documents fixes, and communicates technical steps clearly to users.'),
                'educations' => [$education('Taunggyi Technical Learning Centre', 'Diploma', 'Computer Systems Support', 2019, 2022, 'Fictional technical support diploma record.')],
                'experiences' => [$experience('IT Support Technician', 'Highland Office Systems Demo', 'Taunggyi', '2023-03-01', '2026-02-28', 'Fictional sample work troubleshooting user devices and maintaining a small office help desk.')],
            ],
            [
                'name' => '[DEMO] Khin Sandar', 'email' => 'demo.jobseeker.10@example.test', 'phone' => '09 0000 0010',
                'region' => 'Ayeyarwady', 'township' => 'Pathein', 'professional_title' => 'Education Programme Coordinator', 'years_experience' => 4,
                'desired_job_title' => 'Training Coordinator', 'employment_type' => 'full_time', 'work_mode' => 'hybrid', 'availability' => 'immediately',
                'expected_salary_min' => 600000, 'expected_salary_max' => 950000,
                'hard_skills' => ['Project Management', 'Google Workspace', 'Microsoft Excel', 'Presentation Development', 'Report Writing', 'Records Management'],
                'soft_skills' => ['Communication', 'Organisation', 'Leadership'], 'languages' => ['Burmese', 'English'],
                'bio' => $summary('Coordinates class schedules and learner records, and supports instructors with clear communication and reporting.'),
                'educations' => [$education('Pathein Centre for Community Learning', 'BA', 'English and Education Studies', 2017, 2021, 'Fictional education degree record.')],
                'experiences' => [$experience('Programme Assistant', 'Open River Learning Demo', 'Pathein', '2021-09-01', '2024-12-31', 'Fictional sample work coordinating class materials and attendance records.'), $experience('Education Programme Coordinator', 'Coastal Skills Workshop (Fictional)', 'Pathein', '2025-01-01', '2026-06-30', 'Fictional sample work coordinating training schedules and learner communications.')],
            ],
        ];
    }
}
