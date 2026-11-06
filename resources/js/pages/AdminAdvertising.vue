<script setup>
import { onMounted, ref } from 'vue';

const campaigns = ref([]);
const products = ref([]);
const jobProducts = ref([]);
const externalAds = ref([]);
const externalPagination = ref(null);
const externalSearch = ref('');
const newAd = ref({ advertiser_name: '', advertiser_email: '', advertiser_website: '', campaign_name: '', headline: '', destination_url: '', placement: 'home_banner', starts_at: '', ends_at: '', impression_limit: '', price_amount: '' });
const bannerFile = ref(null);
const paymentReferences = ref({});
const newJobProduct = ref({ name: '', slug: '', description: '', price_amount: '', duration_days: 7, slots: 1, package_validity_days: 90, is_active: false });
const pagination = ref(null);
const loading = ref(true);
const saving = ref('');
const search = ref('');
const statusFilter = ref('');
const error = ref('');
const notice = ref('');

function toLocalDateTime(value) {
    if (!value) return '';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return '';
    const offset = date.getTimezoneOffset() * 60000;
    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

async function load(page = 1) {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await window.axios.get('/api/admin/advertising/campaigns', {
            params: { q: search.value || undefined, status: statusFilter.value || undefined, page },
        });
        pagination.value = data.campaigns;
        campaigns.value = (data.campaigns?.data ?? []).map(campaign => ({
            ...campaign,
            starts_at_input: toLocalDateTime(campaign.starts_at),
            ends_at_input: toLocalDateTime(campaign.ends_at),
            impression_limit_input: campaign.impression_limit ?? '',
        }));
        products.value = (data.company_products ?? []).map(product => ({ ...product, impression_limit: product.impression_limit ?? '' }));
        jobProducts.value = data.job_products ?? [];
        await loadExternalAds();
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'Could not load advertising campaigns.';
    } finally {
        loading.value = false;
    }
}

async function loadExternalAds(page = 1) {
    const { data } = await window.axios.get('/api/admin/advertising/external', { params: { q: externalSearch.value || undefined, page } });
    externalPagination.value = data.advertisements;
    externalAds.value = (data.advertisements?.data ?? []).map(ad => ({ ...ad, starts_at_input: toLocalDateTime(ad.starts_at), ends_at_input: toLocalDateTime(ad.ends_at) }));
}

function selectBanner(event) { bannerFile.value = event.target.files?.[0] ?? null; }

