<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('type', 16)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['type', 'name']);
        });

        Schema::create('job_seeker_skill', function (Blueprint $table) {
            $table->foreignId('job_seeker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['job_seeker_id', 'skill_id']);
        });

        $softSkillNames = [
            'communication', 'teamwork', 'leadership', 'problem solving', 'time management',
            'adaptability', 'critical thinking', 'creativity', 'collaboration', 'work ethic',
            'conflict resolution', 'emotional intelligence', 'attention to detail',
        ];

        DB::table('job_seekers')->select(['id', 'skills'])->orderBy('id')->each(function ($seeker) use ($softSkillNames) {
            $names = json_decode($seeker->skills ?? '[]', true);
            if (! is_array($names)) {
                return;
            }

            foreach (array_unique(array_filter(array_map('trim', $names))) as $name) {
                $type = in_array(mb_strtolower($name), $softSkillNames, true) ? 'soft' : 'hard';
                $now = now();
                $skillId = DB::table('skills')->where('type', $type)->where('name', $name)->value('id');
                if (! $skillId) {
                    $skillId = DB::table('skills')->insertGetId([
                        'name' => $name,
                        'type' => $type,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('job_seeker_skill')->insertOrIgnore([
                    'job_seeker_id' => $seeker->id,
                    'skill_id' => $skillId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        });

        $starterSkills = [
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

        foreach ($starterSkills as $type => $names) {
            foreach ($names as $name) {
                DB::table('skills')->insertOrIgnore([
                    'name' => $name,
                    'type' => $type,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_seeker_skill');
        Schema::dropIfExists('skills');
    }
};
