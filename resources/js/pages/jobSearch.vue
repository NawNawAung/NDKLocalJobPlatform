<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const regions = bootstrap.regions ?? [];
const params = new URLSearchParams(window.location.search);
const filters = reactive({
    keyword: params.get('keyword') ?? '',
    region_id: params.get('region_id') ?? '',
    township_id: params.get('township_id') ?? '',
    category: params.get('category') ?? '',
    experience_level: params.get('experience_level') ?? '',
    employment_type: params.get('employment_type') ?? '',
    work_mode: params.get('work_mode') ?? '',
    salary_min: params.get('salary_min') ?? '',
    salary_max: params.get('salary_max') ?? '',
    date_posted: params.get('date_posted') ?? '',
    sort: params.get('sort') ?? 'latest',
});
const townships = computed(() => regions.find((region) => String(region.id) === String(filters.region_id))?.townships ?? []);
const jobs = ref([]);
const total = ref(0);
const page = ref(Number(params.get('page') ?? 1));
const lastPage = ref(1);
const loading = ref(false);
const errorMessage = ref('');

function queryString(pageNumber = 1) {
    const query = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && !(key === 'sort' && value === 'latest')) query.set(key, value);
    });
    if (pageNumber > 1) query.set('page', String(pageNumber));
    return query.toString();
}

