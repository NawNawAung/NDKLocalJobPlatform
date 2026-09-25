<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->string('experience_level', 24)->nullable()->index()->after('employment_type');
            $table->string('work_mode', 24)->nullable()->index()->after('experience_level');
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['experience_level', 'work_mode']);
        });
    }
};
