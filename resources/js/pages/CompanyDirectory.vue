<script setup>
import { onMounted, ref } from 'vue';

const query = ref(new URLSearchParams(window.location.search).get('q') ?? '');
const companies = ref([]);
const page = ref(1);
const lastPage = ref(1);
const total = ref(0);
const loading = ref(true);
const error = ref('');

async function loadCompanies(nextPage = 1, updateUrl = true) {
    loading.value = true;
    error.value = '';
    const params = { page: nextPage };
    if (query.value.trim()) params.q = query.value.trim();

    try {
        const { data } = await window.axios.get('/api/companies', { params });
        companies.value = data.companies.data ?? [];
        page.value = data.companies.current_page ?? 1;
        lastPage.value = data.companies.last_page ?? 1;
        total.value = data.companies.total ?? 0;
        if (updateUrl) {
            const search = new URLSearchParams();
            if (query.value.trim()) search.set('q', query.value.trim());
            if (page.value > 1) search.set('page', String(page.value));
            window.history.replaceState({}, '', `/companies${search.size ? `?${search}` : ''}`);
        }
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'Companies could not be loaded. Please try again.';
        companies.value = [];
    } finally {
        loading.value = false;
    }
}

function submitSearch() { loadCompanies(1); }
function clearSearch() { query.value = ''; loadCompanies(1); }
function companyLocation(company) { return company.location || 'Location not provided'; }

onMounted(() => loadCompanies(Number(new URLSearchParams(window.location.search).get('page')) || 1, false));
</script>

<template>
    <section class="bg-slate-50 px-4 py-10 sm:px-6 sm:py-14 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="mb-8">
                <p class="text-sm font-semibold text-[var(--brand-primary)]">{{ $t('Explore employers') }}</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">{{ $t('Companies') }}</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $t('Find employers, learn about their business, and see their current openings.') }}</p>
            </header>

            <form class="mb-6 flex flex-col gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row" role="search" @submit.prevent="submitSearch">
                <label class="sr-only" for="company-search">{{ $t('Search companies') }}</label>
                <input id="company-search" v-model="query" type="search" maxlength="120" class="min-w-0 flex-1 rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" :placeholder="$t('Company name, description, or location')">
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] sm:flex-none">{{ $t('Search companies') }}</button>
                    <button v-if="query" type="button" class="rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="clearSearch">{{ $t('Clear') }}</button>
                </div>
            </form>

            <p v-if="!loading && !error" class="mb-4 text-sm text-slate-600">{{ total }} {{ $t('companies found') }}</p>
            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-600" role="status">{{ $t('Loading companies…') }}</div>
            <div v-else-if="error" class="rounded-xl border border-amber-200 bg-amber-50 p-5 text-sm text-amber-900" role="alert">{{ $t(error) }} <button type="button" class="ml-2 font-semibold underline" @click="loadCompanies(page)">{{ $t('Try again') }}</button></div>
            <div v-else-if="companies.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article v-for="company in companies" :key="company.id" class="flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                    <div class="flex items-start gap-4">
                        <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-50 text-2xl text-blue-800"><i class="ti ti-building" aria-hidden="true" /></span>
                        <div class="min-w-0 flex-1">
                            <h2 class="break-words text-lg font-bold text-slate-900">{{ company.name }}</h2>
                            <span v-if="company.is_verified" class="mt-1 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-800"><i class="ti ti-circle-check" aria-hidden="true" />{{ $t('Verified employer') }}</span>
                        </div>
                    </div>
                    <p class="mt-4 line-clamp-3 min-h-[4.5rem] text-sm leading-6 text-slate-600">{{ company.description || $t('Company information has not been added yet.') }}</p>
                    <p class="mt-3 flex items-start gap-2 text-sm text-slate-500"><i class="ti ti-map-pin mt-0.5 shrink-0" aria-hidden="true" /><span>{{ companyLocation(company) }}</span></p>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 border-t border-slate-100 pt-4 text-sm">
                        <span class="font-semibold text-slate-800">{{ company.open_jobs_count }} {{ $t('open jobs') }}</span>
                        <span v-if="company.average_rating !== null" class="inline-flex items-center gap-1 text-amber-700"><i class="ti ti-star-filled" aria-hidden="true" />{{ Number(company.average_rating).toFixed(1) }} <span class="text-slate-500">({{ company.review_count }})</span></span>
                    </div>
                    <a :href="`/companies/${company.id}`" class="mt-5 inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-blue-200 px-4 py-2.5 text-sm font-semibold text-blue-800 transition hover:bg-blue-50">{{ $t('View company profile') }}<i class="ti ti-arrow-right" aria-hidden="true" /></a>
                </article>
            </div>
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <i class="ti ti-building-off text-3xl text-slate-400" aria-hidden="true" />
                <h2 class="mt-3 text-lg font-semibold text-slate-900">{{ $t('No companies found') }}</h2>
                <p class="mt-1 text-sm text-slate-600">{{ $t('Try another company name or location.') }}</p>
            </div>

            <nav v-if="!loading && lastPage > 1" class="mt-7 flex items-center justify-between" :aria-label="$t('Company pages')">
                <button type="button" :disabled="page <= 1" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" @click="loadCompanies(page - 1)">{{ $t('Previous') }}</button>
                <span class="text-sm text-slate-600">{{ $t('Page') }} {{ page }}/{{ lastPage }}</span>
                <button type="button" :disabled="page >= lastPage" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" @click="loadCompanies(page + 1)">{{ $t('Next') }}</button>
            </nav>
        </div>
    </section>
</template>
