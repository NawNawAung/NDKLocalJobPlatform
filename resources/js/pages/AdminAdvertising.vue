<script setup>
import { onMounted, ref } from 'vue';

const campaigns = ref([]);
const products = ref([]);
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
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'Could not load advertising campaigns.';
    } finally {
        loading.value = false;
    }
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

            <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-950"><strong>{{ $t('Sponsored placements are explicit advertisements.') }}</strong> {{ $t('Orders use the existing manual bank-transfer review flow. No online gateway is enabled by this feature.') }}</div>
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
