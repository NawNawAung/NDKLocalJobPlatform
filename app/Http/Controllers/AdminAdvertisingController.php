<?php

namespace App\Http\Controllers;

use App\Models\AdCampaign;
use App\Models\BillingProduct;
use App\Models\JobPromotion;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminAdvertisingController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    private function authorizeAdmin(Request $request): User
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status, 403);

        return $request->user();
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'scheduled', 'paused', 'cancelled'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $jobPromotions = DB::table('job_promotions as campaigns')
            ->join('employers', 'employers.id', '=', 'campaigns.employer_id')
            ->join('job_listings', 'job_listings.id', '=', 'campaigns.job_id')
            ->selectRaw("'job' as campaign_kind, campaigns.id, campaigns.promotion_type as campaign_type, 'home_featured_jobs' as placement, campaigns.status, campaigns.starts_at, campaigns.ends_at, NULL as impression_limit, 0 as impressions_count, employers.company_name, job_listings.title as target_label, campaigns.created_at")
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where(fn (Builder $match) => $match->where('employers.company_name', 'like', "%{$term}%")->orWhere('job_listings.title', 'like', "%{$term}%")))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('campaigns.status', $status));

        $companyCampaigns = DB::table('ad_campaigns as campaigns')
            ->join('employers', 'employers.id', '=', 'campaigns.employer_id')
            ->selectRaw("'company' as campaign_kind, campaigns.id, campaigns.campaign_type, campaigns.placement, campaigns.status, campaigns.starts_at, campaigns.ends_at, campaigns.impression_limit, campaigns.impressions_count, employers.company_name, employers.company_name as target_label, campaigns.created_at")
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where('employers.company_name', 'like', "%{$term}%"))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('campaigns.status', $status));

        $campaigns = DB::query()->fromSub($jobPromotions->unionAll($companyCampaigns), 'advertising_campaigns')
            ->orderByDesc('created_at')->paginate(20)->withQueryString();

        return response()->json([
            'campaigns' => $campaigns->through(fn ($campaign) => [
                'id' => $campaign->id,
                'kind' => $campaign->campaign_kind,
                'campaign_type' => $campaign->campaign_type,
                'placement' => $campaign->placement,
                'company' => $campaign->company_name,
                'target' => $campaign->target_label,
                'status' => $campaign->status,
                'starts_at' => $campaign->starts_at,
                'ends_at' => $campaign->ends_at,
                'impression_limit' => $campaign->impression_limit,
                'impressions_count' => $campaign->impressions_count,
                'created_at' => $campaign->created_at,
            ]),
            'company_products' => BillingProduct::query()->where('product_type', 'company_promotion')->orderBy('sort_order')->get()
                ->map(fn (BillingProduct $product) => [
                    'id' => $product->id, 'name' => $product->name, 'description' => $product->description,
                    'price_amount' => $product->price_amount, 'currency' => $product->currency,
                    'duration_days' => $product->duration_days, 'is_active' => $product->is_active,
                    'impression_limit' => data_get($product->metadata, 'impression_limit'),
                ]),
            'job_products' => BillingProduct::query()->where('product_type', 'job_promotion')->orderBy('sort_order')->get()
                ->map(fn (BillingProduct $product) => [
                    'id' => $product->id, 'name' => $product->name, 'description' => $product->description,
                    'price_amount' => $product->price_amount, 'currency' => $product->currency,
                    'duration_days' => $product->duration_days, 'is_active' => $product->is_active,
                    'slots' => data_get($product->metadata, 'slots', 1),
                    'package_validity_days' => data_get($product->metadata, 'package_validity_days', 90),
                ]),
        ]);
    }

    public function updateCampaign(Request $request, string $kind, int $campaignId): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        abort_unless(in_array($kind, ['job', 'company'], true), 404);
        $data = $request->validate([
            'status' => ['sometimes', 'required', Rule::in(['active', 'scheduled', 'paused', 'cancelled'])],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'required', 'date'],
            'impression_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000000'],
        ]);
        abort_if($kind === 'job' && array_key_exists('impression_limit', $data), 422, 'Display limits are available for company spotlight campaigns only.');
        abort_if($kind === 'company' && array_key_exists('impression_limit', $data) && $data['impression_limit'] !== null && $data['impression_limit'] < 1, 422);
        abort_if($data === [], 422, 'Choose a campaign status, schedule, or display limit to update.');

        $modelClass = $kind === 'job' ? JobPromotion::class : AdCampaign::class;
        $result = DB::transaction(function () use ($request, $admin, $data, $campaignId, $modelClass) {
            $campaign = $modelClass::query()->lockForUpdate()->findOrFail($campaignId);
            $before = [
                'status' => $campaign->status,
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
                'impression_limit' => $campaign->impression_limit,
            ];
            $start = array_key_exists('starts_at', $data) ? ($data['starts_at'] ? Carbon::parse($data['starts_at']) : null) : $campaign->starts_at;
            $end = array_key_exists('ends_at', $data) ? Carbon::parse($data['ends_at']) : $campaign->ends_at;
            abort_if($start && $end->lessThanOrEqualTo($start), 422, 'The campaign end must be after its start.');
            if (($data['status'] ?? null) === 'active') {
                abort_unless($start === null || $start->lessThanOrEqualTo(now()), 422, 'A future campaign must remain scheduled.');
                abort_unless($end->isFuture(), 422, 'An expired campaign cannot be activated without extending its end date.');
                if ($campaign instanceof AdCampaign && $campaign->impression_limit !== null) {
                    abort_unless($campaign->impressions_count < ($data['impression_limit'] ?? $campaign->impression_limit), 422, 'Increase the display limit before reactivating this exhausted campaign.');
                }
            }
            if (($data['status'] ?? null) === 'scheduled') {
                abort_unless($start?->isFuture(), 422, 'A scheduled campaign requires a future start date.');
            }

            $campaign->fill(array_intersect_key($data, array_flip(['status', 'impression_limit'])));
            if (array_key_exists('starts_at', $data)) {
                $campaign->starts_at = $start;
            }
            if (array_key_exists('ends_at', $data)) {
                $campaign->ends_at = $end;
            }
            $campaign->save();

            $after = [
                'status' => $campaign->status,
                'starts_at' => $campaign->starts_at?->toIso8601String(),
                'ends_at' => $campaign->ends_at?->toIso8601String(),
                'impression_limit' => $campaign->impression_limit,
            ];
            $this->audit->record($request, $admin, 'advertising.campaign.updated', $campaign, $before, $after);

            return ['status' => $campaign->status, 'ends_at' => $campaign->ends_at?->toIso8601String()];
        });

        return response()->json(['message' => 'Campaign updated.', 'campaign' => $result]);
    }

    public function updateProduct(Request $request, BillingProduct $product): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        abort_unless(in_array($product->product_type, ['company_promotion', 'job_promotion'], true), 404);
        $isJobProduct = $product->product_type === 'job_promotion';
        $data = $request->validate([
            'price_amount' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000000'],
            'duration_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:90'],
            'impression_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000000'],
            'slots' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
            'package_validity_days' => ['sometimes', 'required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['sometimes', 'required', 'boolean'],
        ]);
        abort_if($isJobProduct && array_key_exists('impression_limit', $data), 422, 'Display limits apply to company placements only.');
        abort_if(! $isJobProduct && (array_key_exists('slots', $data) || array_key_exists('package_validity_days', $data)), 422, 'Package slots apply to featured job products only.');
        abort_if($data === [], 422, 'Choose product settings to update.');
        if (($data['is_active'] ?? false) || ($product->is_active && array_key_exists('is_active', $data) && $data['is_active'])) {
            abort_unless(($data['price_amount'] ?? $product->price_amount) > 0, 422, 'Set a price before making the company promotion available.');
        }

        $before = ['price_amount' => $product->price_amount, 'duration_days' => $product->duration_days, 'is_active' => $product->is_active, 'metadata' => $product->metadata];
        if ($isJobProduct && (array_key_exists('slots', $data) || array_key_exists('package_validity_days', $data))) {
            $product->metadata = [...($product->metadata ?? []), 'promotion_type' => 'featured',
                'slots' => $data['slots'] ?? data_get($product->metadata, 'slots', 1),
                'package_validity_days' => $data['package_validity_days'] ?? data_get($product->metadata, 'package_validity_days', 90)];
        }
        if (! $isJobProduct && array_key_exists('impression_limit', $data)) {
            $product->metadata = [...($product->metadata ?? []), 'placement' => AdCampaign::COMPANY_SPOTLIGHT, 'impression_limit' => $data['impression_limit']];
        }
        $product->fill(array_intersect_key($data, array_flip(['price_amount', 'duration_days', 'is_active'])));
        $product->save();
        $after = ['price_amount' => $product->price_amount, 'duration_days' => $product->duration_days, 'is_active' => $product->is_active, 'metadata' => $product->metadata];
        $this->audit->record($request, $admin, 'advertising.product.updated', $product, $before, $after);

        return response()->json(['message' => 'Company advertising product updated.']);
    }

    public function createJobProduct(Request $request): JsonResponse
    {
        $admin = $this->authorizeAdmin($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'slug' => ['required', 'string', 'max:120', 'alpha_dash', 'unique:billing_products,slug'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_amount' => ['required', 'integer', 'min:1', 'max:100000000'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:90'],
            'slots' => ['required', 'integer', 'min:1', 'max:100'],
            'package_validity_days' => ['required', 'integer', 'min:1', 'max:365'],
            'is_active' => ['required', 'boolean'],
        ]);
        $product = BillingProduct::create([
            'name' => $data['name'], 'slug' => $data['slug'], 'description' => $data['description'] ?? null,
            'product_type' => 'job_promotion', 'price_amount' => $data['price_amount'], 'currency' => 'MMK',
            'duration_days' => $data['duration_days'], 'metadata' => [
                'promotion_type' => 'featured', 'slots' => $data['slots'],
                'package_validity_days' => $data['package_validity_days'],
            ], 'is_active' => $data['is_active'], 'sort_order' => (int) BillingProduct::query()->where('product_type', 'job_promotion')->max('sort_order') + 1,
        ]);
        $this->audit->record($request, $admin, 'featured_job.product.created', $product, [], $product->only(['name', 'slug', 'price_amount', 'duration_days', 'metadata', 'is_active']));

        return response()->json(['message' => 'Featured-job package created.', 'product' => $product], 201);
    }
}
