<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            if (! Schema::hasColumn('job_seekers', 'portfolio_url')) {
                $table->string('portfolio_url', 2048)->nullable();
            }
            if (! Schema::hasColumn('job_seekers', 'social_links')) {
                $table->json('social_links')->nullable();
            }
        });

        Schema::table('employers', function (Blueprint $table) {
            if (! Schema::hasColumn('employers', 'website_url')) {
                $table->string('website_url', 2048)->nullable();
            }
            if (! Schema::hasColumn('employers', 'social_links')) {
                $table->json('social_links')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            if (Schema::hasColumn('employers', 'social_links')) $table->dropColumn('social_links');
            if (Schema::hasColumn('employers', 'website_url')) $table->dropColumn('website_url');
        });

        Schema::table('job_seekers', function (Blueprint $table) {
            if (Schema::hasColumn('job_seekers', 'social_links')) $table->dropColumn('social_links');
            if (Schema::hasColumn('job_seekers', 'portfolio_url')) $table->dropColumn('portfolio_url');
        });
    }
};