async function loadJobs(pageNumber = 1) {
    loading.value = true;
    errorMessage.value = '';
    page.value = pageNumber;
    const query = queryString(pageNumber);
    window.history.replaceState({}, '', `/jobs${query ? `?${query}` : ''}`);

    try {
        const response = await fetch(`/api/jobs/search${query ? `?${query}` : ''}`, {
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            const failure = await response.json().catch(() => ({}));
            const validationMessage = Object.values(failure.errors ?? {})[0]?.[0];
            throw new Error(validationMessage ?? 'Could not load job results. Please try again.');
        }
        const result = await response.json();
        jobs.value = result.data ?? [];
        total.value = result.meta?.total ?? 0;
        lastPage.value = result.meta?.last_page ?? 1;
    } catch (error) {
        errorMessage.value = error.message;
    } finally {
        loading.value = false;
    }
}

function resetFilters() {
    Object.assign(filters, {
        keyword: '', region_id: '', township_id: '', category: '', experience_level: '',
        employment_type: '', work_mode: '', salary_min: '', salary_max: '', date_posted: '', sort: 'latest',
    });
    loadJobs();
}

function formatLabel(value) {
    if (!value) return '';
    return value.split('_').map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
}

function applicationStatusLabel(status) {
    if (!status) return '';
    return status === 'submitted' ? 'Applied' : formatLabel(status);
}

function applicationStatusClass(status) {
    if (status === 'rejected') return 'bg-red-50 text-red-800';
    if (['offered', 'hired'].includes(status)) return 'bg-emerald-50 text-emerald-800';
    if (status === 'withdrawn') return 'bg-slate-100 text-slate-700';
    return 'bg-blue-50 text-blue-900';
}

function formatSalary(job) {
    if (job.salary_min == null && job.salary_max == null) return 'Salary not disclosed';
    const currency = job.salary_currency ?? 'MMK';
    const amount = (value) => new Intl.NumberFormat('en-US').format(value);
    if (job.salary_min != null && job.salary_max != null) return `${amount(job.salary_min)} – ${amount(job.salary_max)} ${currency}`;
    return job.salary_min != null ? `From ${amount(job.salary_min)} ${currency}` : `Up to ${amount(job.salary_max)} ${currency}`;
}

function postedLabel(value) {
    if (!value) return 'Recently posted';
    const days = Math.floor((Date.now() - new Date(value).getTime()) / 86400000);
    if (days <= 0) return 'Today';
    if (days === 1) return '1 day ago';
    if (days < 7) return `${days} days ago`;
    if (days < 30) return `${Math.floor(days / 7)} week${days < 14 ? '' : 's'} ago`;
    return new Date(value).toLocaleDateString();
}

function openJob(job) {
    window.location.hash = `details?job=${job.id}`;
}

onMounted(() => loadJobs(page.value));
</script>

<template>
    <section class="min-h-[65vh] bg-slate-50 px-5 py-8 sm:px-8 lg:py-12">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7">
                <p class="text-sm font-semibold text-[var(--brand-primary)]">OPPORTUNITIES ACROSS MYANMAR</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Find your next role</h1>
                <p class="mt-2 text-sm text-slate-600">Use the filters to narrow the results to the work you want.</p>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[280px_minmax(0,1fr)]">
                <aside class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-5 flex items-center justify-between">
                        <h2 class="font-semibold text-slate-900">Filters</h2>
                        <button type="button" class="text-sm font-medium text-[var(--brand-primary)] hover:underline" @click="resetFilters">Clear all</button>
                    </div>

                    <form class="space-y-4" @submit.prevent="loadJobs()">
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Job title or keyword</span>
                            <input v-model="filters.keyword" type="search" placeholder="Title, skills, company" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Region or state</span>
                            <select v-model="filters.region_id" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" @change="filters.township_id = ''">
                                <option value="">All Myanmar</option>
                                <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }} {{ region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region' }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Township</span>
                            <select v-model="filters.township_id" :disabled="!filters.region_id || townships.length === 0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400">
                                <option value="">Any township</option>
                                <option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Role or category</span>
                            <input v-model="filters.category" type="search" placeholder="e.g. Technology, Finance" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Experience</span>
                            <select v-model="filters.experience_level" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                                <option value="">Any experience</option><option value="entry">Entry level (0–1 years)</option><option value="junior">Junior (1–3 years)</option><option value="mid">Mid level (3–5 years)</option><option value="senior">Senior (5+ years)</option><option value="lead">Lead / manager</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Employment type</span>
                            <select v-model="filters.employment_type" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                                <option value="">Any type</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="internship">Internship</option><option value="temporary">Temporary</option>
                            </select>
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Work arrangement</span>
                            <select v-model="filters.work_mode" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                                <option value="">Any arrangement</option><option value="on_site">On site</option><option value="hybrid">Hybrid</option><option value="remote">Remote</option>
                            </select>
                        </label>
                        <div>
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Monthly salary (MMK)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <input v-model="filters.salary_min" type="number" min="0" step="50000" placeholder="Minimum" aria-label="Minimum salary" class="min-w-0 rounded-lg border border-slate-300 px-2.5 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                                <input v-model="filters.salary_max" type="number" min="0" step="50000" placeholder="Maximum" aria-label="Maximum salary" class="min-w-0 rounded-lg border border-slate-300 px-2.5 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                            </div>
                        </div>
                        <label class="block">
                            <span class="mb-1.5 block text-sm font-medium text-slate-700">Date posted</span>
                            <select v-model="filters.date_posted" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                                <option value="">Any time</option><option value="24h">Past 24 hours</option><option value="7d">Past week</option><option value="30d">Past month</option>
                            </select>
                        </label>
                        <button type="submit" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]">Apply filters</button>
                    </form>
                </aside>

                <div>
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-sm text-slate-600"><span class="font-semibold text-slate-900">{{ total }}</span> {{ total === 1 ? 'job' : 'jobs' }} found</p>
                        <label class="flex items-center gap-2 text-sm text-slate-600">
                            Sort by
                            <select v-model="filters.sort" class="rounded-md border border-slate-300 bg-white px-2.5 py-2 font-medium text-slate-800 outline-none focus:border-[var(--brand-primary)]" @change="loadJobs()">
                                <option value="latest">Most recent</option><option value="salary_high">Highest salary</option><option value="salary_low">Lowest salary</option>
                            </select>
                        </label>
                    </div>

                    <div v-if="errorMessage" class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800" role="alert">{{ errorMessage }}</div>
                    <div v-else-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600" role="status">Loading jobs…</div>
                    <div v-else-if="jobs.length" class="space-y-3">
                        <article v-for="job in jobs" :key="job.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-blue-200 hover:shadow-md sm:p-6">
                            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-[var(--brand-primary)]">{{ job.company }}</p>
                                    <h2 class="mt-1 text-lg font-bold text-[var(--brand-ink)]">{{ job.title }}</h2>
                                    <p class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-slate-600">
                                        <span>{{ job.location }}</span><span aria-hidden="true">·</span><span>{{ formatLabel(job.employment_type) }}</span><span v-if="job.work_mode" aria-hidden="true">·</span><span v-if="job.work_mode">{{ formatLabel(job.work_mode) }}</span>
                                    </p>
                                </div>
                                <p class="shrink-0 text-sm font-semibold text-slate-800">{{ formatSalary(job) }}</p>
                            </div>
                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                <span v-if="job.category" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-800">{{ job.category }}</span>
                                <span v-if="job.experience_level" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ formatLabel(job.experience_level) }} experience</span>
                                <span v-if="job.application_status" class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="applicationStatusClass(job.application_status)">Application: {{ applicationStatusLabel(job.application_status) }}</span>
                                <span class="ml-auto text-xs text-slate-500">{{ postedLabel(job.posted_at) }}</span>
                                <button type="button" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]" @click="openJob(job)">{{ job.application_status ? 'View application' : 'View and apply' }}</button>
                            </div>
                        </article>
                    </div>
                    <div v-else class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">
                        <i class="ti ti-briefcase-2 text-4xl text-slate-300" aria-hidden="true" />
                        <h2 class="mt-3 text-lg font-semibold text-slate-900">No jobs match these filters</h2>
                        <p class="mt-1 text-sm text-slate-600">Try a broader keyword or clear some filters.</p>
                        <button type="button" class="mt-4 text-sm font-semibold text-[var(--brand-primary)] hover:underline" @click="resetFilters">Clear filters</button>
                    </div>

                    <div v-if="lastPage > 1" class="mt-5 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <button type="button" :disabled="page <= 1 || loading" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" @click="loadJobs(page - 1)">Previous</button>
                        <span class="text-sm text-slate-600">Page {{ page }} of {{ lastPage }}</span>
                        <button type="button" :disabled="page >= lastPage || loading" class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 disabled:cursor-not-allowed disabled:opacity-40" @click="loadJobs(page + 1)">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
