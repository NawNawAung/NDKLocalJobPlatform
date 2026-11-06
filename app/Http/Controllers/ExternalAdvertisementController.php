<?php

namespace App\Http\Controllers;

use App\Models\ExternalAdPayment;
use App\Models\ExternalAdvertisement;
use App\Models\User;
use App\Services\AdminAuditLogger;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class ExternalAdvertisementController extends Controller
{
    public function __construct(private readonly AdminAuditLogger $audit) {}

    private function admin(Request $request): User
    {
        abort_unless($request->user()?->role === 'admin' && $request->user()?->status, 403);

        return $request->user();
    }

    public function display(Request $request): JsonResponse
    {
        $data = $request->validate([
            'placement' => ['required', Rule::in(['home_banner', 'job_search_banner'])],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);
        $limit = $data['limit'] ?? 1;
        $ads = ExternalAdvertisement::query()->displayable()->where('placement', $data['placement'])
            ->orderBy('starts_at')->orderBy('id')->limit($limit)->get();

        $displayed = $ads->filter(function (ExternalAdvertisement $ad) {
            $updated = ExternalAdvertisement::query()->whereKey($ad->id)->displayable()
                ->where(fn ($query) => $query->whereNull('impression_limit')->orWhereColumn('impressions_count', '<', 'impression_limit'))
                ->increment('impressions_count');

            return $updated > 0;
        })->map(fn (ExternalAdvertisement $ad) => [
            'id' => $ad->id,
            'advertiser' => $ad->advertiser_name,
            'headline' => $ad->headline,
            'image_url' => Storage::disk('public')->url($ad->banner_path),
            'destination_url' => $ad->destination_url,
            'label' => 'Advertisement',
        ])->values();

        return response()->json(['data' => $displayed]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->admin($request);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(['active', 'scheduled', 'paused', 'cancelled'])],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $ads = ExternalAdvertisement::query()->with(['payments' => fn ($query) => $query->latest()])
            ->when($filters['q'] ?? null, fn ($query, string $q) => $query->where(fn ($match) => $match->where('advertiser_name', 'like', "%{$q}%")->orWhere('campaign_name', 'like', "%{$q}%")))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()->paginate(15)->withQueryString();

        return response()->json(['advertisements' => $ads->through(fn (ExternalAdvertisement $ad) => $this->serialize($ad))]);
    }

    public function store(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'advertiser_name' => ['required', 'string', 'max:160'],
            'advertiser_email' => ['nullable', 'email', 'max:191'],
            'advertiser_website' => ['nullable', 'url', 'regex:/^https?:\/\//i', 'max:2048'],
            'campaign_name' => ['required', 'string', 'max:160'],
            'headline' => ['required', 'string', 'max:180'],
            'banner' => ['required', File::image()->max(5120)],
            'destination_url' => ['required', 'url', 'regex:/^https?:\/\//i', 'max:2048'],
            'placement' => ['required', Rule::in(['home_banner', 'job_search_banner'])],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'impression_limit' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'price_amount' => ['required', 'integer', 'min:1', 'max:100000000'],
        ]);
        $path = $request->file('banner')->store('advertisements', 'public');
        $ad = DB::transaction(fn () => ExternalAdvertisement::create([
            ...collect($data)->except('banner')->all(),
            'banner_path' => $path,
            'status' => 'paused',
            'currency' => 'MMK',
            'created_by' => $admin->id,
        ]));
        $this->audit->record($request, $admin, 'external_ad.created', $ad, [], $ad->only(['advertiser_name', 'campaign_name', 'placement', 'price_amount', 'status']));

        return response()->json(['message' => 'External advertisement campaign created. Record its separate campaign payment before activation.', 'advertisement' => $this->serialize($ad)], 201);
    }

    public function update(Request $request, ExternalAdvertisement $advertisement): JsonResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'advertiser_name' => ['sometimes', 'required', 'string', 'max:160'],
            'advertiser_email' => ['sometimes', 'nullable', 'email', 'max:191'],
            'advertiser_website' => ['sometimes', 'nullable', 'url', 'regex:/^https?:\/\//i', 'max:2048'],
            'campaign_name' => ['sometimes', 'required', 'string', 'max:160'],
            'headline' => ['sometimes', 'required', 'string', 'max:180'],
            'banner' => ['sometimes', 'nullable', File::image()->max(5120)],
            'destination_url' => ['sometimes', 'required', 'url', 'regex:/^https?:\/\//i', 'max:2048'],
            'placement' => ['sometimes', 'required', Rule::in(['home_banner', 'job_search_banner'])],
            'status' => ['sometimes', 'required', Rule::in(['active', 'scheduled', 'paused', 'cancelled'])],
            'starts_at' => ['sometimes', 'nullable', 'date'],
            'ends_at' => ['sometimes', 'required', 'date'],
            'impression_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:10000000'],
            'price_amount' => ['sometimes', 'required', 'integer', 'min:1', 'max:100000000'],
        ]);
        $before = $advertisement->only(['advertiser_name', 'campaign_name', 'placement', 'status', 'starts_at', 'ends_at', 'impression_limit', 'price_amount']);
        $start = array_key_exists('starts_at', $data) ? ($data['starts_at'] ? Carbon::parse($data['starts_at']) : null) : $advertisement->starts_at;
        $end = array_key_exists('ends_at', $data) ? Carbon::parse($data['ends_at']) : $advertisement->ends_at;
        abort_if($start && $end->lessThanOrEqualTo($start), 422, 'The advertisement end must be after its start.');
        if (($data['status'] ?? null) === 'active') {
            abort_unless($advertisement->payments()->where('status', 'paid')->where('amount', $data['price_amount'] ?? $advertisement->price_amount)->exists(), 422, 'Record a paid external campaign transaction matching the campaign price before activation.');
            abort_unless(($start === null || $start->lessThanOrEqualTo(now())) && $end->isFuture(), 422, 'Only currently eligible campaigns can be activated.');
            abort_unless($advertisement->impression_limit === null || $advertisement->impressions_count < ($data['impression_limit'] ?? $advertisement->impression_limit), 422, 'Increase the display limit before reactivating an exhausted campaign.');
        }
        $willRemainActive = ($data['status'] ?? $advertisement->status) === 'active';
        if ($willRemainActive && array_key_exists('price_amount', $data)) {
            abort_unless($advertisement->payments()->where('status', 'paid')->where('amount', $data['price_amount'])->exists(), 422, 'The active campaign price must match its separately recorded paid transaction.');
        }
        if (($data['status'] ?? null) === 'scheduled') {
            abort_unless($start?->isFuture(), 422, 'A scheduled campaign requires a future start date.');
        }

        $oldPath = $advertisement->banner_path;
        if ($request->hasFile('banner')) {
            $data['banner_path'] = $request->file('banner')->store('advertisements', 'public');
        }
        unset($data['banner']);
        $advertisement->fill($data + ['updated_by' => $admin->id]);
        if (array_key_exists('starts_at', $data)) {
            $advertisement->starts_at = $start;
        }
        if (array_key_exists('ends_at', $data)) {
            $advertisement->ends_at = $end;
        }
        $advertisement->save();
        if ($oldPath !== $advertisement->banner_path) {
            Storage::disk('public')->delete($oldPath);
        }
        $this->audit->record($request, $admin, 'external_ad.updated', $advertisement, $before, $advertisement->only(['advertiser_name', 'campaign_name', 'placement', 'status', 'starts_at', 'ends_at', 'impression_limit', 'price_amount']));

        return response()->json(['message' => 'External advertisement updated.', 'advertisement' => $this->serialize($advertisement->load('payments'))]);
    }

    public function recordPayment(Request $request, ExternalAdvertisement $advertisement): JsonResponse
    {
        $admin = $this->admin($request);
        $data = $request->validate([
            'status' => ['required', Rule::in(['paid', 'failed'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_if($data['status'] === 'paid' && blank($data['reference_number']), 422, 'A transaction reference is required for a paid record.');
        $payment = DB::transaction(function () use ($advertisement, $admin, $data) {
            $lockedAd = ExternalAdvertisement::query()->lockForUpdate()->findOrFail($advertisement->id);
            abort_if($data['status'] === 'paid' && $lockedAd->payments()->where('status', 'paid')->where('amount', $lockedAd->price_amount)->exists(), 422, 'A paid transaction matching this campaign price is already recorded.');

            return $lockedAd->payments()->create([
                'amount' => $lockedAd->price_amount,
                'currency' => $lockedAd->currency,
                'payment_method' => 'offline_record',
                'reference_number' => $data['reference_number'] ?? null,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
                'recorded_by' => $admin->id,
                'paid_at' => $data['status'] === 'paid' ? now() : null,
            ]);
        });
        $this->audit->record($request, $admin, 'external_ad.payment_recorded', $payment, [], ['advertisement_id' => $advertisement->id, 'amount' => $payment->amount, 'status' => $payment->status, 'reference_number' => $payment->reference_number]);

        return response()->json(['message' => 'Separate external advertising transaction recorded.', 'payment' => $payment]);
    }

    private function serialize(ExternalAdvertisement $ad): array
    {
        return [
            'id' => $ad->id, 'advertiser_name' => $ad->advertiser_name, 'advertiser_email' => $ad->advertiser_email,
            'advertiser_website' => $ad->advertiser_website, 'campaign_name' => $ad->campaign_name,
            'headline' => $ad->headline, 'image_url' => Storage::disk('public')->url($ad->banner_path),
            'destination_url' => $ad->destination_url, 'placement' => $ad->placement, 'status' => $ad->status,
            'starts_at' => $ad->starts_at?->toIso8601String(), 'ends_at' => $ad->ends_at?->toIso8601String(),
            'impression_limit' => $ad->impression_limit, 'impressions_count' => $ad->impressions_count,
            'price_amount' => $ad->price_amount, 'currency' => $ad->currency,
            'payments' => $ad->payments->map(fn (ExternalAdPayment $payment) => ['id' => $payment->id, 'amount' => $payment->amount, 'currency' => $payment->currency, 'status' => $payment->status, 'reference_number' => $payment->reference_number, 'paid_at' => $payment->paid_at?->toIso8601String(), 'notes' => $payment->notes]),
            'payment_status' => $ad->payments->contains(fn (ExternalAdPayment $payment) => $payment->status === 'paid' && $payment->amount === $ad->price_amount) ? 'paid' : ($ad->payments->last()?->status ?? 'unrecorded'),
        ];
    }
}
