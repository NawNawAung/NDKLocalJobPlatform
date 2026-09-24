<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('status')->default(true)->after('password');
            $table->string('role', 32)->default('job_seeker')->index()->after('status');
        });

        Schema::create('employers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('company_name');
            $table->text('company_description')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->timestamps();
        });

        Schema::create('job_seekers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('cv_path')->nullable();
            $table->json('skills')->nullable();
            $table->json('languages')->nullable();
            $table->boolean('status')->default(true);
            $table->text('bio')->nullable();
            $table->timestamps();
        });

        // Laravel already uses `jobs` for its database queue, so job postings
        // live in `job_listings` to keep queue infrastructure intact.
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('description');
            $table->longText('requirements')->nullable();
            $table->string('location')->index();
            $table->string('category', 100)->index();
            $table->string('employment_type', 32)->default('full_time');
            $table->unsignedBigInteger('salary_min')->nullable();
            $table->unsignedBigInteger('salary_max')->nullable();
            $table->char('salary_currency', 3)->default('MMK');
            $table->string('status', 24)->default('draft')->index();
            $table->timestamp('posted_at')->nullable()->index();
            $table->timestamp('application_deadline')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_listings')->restrictOnDelete();
            $table->foreignId('job_seeker_id')->constrained()->restrictOnDelete();
            $table->text('cover_letter')->nullable();
            $table->string('cv_path')->nullable();
            $table->string('status', 24)->default('submitted')->index();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
            $table->index(['job_id', 'job_seeker_id']);
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('job_seeker_id')->constrained()->cascadeOnDelete();
            $table->timestamp('saved_at')->useCurrent();
            $table->timestamps();
            $table->unique(['job_id', 'job_seeker_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('message');
            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->restrictOnDelete();
            $table->timestamp('interview_at')->index();
            $table->string('interview_type', 24)->default('online');
            $table->string('status', 24)->default('scheduled')->index();
            $table->string('meeting_url')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('job_listings');
        Schema::dropIfExists('job_seekers');
        Schema::dropIfExists('employers');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['status', 'role']);
        });
    }
};
