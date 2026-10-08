<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employers', function (Blueprint $table) {
            if (! Schema::hasColumn('employers', 'verification_status')) $table->string('verification_status', 24)->default('unverified')->index();
            if (! Schema::hasColumn('employers', 'verification_notes')) $table->text('verification_notes')->nullable();
            if (! Schema::hasColumn('employers', 'verification_requested_at')) $table->timestamp('verification_requested_at')->nullable();
            if (! Schema::hasColumn('employers', 'verification_reviewed_at')) $table->timestamp('verification_reviewed_at')->nullable();
            if (! Schema::hasColumn('employers', 'verification_reviewed_by')) $table->foreignId('verification_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
        });

        DB::table('employers')->where('is_verified', true)->update(['verification_status' => 'verified']);

        if (! Schema::hasTable('content_reports')) {
            Schema::create('content_reports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reported_by')->constrained('users')->cascadeOnDelete();
                $table->string('target_type', 24)->index();
                $table->unsignedBigInteger('target_id');
                $table->string('reason', 80);
                $table->text('details')->nullable();
                $table->string('status', 24)->default('open')->index();
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
                $table->index(['target_type', 'target_id']);
                $table->index(['status', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('content_reports');
        Schema::table('employers', function (Blueprint $table) {
            foreach (['verification_reviewed_by', 'verification_reviewed_at', 'verification_requested_at', 'verification_notes', 'verification_status'] as $column) {
                if (Schema::hasColumn('employers', $column)) {
                    if ($column === 'verification_reviewed_by') $table->dropConstrainedForeignId($column);
                    else $table->dropColumn($column);
                }
            }
        });
    }
};
