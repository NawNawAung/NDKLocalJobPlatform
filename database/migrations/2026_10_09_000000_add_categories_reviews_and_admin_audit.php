<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('job_categories')) {
            Schema::create('job_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->string('slug', 120)->unique();
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedSmallInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('job_listings', 'category_id')) {
            Schema::table('job_listings', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('category')->constrained('job_categories')->restrictOnDelete();
            });
        }

        foreach (DB::table('job_listings')->whereNotNull('category')->distinct()->pluck('category') as $legacyName) {
            $name = trim((string) $legacyName);
            if ($name === '') continue;
            $category = DB::table('job_categories')->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            if (! $category) {
                $baseSlug = Str::slug($name) ?: 'category';
                $slug = $baseSlug;
                $suffix = 2;
                while (DB::table('job_categories')->where('slug', $slug)->exists()) $slug = $baseSlug.'-'.$suffix++;
                $id = DB::table('job_categories')->insertGetId(['name' => $name, 'slug' => $slug, 'is_active' => true, 'sort_order' => 0, 'created_at' => now(), 'updated_at' => now()]);
                $category = (object) ['id' => $id];
            }
            DB::table('job_listings')->whereRaw('LOWER(category) = ?', [mb_strtolower($name)])->update(['category_id' => $category->id]);
        }

        $uncategorized = DB::table('job_listings')->whereNull('category')->orWhere('category', '')->exists();
        if ($uncategorized) {
            $name = 'Uncategorized (Legacy)';
            $category = DB::table('job_categories')->where('name', $name)->first();
            if (! $category) {
                $slug = 'uncategorized-legacy';
                $suffix = 2;
                while (DB::table('job_categories')->where('slug', $slug)->exists()) $slug = 'uncategorized-legacy-'.$suffix++;
                $id = DB::table('job_categories')->insertGetId(['name' => $name, 'slug' => $slug, 'is_active' => true, 'sort_order' => 65535, 'created_at' => now(), 'updated_at' => now()]);
                $category = (object) ['id' => $id];
            }
            DB::table('job_listings')->where(fn ($query) => $query->whereNull('category')->orWhere('category', ''))->update(['category_id' => $category->id]);
            DB::table('job_listings')->where('category_id', $category->id)->whereNull('category')->update(['category' => $name]);
        }

        if (! Schema::hasTable('employer_reviews')) {
            Schema::create('employer_reviews', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('job_seeker_id')->constrained()->cascadeOnDelete();
                $table->foreignId('application_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedTinyInteger('rating');
                $table->string('title', 160)->nullable();
                $table->text('review');
                $table->string('status', 24)->default('pending')->index();
                $table->text('moderation_notes')->nullable();
                $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('moderated_at')->nullable();
                $table->timestamps();
                $table->unique(['employer_id', 'job_seeker_id']);
                $table->index(['employer_id', 'status', 'created_at']);
            });
        }

        if (! Schema::hasTable('admin_audit_logs')) {
            Schema::create('admin_audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('administrator_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 100)->index();
                $table->string('target_type', 100)->index();
                $table->unsignedBigInteger('target_id')->nullable()->index();
                $table->json('before')->nullable();
                $table->json('after')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->timestamp('created_at')->useCurrent()->index();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('employer_reviews');
        if (Schema::hasColumn('job_listings', 'category_id')) {
            Schema::table('job_listings', fn (Blueprint $table) => $table->dropConstrainedForeignId('category_id'));
        }
        Schema::dropIfExists('job_categories');
    }
};
