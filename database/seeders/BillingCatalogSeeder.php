<?php

namespace Database\Seeders;

use App\Models\BillingProduct;
use App\Models\PlanFeature;
use App\Models\PricingPlan;
use Illuminate\Database\Seeder;

class BillingCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['name' => 'Free', 'slug' => 'free', 'description' => 'A no-cost starting plan for small employers.', 'price_amount' => 0, 'billing_interval' => 'month', 'sort_order' => 1, 'features' => [
                'max_active_jobs' => 1, 'candidate_preview_limit' => 5, 'candidate_search' => false,
                'candidate_contact_view' => false, 'candidate_cv_downloads' => 0, 'analytics_level' => 'basic', 'included_featured_jobs' => 0,
            ]],
            ['name' => 'Professional', 'slug' => 'professional', 'description' => 'For recurring hiring teams. Example monthly price; adjust in the pricing_plans table before launch.', 'price_amount' => 150000, 'billing_interval' => 'month', 'sort_order' => 2, 'features' => [
                'max_active_jobs' => 20, 'candidate_preview_limit' => -1, 'candidate_search' => true,
                'candidate_contact_view' => true, 'candidate_cv_downloads' => 100, 'analytics_level' => 'advanced',
            ]],
            ['name' => 'Enterprise', 'slug' => 'enterprise', 'description' => 'Custom organization plan. Contact the platform operator for a quote.', 'price_amount' => 0, 'billing_interval' => 'custom', 'sort_order' => 3, 'features' => [
                'max_active_jobs' => -1, 'candidate_preview_limit' => -1, 'candidate_search' => true,
                'candidate_contact_view' => true, 'candidate_cv_downloads' => -1, 'analytics_level' => 'advanced',
            ]],
        ];

        foreach ($plans as $definition) {
            $features = $definition['features'];
            unset($definition['features']);
            $plan = PricingPlan::updateOrCreate(['slug' => $definition['slug']], [...$definition, 'plan_type' => 'subscription', 'currency' => 'MMK', 'interval_count' => 1, 'is_active' => true]);
            $plan->features()->whereNotIn('feature_key', array_keys($features))->delete();
            foreach ($features as $key => $value) {
                PlanFeature::updateOrCreate(['pricing_plan_id' => $plan->id, 'feature_key' => $key], ['feature_value' => $value]);
            }
        }

        BillingProduct::updateOrCreate(['slug' => 'featured-job-7-days'], [
            'name' => 'Featured job · 7 days',
            'description' => 'Feature one published job for seven days with a featured badge and elevated placement.',
            'product_type' => 'job_promotion',
            'price_amount' => 20000,
            'currency' => 'MMK',
            'duration_days' => 7,
            'metadata' => ['promotion_type' => 'featured'],
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }
}