async function createExternalAd() {
    saving.value = 'external-create'; error.value = ''; notice.value = '';
    const form = new FormData();
    Object.entries(newAd.value).forEach(([key, value]) => { if (value !== '') form.append(key, value); });
    if (bannerFile.value) form.append('banner', bannerFile.value);
    try {
        const { data } = await window.axios.post('/api/admin/advertising/external', form);
        notice.value = data.message;
        newAd.value = { advertiser_name: '', advertiser_email: '', advertiser_website: '', campaign_name: '', headline: '', destination_url: '', placement: 'home_banner', starts_at: '', ends_at: '', impression_limit: '', price_amount: '' };
        bannerFile.value = null;
        await loadExternalAds();
    } catch (exception) { error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not create external ad campaign.'; }
    finally { saving.value = ''; }
}

async function recordExternalPayment(ad, status = 'paid') {
    saving.value = `external-payment-${ad.id}`; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.post(`/api/admin/advertising/external/${ad.id}/payments`, { status, reference_number: paymentReferences.value[ad.id] || undefined });
        notice.value = data.message;
        await loadExternalAds(externalPagination.value?.current_page ?? 1);
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not record the external campaign payment.'; }
    finally { saving.value = ''; }
}

async function saveExternalAd(ad) {
    saving.value = `external-${ad.id}`; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.patch(`/api/admin/advertising/external/${ad.id}`, { status: ad.status, starts_at: ad.starts_at_input || null, ends_at: ad.ends_at_input });
        notice.value = data.message;
        await loadExternalAds(externalPagination.value?.current_page ?? 1);
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not update external campaign.'; }
    finally { saving.value = ''; }
}

async function saveJobProduct(product) {
    saving.value = `job-product-${product.id}`; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.patch(`/api/admin/advertising/products/${product.id}`, { price_amount: Number(product.price_amount), duration_days: Number(product.duration_days), slots: Number(product.slots), package_validity_days: Number(product.package_validity_days), is_active: Boolean(product.is_active) });
        notice.value = data.message;
        await load();
    } catch (exception) { error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not update featured-job package.'; }
    finally { saving.value = ''; }
}

async function createJobProduct() {
    saving.value = 'job-product-create'; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.post('/api/admin/advertising/job-products', { ...newJobProduct.value, price_amount: Number(newJobProduct.value.price_amount), duration_days: Number(newJobProduct.value.duration_days), slots: Number(newJobProduct.value.slots), package_validity_days: Number(newJobProduct.value.package_validity_days), is_active: Boolean(newJobProduct.value.is_active) });
        notice.value = data.message;
        newJobProduct.value = { name: '', slug: '', description: '', price_amount: '', duration_days: 7, slots: 1, package_validity_days: 90, is_active: false };
        await load();
    } catch (exception) { error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not create package.'; }
    finally { saving.value = ''; }
}

async function saveCampaign(campaign) {
    const key = `${campaign.kind}-${campaign.id}`;
    saving.value = key; error.value = ''; notice.value = '';
    try {
        const payload = {
            status: campaign.status,
            starts_at: campaign.starts_at_input || null,
            ends_at: campaign.ends_at_input,
        };
        if (campaign.kind === 'company') payload.impression_limit = campaign.impression_limit_input === '' ? null : Number(campaign.impression_limit_input);
        const { data } = await window.axios.patch(`/api/admin/advertising/campaigns/${campaign.kind}/${campaign.id}`, payload);
        notice.value = data.message;
        await load(pagination.value?.current_page ?? 1);
    } catch (exception) {
        error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not update this campaign.';
    } finally {
        saving.value = '';
    }
}

async function saveProduct(product) {
    saving.value = `product-${product.id}`; error.value = ''; notice.value = '';
    try {
        const payload = {
            price_amount: Number(product.price_amount),
            duration_days: Number(product.duration_days),
            impression_limit: product.impression_limit === '' ? null : Number(product.impression_limit),
            is_active: Boolean(product.is_active),
        };
        const { data } = await window.axios.patch(`/api/admin/advertising/products/${product.id}`, payload);
        notice.value = data.message;
        await load(pagination.value?.current_page ?? 1);
    } catch (exception) {
        error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not update this advertising product.';
    } finally {
        saving.value = '';
    }
}

function statusClass(status) {
    if (status === 'active') return 'bg-emerald-50 text-emerald-800';
    if (status === 'scheduled') return 'bg-blue-50 text-blue-800';
    if (status === 'paused') return 'bg-amber-50 text-amber-800';
    return 'bg-slate-100 text-slate-700';
}

onMounted(() => load());
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-sm font-semibold text-[var(--brand-primary)]">{{ $t('Platform administration') }}</p><h1 class="mt-1 text-3xl font-bold text-[var(--brand-ink)]">{{ $t('Advertising') }}</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ $t('Manage paid job and company campaigns, their schedules, and display limits. Changes are recorded in the audit log.') }}</p></div>
                <a href="/admin" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ $t('Admin dashboard') }}</a>
            </header>

            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"><strong>{{ $t('Employer promotions and external advertisements use separate records and workflows.') }}</strong> {{ $t('Employer packages use the existing manual bank-transfer review flow. External advertising transactions are recorded separately by an administrator; no online gateway or real charge is triggered.') }}</div>
            <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $t(error) }}</p>
            <p v-if="notice" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ $t(notice) }}</p>

            <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4"><h2 class="text-lg font-bold text-slate-950">{{ $t('Featured company product') }}</h2><p class="mt-1 text-sm text-slate-600">{{ $t('Set the price and duration here. The product starts disabled and becomes purchasable only after it has a price and is activated.') }}</p></div>
                <div v-if="products.length" class="grid gap-4 md:grid-cols-2">
                    <article v-for="product in products" :key="product.id" class="rounded-xl border border-slate-200 p-4">
                        <h3 class="font-semibold text-slate-900">{{ product.name }}</h3><p class="mt-1 text-sm text-slate-600">{{ product.description }}</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <label class="text-xs font-semibold text-slate-600">{{ $t('Price (MMK)') }}<input v-model.number="product.price_amount" type="number" min="1" max="100000000" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label>
                            <label class="text-xs font-semibold text-slate-600">{{ $t('Duration (days)') }}<input v-model.number="product.duration_days" type="number" min="1" max="90" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label>
                            <label class="text-xs font-semibold text-slate-600 sm:col-span-2">{{ $t('Maximum impressions per order') }}<input v-model.number="product.impression_limit" type="number" min="1" max="10000000" :placeholder="$t('No impression cap')" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label>
                        </div>
                        <label class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-slate-700"><input v-model="product.is_active" type="checkbox" class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">{{ $t('Available to employers') }}</label>
                        <button type="button" :disabled="saving === `product-${product.id}`" class="mt-4 block w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:opacity-50" @click="saveProduct(product)">{{ saving === `product-${product.id}` ? $t('Saving…') : $t('Save product settings') }}</button>
                    </article>
                </div>
                <p v-else class="rounded-lg bg-slate-50 p-4 text-sm text-slate-600">{{ $t('No company advertising product is configured. Run the billing catalog seeder, then set its price here.') }}</p>
            </section>

            <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                <div class="mb-4"><h2 class="text-lg font-bold text-slate-950">Featured job packages</h2><p class="mt-1 text-sm text-slate-600">Configure purchasable employer packages. Existing prices remain unchanged until an administrator edits them here.</p></div>
                <div v-if="jobProducts.length" class="grid gap-4 md:grid-cols-2"><article v-for="product in jobProducts" :key="product.id" class="rounded-xl border border-slate-200 p-4"><h3 class="font-semibold text-slate-900">{{ product.name }}</h3><div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="text-xs font-semibold text-slate-600">Price ({{ product.currency }})<input v-model.number="product.price_amount" type="number" min="1" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Featured duration per job (days)<input v-model.number="product.duration_days" type="number" min="1" max="90" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Job slots<input v-model.number="product.slots" type="number" min="1" max="100" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Package validity (days)<input v-model.number="product.package_validity_days" type="number" min="1" max="365" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label></div><label class="mt-3 inline-flex items-center gap-2 text-sm"><input v-model="product.is_active" type="checkbox">Available for purchase</label><button type="button" :disabled="saving === `job-product-${product.id}`" class="mt-3 w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50" @click="saveJobProduct(product)">{{ saving === `job-product-${product.id}` ? $t('Saving…') : 'Save package settings' }}</button></article></div>
                <p v-else class="text-sm text-slate-600">No featured-job package is configured. Run the existing billing catalog seeder to create its catalog entry.</p>
                <details class="mt-5 rounded-xl border border-slate-200 p-4"><summary class="cursor-pointer text-sm font-semibold text-blue-900">Create another featured-job package</summary><form class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4" @submit.prevent="createJobProduct"><label class="text-xs font-semibold text-slate-600">Package name<input v-model="newJobProduct.name" required maxlength="160" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Unique slug<input v-model="newJobProduct.slug" required maxlength="120" pattern="[A-Za-z0-9_-]+" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Price (MMK)<input v-model.number="newJobProduct.price_amount" type="number" min="1" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Featured days per job<input v-model.number="newJobProduct.duration_days" type="number" min="1" max="90" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Job slots<input v-model.number="newJobProduct.slots" type="number" min="1" max="100" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Package validity days<input v-model.number="newJobProduct.package_validity_days" type="number" min="1" max="365" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600 sm:col-span-2">Description<textarea v-model="newJobProduct.description" maxlength="1000" rows="2" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></textarea></label><label class="inline-flex items-center gap-2 text-sm"><input v-model="newJobProduct.is_active" type="checkbox">Available for purchase now</label><button type="submit" :disabled="saving === 'job-product-create'" class="rounded-lg bg-blue-800 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ saving === 'job-product-create' ? $t('Saving…') : 'Create package' }}</button></form></details>
            </section>

            <section class="mb-6 overflow-hidden rounded-xl border border-violet-200 bg-white shadow-sm">
                <div class="border-b border-violet-100 bg-violet-50/70 p-5"><h2 class="text-lg font-bold text-slate-950">External business advertisements</h2><p class="mt-1 text-sm text-slate-600">Create banner campaigns for third-party advertisers. These records are independent of employers, jobs, and employer billing.</p></div>
                <form class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3" @submit.prevent="createExternalAd">
                    <label class="text-xs font-semibold text-slate-600">Advertiser name<input v-model="newAd.advertiser_name" required maxlength="160" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Advertiser email<input v-model="newAd.advertiser_email" type="email" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Advertiser website<input v-model="newAd.advertiser_website" type="url" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Campaign name<input v-model="newAd.campaign_name" required maxlength="160" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Banner headline<input v-model="newAd.headline" required maxlength="180" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Destination URL<input v-model="newAd.destination_url" type="url" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Banner image<input type="file" accept="image/*" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal" @change="selectBanner"></label>
                    <label class="text-xs font-semibold text-slate-600">Placement<select v-model="newAd.placement" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal"><option value="home_banner">Home banner</option><option value="job_search_banner">Job search banner</option></select></label>
                    <label class="text-xs font-semibold text-slate-600">Campaign price (MMK)<input v-model.number="newAd.price_amount" type="number" min="1" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Starts at<input v-model="newAd.starts_at" type="datetime-local" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Ends at<input v-model="newAd.ends_at" type="datetime-local" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <label class="text-xs font-semibold text-slate-600">Impression cap (optional)<input v-model="newAd.impression_limit" type="number" min="1" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label>
                    <button type="submit" :disabled="saving === 'external-create'" class="self-end rounded-lg bg-violet-800 px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ saving === 'external-create' ? $t('Saving…') : 'Create external campaign' }}</button>
                </form>
                <div class="flex flex-wrap items-end gap-3 border-t border-slate-100 p-4"><label class="min-w-56 flex-1 text-xs font-semibold text-slate-600">Search external campaigns<input v-model="externalSearch" type="search" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold" @click="loadExternalAds(1)">Search</button></div>
                <div v-if="externalAds.length" class="divide-y divide-slate-100"> <article v-for="ad in externalAds" :key="ad.id" class="grid gap-4 p-5 lg:grid-cols-[180px_minmax(0,1fr)]"><img :src="ad.image_url" :alt="ad.headline" class="h-24 w-full rounded-lg border border-slate-200 object-cover lg:w-44"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ ad.advertiser_name }} · {{ ad.campaign_name }}</h3><span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-bold text-violet-900">Advertisement</span><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold">{{ ad.status }} · payment {{ ad.payment_status }}</span></div><p class="mt-1 text-sm text-slate-600">{{ ad.headline }} · {{ ad.placement }} · {{ ad.impressions_count }} / {{ ad.impression_limit ?? 'Unlimited' }} impressions · {{ ad.price_amount }} {{ ad.currency }}</p><div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4"><label class="text-xs font-semibold text-slate-600">Status<select v-model="ad.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-sm font-normal"><option value="active">Active</option><option value="scheduled">Scheduled</option><option value="paused">Paused</option><option value="cancelled">Cancelled</option></select></label><label class="text-xs font-semibold text-slate-600">Starts<input v-model="ad.starts_at_input" type="datetime-local" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm font-normal"></label><label class="text-xs font-semibold text-slate-600">Ends<input v-model="ad.ends_at_input" type="datetime-local" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-2 text-sm font-normal"></label><button type="button" :disabled="saving === `external-${ad.id}`" class="self-end rounded-lg border border-violet-200 px-3 py-2 text-sm font-semibold text-violet-900" @click="saveExternalAd(ad)">Save status/schedule</button></div><div v-if="ad.payment_status !== 'paid'" class="mt-3 flex flex-wrap gap-2"><input v-model="paymentReferences[ad.id]" maxlength="100" placeholder="External transaction reference" class="min-w-56 flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm"><button type="button" :disabled="saving === `external-payment-${ad.id}` || !paymentReferences[ad.id]" class="rounded-lg bg-emerald-700 px-3 py-2 text-xs font-semibold text-white disabled:opacity-50" @click="recordExternalPayment(ad, 'paid')">Record payment received</button><button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700" @click="recordExternalPayment(ad, 'failed')">Record payment failed</button></div><details v-if="ad.payments?.length" class="mt-3 text-xs text-slate-600"><summary class="cursor-pointer font-semibold">Separate campaign transaction history</summary><p v-for="payment in ad.payments" :key="payment.id" class="mt-1">{{ payment.status }} · {{ payment.amount }} {{ payment.currency }} · {{ payment.reference_number || 'No reference' }}</p></details></div></article></div><p v-else class="p-8 text-center text-sm text-slate-600">No external campaigns have been created.</p>
                <footer v-if="externalPagination?.last_page > 1" class="flex justify-between border-t border-slate-100 p-4 text-xs"><span>Page {{ externalPagination.current_page }} / {{ externalPagination.last_page }}</span><div class="flex gap-2"><button type="button" :disabled="!externalPagination.prev_page_url" class="rounded border px-3 py-1.5 disabled:opacity-40" @click="loadExternalAds(externalPagination.current_page - 1)">Previous</button><button type="button" :disabled="!externalPagination.next_page_url" class="rounded border px-3 py-1.5 disabled:opacity-40" @click="loadExternalAds(externalPagination.current_page + 1)">Next</button></div></footer>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5"><h2 class="text-lg font-bold text-slate-950">{{ $t('Campaign management') }}</h2><p class="mt-1 text-sm text-slate-600">{{ $t('Review, pause, resume, cancel, or reschedule sponsored campaigns.') }}</p></div>
                <form class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-[minmax(0,1fr)_200px_auto]" @submit.prevent="load(1)">
                    <label class="text-xs font-semibold text-slate-600">{{ $t('Search campaigns') }}<input v-model="search" type="search" maxlength="120" :placeholder="$t('Company or job title')" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label>
                    <label class="text-xs font-semibold text-slate-600">{{ $t('Status') }}<select v-model="statusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900"><option value="">{{ $t('All statuses') }}</option><option value="active">{{ $t('Active') }}</option><option value="scheduled">{{ $t('Scheduled') }}</option><option value="paused">{{ $t('Paused') }}</option><option value="cancelled">{{ $t('Cancelled') }}</option></select></label>
                    <button class="self-end rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white">{{ $t('Apply filters') }}</button>
                </form>
                <div v-if="loading" class="p-10 text-center text-sm text-slate-600" role="status">{{ $t('Loading campaigns…') }}</div>
                <div v-else-if="campaigns.length" class="divide-y divide-slate-100">
                    <article v-for="campaign in campaigns" :key="`${campaign.kind}-${campaign.id}`" class="p-5">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h3 class="font-semibold text-slate-900">{{ campaign.company }}<span v-if="campaign.kind === 'job'"> · {{ campaign.target }}</span></h3><span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(campaign.status)">{{ campaign.status }}</span><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-900">{{ campaign.kind === 'job' ? $t('Sponsored job') : $t('Sponsored company') }}</span></div><p class="mt-1 text-xs text-slate-500">{{ campaign.placement }} · {{ campaign.campaign_type }}</p><p v-if="campaign.kind === 'company'" class="mt-1 text-xs text-slate-600">{{ campaign.impressions_count }} / {{ campaign.impression_limit ?? $t('Unlimited') }} {{ $t('impressions') }}</p></div>
                        </div>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                            <label class="text-xs font-semibold text-slate-600">{{ $t('Status') }}<select v-model="campaign.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-normal text-slate-900"><option value="active">{{ $t('Active') }}</option><option value="scheduled">{{ $t('Scheduled') }}</option><option value="paused">{{ $t('Paused') }}</option><option value="cancelled">{{ $t('Cancelled') }}</option></select></label>
                            <label class="text-xs font-semibold text-slate-600">{{ $t('Starts at') }}<input v-model="campaign.starts_at_input" type="datetime-local" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></label>
                            <label class="text-xs font-semibold text-slate-600">{{ $t('Ends at') }}<input v-model="campaign.ends_at_input" type="datetime-local" required class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></label>
                            <label v-if="campaign.kind === 'company'" class="text-xs font-semibold text-slate-600">{{ $t('Display limit') }}<input v-model="campaign.impression_limit_input" type="number" min="1" max="10000000" :placeholder="$t('Unlimited')" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"></label>
                            <button type="button" :disabled="saving === `${campaign.kind}-${campaign.id}`" class="self-end rounded-lg border border-blue-200 px-4 py-2.5 text-sm font-semibold text-blue-800 hover:bg-blue-50 disabled:opacity-50" @click="saveCampaign(campaign)">{{ saving === `${campaign.kind}-${campaign.id}` ? $t('Saving…') : $t('Save campaign') }}</button>
                        </div>
                    </article>
                </div>
                <p v-else-if="!loading" class="p-10 text-center text-sm text-slate-600">{{ $t('No campaigns match these filters.') }}</p>
                <footer v-if="pagination?.last_page > 1" class="flex items-center justify-between border-t border-slate-100 px-4 py-3"><span class="text-xs text-slate-500">{{ $t('Page') }} {{ pagination.current_page }}/{{ pagination.last_page }} · {{ pagination.total }}</span><div class="flex gap-2"><button type="button" :disabled="!pagination.prev_page_url || loading" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold disabled:opacity-40" @click="load(pagination.current_page - 1)">{{ $t('Previous') }}</button><button type="button" :disabled="!pagination.next_page_url || loading" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold disabled:opacity-40" @click="load(pagination.current_page + 1)">{{ $t('Next') }}</button></div></footer>
            </section>
        </div>
    </section>
</template>
