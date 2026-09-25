<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->string('phone', 40)->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->string('cv_original_name')->nullable();
            $table->string('professional_title', 160)->nullable();
            $table->unsignedTinyInteger('years_experience')->nullable();
            $table->string('desired_job_title', 160)->nullable();
            $table->string('employment_type', 32)->nullable();
            $table->string('work_mode', 32)->nullable();
            $table->unsignedBigInteger('expected_salary_min')->nullable();
            $table->unsignedBigInteger('expected_salary_max')->nullable();
            $table->string('availability', 32)->nullable();
        });

        Schema::create('job_seeker_experiences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_seeker_id')->constrained()->cascadeOnDelete();
            $table->string('job_title', 160);
            $table->string('employer_name', 160);
            $table->string('location', 160)->nullable();
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->boolean('is_current')->default(false);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['job_seeker_id', 'sort_order']);
        });

        Schema::create('job_seeker_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_seeker_id')->constrained()->cascadeOnDelete();
            $table->string('institution', 180);
            $table->string('qualification', 160)->nullable();
            $table->string('field_of_study', 160)->nullable();
            $table->unsignedSmallInteger('started_year')->nullable();
            $table->unsignedSmallInteger('graduated_year')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['job_seeker_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_seeker_educations');
        Schema::dropIfExists('job_seeker_experiences');
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn([
                'phone', 'profile_photo_path', 'cv_original_name', 'professional_title', 'years_experience',
                'desired_job_title', 'employment_type', 'work_mode', 'expected_salary_min',
                'expected_salary_max', 'availability',
            ]);
        });
    }
};
