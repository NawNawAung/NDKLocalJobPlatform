<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->string('category', 32)->default('general')->index()->after('message');
            $table->string('action_url', 2048)->nullable()->after('category');
        });

        Schema::table('job_seekers', function (Blueprint $table) {
            $table->boolean('job_alerts_enabled')->default(true)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn('job_alerts_enabled');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex(['category']);
            $table->dropColumn(['category', 'action_url']);
        });
    }
};
