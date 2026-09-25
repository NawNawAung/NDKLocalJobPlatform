<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('type', 24); // region, state, or union_territory
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('townships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_featured')->default(false);
            $table->timestamps();
            $table->unique(['region_id', 'name']);
            $table->index(['region_id', 'is_featured']);
        });

        Schema::create('featured_cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('highlight')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['region_id', 'name']);
        });

        Schema::table('job_seekers', function (Blueprint $table) {
            $table->foreignId('township_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('employers', function (Blueprint $table) {
            $table->foreignId('township_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        Schema::table('job_listings', function (Blueprint $table) {
            $table->foreignId('township_id')->nullable()->after('employer_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', fn (Blueprint $table) => $table->dropConstrainedForeignId('township_id'));
        Schema::table('employers', fn (Blueprint $table) => $table->dropConstrainedForeignId('township_id'));
        Schema::table('job_seekers', fn (Blueprint $table) => $table->dropConstrainedForeignId('township_id'));
        Schema::dropIfExists('featured_cities');
        Schema::dropIfExists('townships');
        Schema::dropIfExists('regions');
    }
};
