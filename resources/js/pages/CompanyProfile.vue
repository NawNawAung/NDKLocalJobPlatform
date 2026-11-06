<script setup>
import { onMounted, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const company = ref(null);
const jobs = ref([]);
const reviews = ref([]);
const page = ref(1);
const lastPage = ref(1);
const loading = ref(true);
const error = ref('');
const companyId = bootstrap.companyId;

async function loadProfile(nextPage = 1) {
    loading.value = true;
    error.value = '';
    try {
        const [profileResponse, reviewResponse] = await Promise.all([
            window.axios.get(`/api/companies/${encodeURIComponent(companyId)}`, { params: { page: nextPage } }),
            window.axios.get(`/api/employers/${encodeURIComponent(companyId)}/reviews`),
        ]);
        company.value = profileResponse.data.company;
        jobs.value = profileResponse.data.jobs.data ?? [];
        page.value = profileResponse.data.jobs.current_page ?? 1;
        lastPage.value = profileResponse.data.jobs.last_page ?? 1;
        reviews.value = reviewResponse.data.reviews ?? [];
    } catch (exception) {
        error.value = exception.response?.status === 404 ? 'This company profile is unavailable.' : 'Company information could not be loaded. Please try again.';
    } finally {
        loading.value = false;
    }
}

function formatSalary(job) {
    if (job.salary_min == null && job.salary_max == null) return 'Salary not disclosed';
    const amount = value => new Intl.NumberFormat().format(value);
    const currency = job.salary_currency || 'MMK';
    if (job.salary_min != null && job.salary_max != null) return `${amount(job.salary_min)}–${amount(job.salary_max)} ${currency}`;
    return job.salary_min != null ? `From ${amount(job.salary_min)} ${currency}` : `Up to ${amount(job.salary_max)} ${currency}`;
}

function formatLabel(value) { return value ? value.replaceAll('_', ' ') : ''; }

onMounted(() => loadProfile());
</script>

<template>
    <section class="min-h-[60vh] bg-slate-50 px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <a href="/companies" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-800 hover:underline"><i class="ti ti-arrow-left" aria-hidden="true" />{{ $t('All companies') }}</a>
            <div v-if="loading" class="mt-6 rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-600" role="status">{{ $t('Loading company profile…') }}</div>
            <div v-else-if="error" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900" role="alert">{{ $t(error) }} <a href="/companies" class="ml-2 font-semibold underline">{{ $t('Browse companies') }}</a></div>
            <template v-else-if="company">
                <header class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <span class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-blue-50 text-3xl text-blue-800"><i class="ti ti-building" aria-hidden="true" /></span>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-3">
                                <h1 class="break-words text-2xl font-bold text-[var(--brand-ink)] sm:text-3xl">{{ company.name }}</h1>
                                <span v-if="company.is_verified" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800"><i class="ti ti-circle-check" aria-hidden="true" />{{ $t('Verified employer') }}</span>
                            </div>
                            <p class="mt-2 flex items-start gap-2 text-sm text-slate-600"><i class="ti ti-map-pin mt-0.5 shrink-0" aria-hidden="true" /><span>{{ company.location || $t('Location not provided') }}</span></p>
                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm">
                                <span class="font-semibold text-slate-800">{{ company.open_jobs_count }} {{ $t('open jobs') }}</span>
                                <span v-if="company.average_rating !== null" class="inline-flex items-center gap-1 font-semibold text-amber-700"><i class="ti ti-star-filled" aria-hidden="true" />{{ Number(company.average_rating).toFixed(1) }} <span class="font-normal text-slate-500">({{ company.review_count }} {{ $t('reviews') }})</span></span>
                                <span v-else class="text-slate-500">{{ $t('No reviews yet') }}</span>
                            </div>
                            <div class="mt-4 flex flex-wrap gap-3">
                                <a v-if="company.website_url" :href="company.website_url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50"><i class="ti ti-world" aria-hidden="true" />{{ $t('Company website') }}</a>
                                <template v-for="(url, network) in company.social_links" :key="network"><a v-if="url" :href="url" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold capitalize text-slate-700 hover:bg-slate-50"><i class="ti ti-link" aria-hidden="true" />{{ network }}</a></template>
                            </div>
                        </div>
                    </div>
                    <div class="mt-7 border-t border-slate-100 pt-6">
                        <h2 class="text-lg font-bold text-slate-900">{{ $t('About this company') }}</h2>
                        <p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-700">{{ company.description || $t('Company information has not been added yet.') }}</p>
                    </div>
                </header>

                <section class="mt-8" aria-labelledby="company-jobs-heading">
                    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                        <div><p class="text-sm font-semibold text-[var(--brand-primary)]">{{ company.open_jobs_count }} {{ $t('open jobs') }}</p><h2 id="company-jobs-heading" class="mt-1 text-xl font-bold text-[var(--brand-ink)]">{{ $t('Current openings') }}</h2></div>
                    </div>
                    <div v-if="jobs.length" class="space-y-3">
                        <article v-for="job in jobs" :key="job.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md">
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2"><h3 class="text-base font-bold text-slate-900">{{ job.title }}</h3><span v-if="job.is_featured" class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-900"><i class="ti ti-star-filled" aria-hidden="true" /> {{ $t('Featured') }}</span></div>
                                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-600"><span>{{ job.category }}</span><span>{{ job.location }}</span><span>{{ $t(formatLabel(job.employment_type)) }}</span><span v-if="job.experience_level">{{ $t(formatLabel(job.experience_level)) }}</span><span>{{ formatSalary(job) }}</span></div>
                                </div>
                                <a :href="`/#details?job=${job.id}`" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]">{{ $t('View job') }}<i class="ti ti-arrow-right" aria-hidden="true" /></a>
                            </div>
                        </article>
                    </div>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">{{ $t('This company has no current openings.') }}</div>
                    <nav v-if="lastPage > 1" class="mt-5 flex items-center justify-between" :aria-label="$t('Job pages')">
                        <button type="button" :disabled="page <= 1" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40" @click="loadProfile(page - 1)">{{ $t('Previous') }}</button>
                        <span class="text-sm text-slate-600">{{ $t('Page') }} {{ page }}/{{ lastPage }}</span>
                        <button type="button" :disabled="page >= lastPage" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 disabled:opacity-40" @click="loadProfile(page + 1)">{{ $t('Next') }}</button>
                    </nav>
                </section>

                <section v-if="reviews.length" class="mt-8 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <h2 class="text-lg font-bold text-slate-900">{{ $t('Candidate reviews') }}</h2>
                    <div class="mt-4 divide-y divide-slate-100">
                        <article v-for="review in reviews" :key="review.id" class="py-4 first:pt-0 last:pb-0">
                            <p class="flex items-center gap-2 text-sm font-semibold text-slate-800"><span class="inline-flex items-center gap-1 text-amber-700"><i class="ti ti-star-filled" aria-hidden="true" />{{ review.rating }}/5</span><span v-if="review.title">· {{ review.title }}</span></p>
                            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ review.review }}</p>
                        </article>
                    </div>
                </section>
            </template>
        </div>
    </section>
</template>
