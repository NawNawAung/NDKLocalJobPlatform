<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->boolean('profile_searchable')->default(false)->after('job_alerts_enabled');
        });

        Schema::create('pricing_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('plan_type', 24)->default('subscription')->index();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->char('currency', 3)->default('MMK');
            $table->string('billing_interval', 16)->default('month');
            $table->unsignedTinyInteger('interval_count')->default(1);
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('billing_products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('product_type', 32)->index();
            $table->unsignedBigInteger('price_amount');
            $table->char('currency', 3)->default('MMK');
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pricing_plan_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->json('feature_value');
            $table->timestamps();
            $table->unique(['pricing_plan_id', 'feature_key']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedBigInteger('price_amount');
            $table->char('currency', 3)->default('MMK');
            $table->string('billing_interval', 16)->default('month');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['employer_id', 'status']);
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 40)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('employer_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 24)->default('pending_payment')->index();
            $table->char('currency', 3)->default('MMK');
            $table->unsignedBigInteger('subtotal_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->json('billing_snapshot')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pricing_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('billing_product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('job_id')->nullable()->constrained('job_listings')->nullOnDelete();
            $table->string('item_type', 32);
            $table->string('item_name');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedBigInteger('unit_amount');
            $table->unsignedBigInteger('total_amount');
            $table->json('item_snapshot')->nullable();
            $table->timestamps();
        });

        Schema::create('subscription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pricing_plan_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('price_amount');
            $table->char('currency', 3)->default('MMK');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 24)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('payment_method', 32);
            $table->string('payment_provider', 80)->nullable();
            $table->string('gateway_transaction_id', 160)->nullable()->unique();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('MMK');
            $table->string('reference_number', 120)->nullable();
            $table->string('proof_path')->nullable();
            $table->text('failure_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'created_at']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 40)->unique();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('issued')->index();
            $table->char('currency', 3)->default('MMK');
            $table->unsignedBigInteger('subtotal_amount');
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->unsignedBigInteger('total_amount');
            $table->json('bill_to_snapshot')->nullable();
            $table->timestamp('issued_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('amount');
            $table->char('currency', 3)->default('MMK');
            $table->string('status', 24)->default('pending')->index();
            $table->text('reason');
            $table->string('reference_number', 120)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('feature_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key', 80)->index();
            $table->string('resource_type', 40)->nullable();
            $table->unsignedBigInteger('resource_id')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('period_start')->index();
            $table->timestamp('period_end')->nullable();
            $table->json('context')->nullable();
            $table->timestamps();
            $table->index(['employer_id', 'feature_key', 'period_start']);
        });

        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('discount_type', 16);
            $table->unsignedInteger('discount_value');
            $table->char('currency', 3)->default('MMK');
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->unsignedInteger('redemptions_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('promotion_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->restrictOnDelete();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('discount_amount');
            $table->timestamp('redeemed_at');
            $table->timestamps();
            $table->unique(['promotion_id', 'user_id']);
        });

        Schema::create('job_promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promotion_type', 32)->index();
            $table->string('status', 24)->default('scheduled')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['job_id', 'status', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_promotions');
        Schema::dropIfExists('promotion_redemptions');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('feature_usages');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscription_items');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('billing_products');
        Schema::dropIfExists('pricing_plans');
        Schema::table('job_seekers', function (Blueprint $table) {
            $table->dropColumn('profile_searchable');
        });
    }
};
