<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('ad_campaigns')) {
            return;
        }

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->nullable()->constrained()->nullOnDelete();
            $table->string('campaign_type', 32)->index();
            $table->string('placement', 64)->index();
            $table->string('status', 24)->default('scheduled')->index();
            $table->timestamp('starts_at')->nullable();
            $table->dateTime('ends_at')->index();
            $table->unsignedInteger('impression_limit')->nullable();
            $table->unsignedInteger('impressions_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['placement', 'status', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
