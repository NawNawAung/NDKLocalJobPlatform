<script setup>
import { onMounted, onUnmounted, reactive, ref, watch } from 'vue';
import { t } from '../i18n.js';

const explorerFilters = reactive({ keyword: '', experience_level: '', location: '', category_id: '', currency: '' });
const salaryExplorer = ref(null);
const salaryExplorerLoading = ref(true);
const salaryExplorerError = ref('');
let explorerTimer;
let explorerController;
let explorerRequest = 0;

function salaryAmount(value, currency = salaryExplorer.value?.filters.currency || 'MMK') {
    return value === null || value === undefined ? '—' : `${currency} ${Number(value).toLocaleString('en-US', { maximumFractionDigits: 0 })}`;
}

function salaryRange(job) {
    const monthLabel = ` ${t('/ month')}`;
    const minimum = job.salary_min ? salaryAmount(job.salary_min, job.currency) : null;
    const maximum = job.salary_max ? salaryAmount(job.salary_max, job.currency) : null;
    if (minimum && maximum) return `${minimum} – ${maximum}${monthLabel}`;
    if (minimum) return `${t('From')} ${minimum}${monthLabel}`;
    if (maximum) return `${t('Up to')} ${maximum}${monthLabel}`;
    return t('Salary not disclosed');
}

function scheduleSalaryExplorerLoad() {
    clearTimeout(explorerTimer);
    explorerTimer = setTimeout(loadSalaryExplorer, 250);
}

