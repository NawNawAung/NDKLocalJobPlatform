<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_job_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('slots_total');
            $table->unsignedSmallInteger('slots_remaining');
            $table->unsignedSmallInteger('promotion_duration_days');
            // Use DATETIME for required application-supplied values to avoid
            // legacy MySQL TIMESTAMP implicit-default constraints.
            $table->dateTime('purchased_at');
            $table->dateTime('expires_at')->index();
            $table->timestamps();
            $table->index(['employer_id', 'expires_at']);
        });

        Schema::create('external_advertisements', function (Blueprint $table) {
            $table->id();
            $table->string('advertiser_name', 160);
            $table->string('advertiser_email', 191)->nullable();
            $table->string('advertiser_website', 2048)->nullable();
            $table->string('campaign_name', 160);
            $table->string('headline', 180);
            $table->string('banner_path', 255);
            $table->string('destination_url', 2048);
            $table->string('placement', 48)->index();
            $table->string('status', 24)->default('paused')->index();
            $table->timestamp('starts_at')->nullable();
            $table->dateTime('ends_at')->index();
            $table->unsignedInteger('impression_limit')->nullable();
            $table->unsignedInteger('impressions_count')->default(0);
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->char('currency', 3)->default('MMK');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['placement', 'status', 'starts_at', 'ends_at'], 'external_ads_eligibility_idx');
        });

        Schema::create('external_ad_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('external_advertisement_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('MMK');
            $table->string('payment_method', 64)->default('offline_record');
            $table->string('reference_number', 100)->nullable()->index();
            $table->string('status', 24)->default('pending')->index();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['external_advertisement_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_ad_payments');
        Schema::dropIfExists('external_advertisements');
        Schema::dropIfExists('featured_job_packages');
    }
};
