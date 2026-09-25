<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->foreignId('region_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employers', fn (Blueprint $table) => $table->dropConstrainedForeignId('region_id'));
        Schema::table('job_seekers', fn (Blueprint $table) => $table->dropConstrainedForeignId('region_id'));
    }
};