async function loadSalaryExplorer() {
    const requestId = ++explorerRequest;
    explorerController?.abort();
    explorerController = new AbortController();
    salaryExplorerLoading.value = true;
    salaryExplorerError.value = '';
    const params = new URLSearchParams();
    Object.entries(explorerFilters).forEach(([key, value]) => { if (value !== '') params.set(key, value); });

    try {
        const response = await fetch(`/api/salary-insights?${params}`, {
            headers: { Accept: 'application/json' },
            signal: explorerController.signal,
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || 'Salary information could not be loaded.');
        if (requestId !== explorerRequest) return;
        salaryExplorer.value = payload;
        if (!explorerFilters.currency && payload.filters.currency) explorerFilters.currency = payload.filters.currency;
    } catch (error) {
        if (error.name !== 'AbortError' && requestId === explorerRequest) salaryExplorerError.value = error.message || 'Salary information could not be loaded.';
    } finally {
        if (requestId === explorerRequest) salaryExplorerLoading.value = false;
    }
}

function resetSalaryFilters() {
    Object.assign(explorerFilters, { keyword: '', experience_level: '', location: '', category_id: '', currency: '' });
}

watch(explorerFilters, scheduleSalaryExplorerLoad);
onMounted(loadSalaryExplorer);
onUnmounted(() => {
    clearTimeout(explorerTimer);
    explorerController?.abort();
});
</script>

<template>
    <main class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-8 sm:py-12">
        <div class="mx-auto max-w-6xl space-y-5">
            <header class="rounded-2xl bg-gradient-to-br from-blue-50 via-white to-slate-100 px-5 py-7 sm:px-8">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-[var(--brand-primary)]">{{ $t('Live platform data') }}</p>
                <div class="mt-2 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <h1 class="text-2xl font-bold tracking-tight text-[var(--brand-ink)] sm:text-3xl">{{ $t('Myanmar Salary Insights') }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $t('Compare advertised monthly salaries using published jobs on this platform.') }}</p>
                    </div>
                    <a href="/#search" class="inline-flex w-fit items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[var(--brand-primary-hover)]"><i class="ti ti-search" aria-hidden="true" />{{ $t('Find jobs') }}</a>
                </div>
            </header>

            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7" aria-labelledby="salary-explorer-title">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div><h2 id="salary-explorer-title" class="text-xl font-bold text-[var(--brand-ink)]">{{ $t('Salary overview') }}</h2><p class="mt-1 text-sm text-slate-500">{{ $t('Filter by role, experience, location, or category.') }}</p></div>
                    <button type="button" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50" @click="resetSalaryFilters">{{ $t('Reset filters') }}</button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <label class="text-sm font-medium text-slate-700">{{ $t('Job title or keyword') }}<input v-model="explorerFilters.keyword" type="search" maxlength="120" :placeholder="$t('Search job titles')" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></label>
                    <label class="text-sm font-medium text-slate-700">{{ $t('Experience level') }}<select v-model="explorerFilters.experience_level" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="">{{ $t('All levels') }}</option><option value="entry">{{ $t('Entry') }}</option><option value="junior">{{ $t('Junior') }}</option><option value="mid">{{ $t('Mid') }}</option><option value="senior">{{ $t('Senior') }}</option><option value="lead">{{ $t('Lead') }}</option><option value="executive">{{ $t('Executive') }}</option></select></label>
                    <label class="text-sm font-medium text-slate-700">{{ $t('Location') }}<input v-model="explorerFilters.location" type="search" maxlength="120" :placeholder="$t('City or township')" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></label>
                    <label class="text-sm font-medium text-slate-700">{{ $t('Job category') }}<select v-model="explorerFilters.category_id" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option value="">{{ $t('All categories') }}</option><option v-for="category in salaryExplorer?.options.categories ?? []" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select></label>
                    <label class="text-sm font-medium text-slate-700">{{ $t('Currency') }}<select v-model="explorerFilters.currency" class="mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"><option v-for="currency in salaryExplorer?.options.currencies ?? []" :key="currency" :value="currency">{{ currency }}</option></select></label>
                </div>

                <div v-if="salaryExplorerLoading" class="mt-5 rounded-xl bg-blue-50 px-4 py-5 text-sm text-blue-900" role="status">{{ $t('Loading salary data…') }}</div>
                <div v-else-if="salaryExplorerError" class="mt-5 rounded-xl border border-red-200 bg-red-50 px-4 py-4 text-sm text-red-800" role="alert">{{ $t(salaryExplorerError) }} <button type="button" class="ml-2 font-semibold underline" @click="loadSalaryExplorer">{{ $t('Try again') }}</button></div>
                <template v-else-if="salaryExplorer">
                    <div v-if="salaryExplorer.summary.sample_size" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Listings with salary') }}</p><p class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">{{ salaryExplorer.summary.sample_size }}</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Lowest advertised') }}</p><p class="mt-2 text-lg font-bold text-[var(--brand-ink)]">{{ salaryAmount(salaryExplorer.summary.minimum_salary) }}</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Median advertised') }}</p><p class="mt-2 text-lg font-bold text-[var(--brand-ink)]">{{ salaryExplorer.summary.median_available ? salaryAmount(salaryExplorer.summary.median_salary) : $t('Insufficient data') }}</p></article>
                        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Highest advertised') }}</p><p class="mt-2 text-lg font-bold text-[var(--brand-ink)]">{{ salaryAmount(salaryExplorer.summary.maximum_salary) }}</p></article>
                    </div>
                    <div v-else class="mt-5 rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center"><h3 class="font-semibold text-slate-800">{{ $t('No salary data matches these filters') }}</h3><p class="mt-1 text-sm text-slate-600">{{ $t('Try changing or clearing your filters.') }}</p></div>

                    <section class="mt-6" aria-labelledby="salary-related-jobs-title">
                        <div class="flex items-center justify-between gap-3"><h3 id="salary-related-jobs-title" class="font-semibold text-slate-900">{{ $t('Matching job listings') }}</h3><span class="text-xs text-slate-500">{{ salaryExplorer.jobs.length }}</span></div>
                        <div v-if="salaryExplorer.jobs.length" class="mt-3 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <article v-for="job in salaryExplorer.jobs" :key="job.id" class="rounded-xl border border-slate-200 p-4 transition hover:border-blue-200 hover:shadow-sm"><h4 class="font-semibold text-slate-900">{{ job.title }}</h4><p class="mt-1 text-xs text-slate-500">{{ job.category || $t('Category not specified') }} · {{ job.location }}</p><p class="mt-3 text-sm font-semibold text-[var(--brand-ink)]">{{ salaryRange(job) }}</p><a :href="`/#details?job=${job.id}`" class="mt-3 inline-flex text-sm font-semibold text-blue-800 hover:underline">{{ $t('View job') }}</a></article>
                        </div>
                        <p v-else class="mt-3 text-sm text-slate-500">{{ $t('No matching jobs are currently listed.') }}</p>
                    </section>
                    <p class="mt-5 border-t border-slate-100 pt-4 text-xs leading-5 text-slate-500">{{ $t('Figures come from published, unexpired listings with valid salary data. They are advertised amounts, not guaranteed pay. The median is shown only when the sample is large enough.') }}</p>
                </template>
            </section>
        </div>
    </main>
</template>
