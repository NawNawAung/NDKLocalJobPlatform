<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $builtIns = [
        'hard' => [
            'Microsoft Excel', 'Microsoft Word', 'Google Workspace', 'Data Analysis', 'Python',
            'JavaScript', 'Vue.js', 'Laravel', 'SQL', 'Project Management', 'Accounting',
            'Customer Service', 'Digital Marketing', 'Graphic Design', 'Network Administration',
            'AutoCAD', 'Sales', 'Human Resources', 'Supply Chain Management',
        ],
        'soft' => [
            'Communication', 'Teamwork', 'Leadership', 'Problem Solving', 'Time Management',
            'Adaptability', 'Critical Thinking', 'Collaboration', 'Creativity',
            'Attention to Detail', 'Conflict Resolution', 'Work Ethic', 'Customer Focus',
            'Decision Making', 'Organisation',
        ],
    ];

    public function up(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->dropUnique('skills_type_name_unique');
            $table->foreignId('created_by_job_seeker_id')->nullable()->after('type')->constrained('job_seekers')->cascadeOnDelete();
            $table->boolean('is_builtin')->default(false)->after('created_by_job_seeker_id')->index();
            $table->index(['type', 'name', 'created_by_job_seeker_id'], 'skills_type_name_owner_index');
        });

        $builtInLookup = collect($this->builtIns)->map(fn (array $names) => array_fill_keys(array_map('mb_strtolower', $names), true));

        DB::table('skills')->orderBy('id')->get()->each(function ($skill) use ($builtInLookup) {
            $isBuiltIn = isset($builtInLookup[$skill->type][mb_strtolower($skill->name)]);
            if ($isBuiltIn) {
                DB::table('skills')->where('id', $skill->id)->update([
                    'is_builtin' => true,
                    'created_by_job_seeker_id' => null,
                ]);

                return;
            }

            $owners = DB::table('job_seeker_skill')
                ->where('skill_id', $skill->id)
                ->orderBy('job_seeker_id')
                ->pluck('job_seeker_id');

            if ($owners->isEmpty()) {
                // Old unassigned custom entries have no reliable creator to scope them to.
                DB::table('skills')->where('id', $skill->id)->update(['is_active' => false]);

                return;
            }

            $firstOwner = $owners->shift();
            DB::table('skills')->where('id', $skill->id)->update([
                'created_by_job_seeker_id' => $firstOwner,
                'is_builtin' => false,
            ]);

            foreach ($owners as $ownerId) {
                $copyId = DB::table('skills')->insertGetId([
                    'name' => $skill->name,
                    'type' => $skill->type,
                    'created_by_job_seeker_id' => $ownerId,
                    'is_builtin' => false,
                    'is_active' => $skill->is_active,
                    'created_at' => $skill->created_at,
                    'updated_at' => now(),
                ]);
                DB::table('job_seeker_skill')->where('job_seeker_id', $ownerId)->where('skill_id', $skill->id)->delete();
                DB::table('job_seeker_skill')->insert([
                    'job_seeker_id' => $ownerId,
                    'skill_id' => $copyId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('skills')->orderBy('id')->get()->groupBy(fn ($skill) => $skill->type.'|'.mb_strtolower($skill->name))->each(function ($skills) {
            $canonical = $skills->first();
            foreach ($skills->skip(1) as $duplicate) {
                $jobSeekerIds = DB::table('job_seeker_skill')->where('skill_id', $duplicate->id)->pluck('job_seeker_id');
                foreach ($jobSeekerIds as $jobSeekerId) {
                    DB::table('job_seeker_skill')->insertOrIgnore([
                        'job_seeker_id' => $jobSeekerId,
                        'skill_id' => $canonical->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
                DB::table('job_seeker_skill')->where('skill_id', $duplicate->id)->delete();
                DB::table('skills')->where('id', $duplicate->id)->delete();
            }
        });

        Schema::table('skills', function (Blueprint $table) {
            $table->dropIndex('skills_type_name_owner_index');
            $table->dropIndex('skills_is_builtin_index');
            $table->dropForeign(['created_by_job_seeker_id']);
            $table->dropColumn(['created_by_job_seeker_id', 'is_builtin']);
            $table->unique(['type', 'name']);
        });
    }
};
