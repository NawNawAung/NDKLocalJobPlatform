<script setup>
import { computed, onMounted, ref } from 'vue';

const keyword = ref('');
const regionId = ref('');
const townshipId = ref('');
const categoryId = ref('');
const categories = ref([]);
const experienceLevel = ref('');
const employmentType = ref('');
const workMode = ref('');
const salaryMin = ref('');
const salaryMax = ref('');
const datePosted = ref('');
const advancedFiltersOpen = ref(false);
const featuredJobs = ref([]);
const featuredLoading = ref(true);
const featuredError = ref('');
const companies = ref([]);
const companiesLoading = ref(true);
const companiesError = ref('');
const sponsoredCompanies = ref([]);
const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const isEmployer = bootstrap.role === 'employer';
const regions = bootstrap.regions ?? [];
const townships = computed(() => regions.find((region) => String(region.id) === regionId.value)?.townships ?? []);

function submitSearch() {
    const params = new URLSearchParams();
    const filters = {
        keyword: keyword.value.trim(),
        region_id: regionId.value,
        township_id: townshipId.value,
        category_id: categoryId.value,
        experience_level: experienceLevel.value,
        employment_type: employmentType.value,
        work_mode: workMode.value,
        salary_min: salaryMin.value,
        salary_max: salaryMax.value,
        date_posted: datePosted.value,
    };
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '') params.set(key, value);
    });
    window.location.href = `/jobs${params.size ? `?${params.toString()}` : ''}`;
}

function usePopularSearch(term) {
    const region = regions.find((item) => item.name.toLowerCase() === term.toLowerCase());
    if (region) {
        regionId.value = String(region.id);
        townshipId.value = '';
    } else {
        keyword.value = term;
    }
    submitSearch();
}

function searchByCategory(value) {
    const match = categories.value.find((item) => item.name.toLowerCase().includes(value.toLowerCase()));
    if (match) categoryId.value = String(match.id);
    else keyword.value = value;
    submitSearch();
}
function formatLabel(value) {
    return value ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : '';
}

function formatSalary(job) {
    if (job.salary_min == null && job.salary_max == null) return 'Salary not disclosed';
    const amount = (value) => new Intl.NumberFormat('en-US').format(value);
    if (job.salary_min != null && job.salary_max != null) return `${amount(job.salary_min)} – ${amount(job.salary_max)} ${job.salary_currency ?? 'MMK'}`;
    return job.salary_min != null ? `From ${amount(job.salary_min)} ${job.salary_currency ?? 'MMK'}` : `Up to ${amount(job.salary_max)} ${job.salary_currency ?? 'MMK'}`;
}

function postedLabel(value) {
    if (!value) return 'Recently posted';
    const days = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 86400000));
    return days === 0 ? 'Today' : days === 1 ? '1 day ago' : days < 7 ? `${days} days ago` : `${Math.floor(days / 7)} weeks ago`;
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

onMounted(async () => {
    const [jobsResult, categoriesResult, companiesResult, companiesAdsResult] = await Promise.allSettled([
        window.axios.get('/api/jobs/search'),
        window.axios.get('/api/job-categories'),
        window.axios.get('/api/companies', { params: { page: 1, hiring_only: 1, exclude_sponsored: 1 } }),
        window.axios.get('/api/advertisements/companies', { params: { limit: 4 } }),
    ]);

    if (jobsResult.status === 'fulfilled') {
        featuredJobs.value = (jobsResult.value.data.data ?? []).filter(job => job.is_featured).slice(0, 3);
    } else {
        featuredError.value = 'Featured jobs are unavailable right now.';
    }
    if (categoriesResult.status === 'fulfilled') categories.value = categoriesResult.value.data.categories ?? [];
    if (companiesResult.status === 'fulfilled') {
        companies.value = companiesResult.value.data.companies?.data?.slice(0, 4) ?? [];
    } else {
        companiesError.value = 'Company information is unavailable right now.';
    }
    if (companiesAdsResult.status === 'fulfilled') sponsoredCompanies.value = companiesAdsResult.value.data.data ?? [];
    featuredLoading.value = false;
    companiesLoading.value = false;
});
</script>

<template>
      <div class="bg-white min-h-screen">
        <div class="bg-gradient-to-br from-blue-50 via-white to-slate-100 px-5 py-16 sm:px-8 sm:py-20">
          <div class="mx-auto max-w-5xl text-center">
            <div class="mb-3 text-4xl font-extrabold tracking-tight text-[var(--brand-ink)] leading-tight sm:text-5xl">{{ isEmployer ? $t('Explore Myanmar’s Job Market') : $t('Find Your Next Opportunity in Myanmar') }}</div>
            <div class="mx-auto mb-8 max-w-2xl text-base leading-6 text-slate-600">{{ isEmployer ? $t('Review public listings from other employers to understand current roles, locations, and compensation.') : $t('Search open roles by title, location, experience, work arrangement, and salary.') }}</div>
            <form class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-lg shadow-blue-900/5 sm:p-6" @submit.prevent="submitSearch">
              <div class="grid gap-4 md:grid-cols-[1.25fr_1fr_1fr_auto]">
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Job title or keyword') }}</span>
                  <span class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-3 focus-within:border-[var(--brand-primary)] focus-within:ring-4 focus-within:ring-blue-100">
                    <i class="ti ti-search text-slate-400" aria-hidden="true" />
                    <input v-model="keyword" type="search" class="w-full text-sm text-slate-800 outline-none" :placeholder="$t('e.g. Accountant, Python, sales')" />
                  </span>
                </label>
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Region or state') }}</span>
                  <select v-model="regionId" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-800 outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" @change="townshipId = ''">
                    <option value="">{{ $t('All Myanmar') }}</option>
                    <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }} {{ $t(region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region') }}</option>
                  </select>
                </label>
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Township') }}</span>
                  <select v-model="townshipId" :disabled="!regionId || townships.length === 0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-800 outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400">
                    <option value="">{{ $t('Any township') }}</option>
                    <option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option>
                  </select>
                </label>
                <button type="submit" class="self-end rounded-lg bg-[var(--brand-primary)] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">{{ $t('Search jobs') }}</button>
              </div>

              <div class="mt-4 border-t border-slate-100 pt-4">
                <button type="button" class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)]" :aria-expanded="advancedFiltersOpen" @click="advancedFiltersOpen = !advancedFiltersOpen">
                  <i class="ti" :class="advancedFiltersOpen ? 'ti-adjustments-x' : 'ti-adjustments-horizontal'" aria-hidden="true" />
                  {{ advancedFiltersOpen ? $t('Hide filters') : $t('More filters') }}
                  <i class="ti" :class="advancedFiltersOpen ? 'ti-chevron-up' : 'ti-chevron-down'" aria-hidden="true" />
                </button>
                <div v-if="advancedFiltersOpen" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Job category') }}</span>
                    <select v-model="categoryId" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100"><option value="">{{ $t('All categories') }}</option><option v-for="item in categories" :key="item.id" :value="String(item.id)">{{ item.name }}</option></select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Experience') }}</span>
                    <select v-model="experienceLevel" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">{{ $t('Any experience') }}</option><option value="entry">{{ $t('Entry level (0–1 years)') }}</option><option value="junior">{{ $t('Junior (1–3 years)') }}</option><option value="mid">{{ $t('Mid level (3–5 years)') }}</option><option value="senior">{{ $t('Senior (5+ years)') }}</option><option value="lead">{{ $t('Lead / manager') }}</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Employment type') }}</span>
                    <select v-model="employmentType" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">{{ $t('Any employment type') }}</option><option value="full_time">{{ $t('Full time') }}</option><option value="part_time">{{ $t('Part time') }}</option><option value="contract">{{ $t('Contract') }}</option><option value="internship">{{ $t('Internship') }}</option><option value="temporary">{{ $t('Temporary') }}</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Work arrangement') }}</span>
                    <select v-model="workMode" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">{{ $t('Any work arrangement') }}</option><option value="on_site">{{ $t('On site') }}</option><option value="hybrid">{{ $t('Hybrid') }}</option><option value="remote">{{ $t('Remote') }}</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Minimum monthly salary (MMK)') }}</span>
                    <input v-model="salaryMin" type="number" min="0" step="50000" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" :placeholder="$t('No minimum')">
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Maximum monthly salary (MMK)') }}</span>
                    <input v-model="salaryMax" type="number" min="0" step="50000" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" :placeholder="$t('No maximum')">
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Date posted') }}</span>
                    <select v-model="datePosted" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">{{ $t('Any time') }}</option><option value="24h">{{ $t('Past 24 hours') }}</option><option value="7d">{{ $t('Past week') }}</option><option value="30d">{{ $t('Past month') }}</option>
                    </select>
                  </label>
                </div>
              </div>
            </form>
            <div class="flex flex-wrap gap-2 justify-center items-center mt-5 max-sm:gap-1.5">
              <span class="text-sm font-medium text-slate-500">Popular Searches:</span>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Yangon')">Yangon</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Mandalay')">Mandalay</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('KBZ Group')">KBZ Group</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Wave Money')">Wave Money</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Developer')">Developer</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Sales')">Sales</button>
              <button type="button" class="px-3 py-1.5 text-sm text-gray-700 bg-white rounded-3xl border border-gray-300 border-solid cursor-pointer" @click="usePopularSearch('Banking')">Banking</button>
            </div>
          </div>
        </div>
        <section v-if="sponsoredCompanies.length" class="bg-blue-50/70 px-5 py-10 sm:px-8 sm:py-12 lg:px-12 xl:px-20" aria-labelledby="sponsored-companies-heading">
          <div class="mb-5 flex flex-wrap items-end justify-between gap-3"><div><span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold uppercase text-blue-900"><i class="ti ti-ad" aria-hidden="true"/>{{ $t('Sponsored') }}</span><h2 id="sponsored-companies-heading" class="mt-2 text-2xl font-bold text-[var(--brand-ink)]">{{ $t('Sponsored companies') }}</h2></div><p class="text-xs text-slate-500">{{ $t('Advertisement') }}</p></div>
          <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a v-for="item in sponsoredCompanies" :key="item.campaign_id" :href="`/companies/${item.company.id}`" class="group flex min-w-0 items-start gap-3 rounded-xl border border-blue-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-50 text-2xl text-blue-800"><i class="ti ti-building" aria-hidden="true"/></span><span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900 group-hover:text-blue-800"><span class="break-words">{{ item.company.name }}</span><span class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px] font-bold uppercase text-blue-900">{{ $t('Sponsored') }}</span></span><span class="mt-1 block text-sm text-slate-600">{{ item.company.open_jobs_count }} {{ $t('open jobs') }}</span><span v-if="item.company.location" class="mt-1 block truncate text-xs text-slate-500">{{ item.company.location }}</span></span></a>
          </div>
        </section>
        <section class="px-5 py-12 sm:px-8 sm:py-14 lg:px-12 xl:px-20" aria-labelledby="top-companies-heading">
          <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div><p class="text-sm font-semibold text-[var(--brand-primary)]">{{ $t('Meet employers') }}</p><h2 id="top-companies-heading" class="mt-1 text-2xl font-bold leading-8 text-gray-900">{{ $t('Companies hiring') }}</h2></div>
            <a href="/companies" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:underline">{{ $t('Explore all companies') }}<i class="ti ti-arrow-right" aria-hidden="true" /></a>
          </div>
          <p v-if="companiesError" class="rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $t(companiesError) }}</p>
          <div v-else-if="companiesLoading" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-600" role="status">{{ $t('Loading companies…') }}</div>
          <div v-else-if="companies.length" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <a v-for="company in companies" :key="company.id" :href="`/companies/${company.id}`" class="group flex min-w-0 items-start gap-3 rounded-xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
              <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-blue-50 text-2xl text-blue-800"><i class="ti ti-building" aria-hidden="true" /></span>
              <span class="min-w-0 flex-1"><span class="flex flex-wrap items-center gap-1.5 font-semibold text-slate-900 group-hover:text-blue-800"><span class="break-words">{{ company.name }}</span><i v-if="company.is_verified" class="ti ti-circle-check text-emerald-700" :aria-label="$t('Verified employer')" /></span><span class="mt-1 block text-sm text-slate-500">{{ company.open_jobs_count }} {{ $t('open jobs') }}</span><span v-if="company.location" class="mt-1 block truncate text-xs text-slate-500">{{ company.location }}</span></span>
            </a>
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center text-sm text-slate-600">{{ $t('No companies found') }} <a href="/companies" class="font-semibold text-blue-800 hover:underline">{{ $t('Browse companies') }}</a></div>
        </section>
        <div class="px-20 pb-16 max-sm:px-4 max-sm:pb-10">
          <div class="mb-6 text-2xl font-bold leading-8 text-gray-900">Popular Job Categories</div>
          <div class="grid grid-cols-4 gap-5 max-md:grid-cols-2 max-sm:grid-cols-1">
            <button type="button" class="flex flex-col gap-3 p-6 rounded-xl text-left cursor-pointer bg-slate-400" @click="searchByCategory('Technology')">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-blue-100">
                <img :src="'/images/categories/technology.svg'" alt="" class="h-6 w-6 object-contain" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Technology</div>
              <div class="text-sm leading-5 text-white text-opacity-80">340+ Jobs</div>
            </button>
            <button type="button" class="flex flex-col gap-3 p-6 rounded-xl text-left cursor-pointer bg-slate-400" @click="searchByCategory('Finance')">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-blue-100">
                <img :src="'/images/categories/finance.svg'" alt="" class="h-6 w-6 object-contain" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Finance &amp; Banking</div>
              <div class="text-sm leading-5 text-white text-opacity-80">210+ Jobs</div>
            </button>
            <button type="button" class="flex flex-col gap-3 p-6 rounded-xl text-left cursor-pointer bg-slate-400" @click="searchByCategory('Engineering')">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-blue-100">
                <img :src="'/images/categories/engineering.svg'" alt="" class="h-6 w-6 object-contain" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Engineering</div>
              <div class="text-sm leading-5 text-white text-opacity-80">180+ Jobs</div>
            </button>
            <button type="button" class="flex flex-col gap-3 p-6 rounded-xl text-left cursor-pointer bg-slate-400" @click="searchByCategory('Administration')">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-blue-100">
                <img :src="'/images/categories/administration.svg'" alt="" class="h-6 w-6 object-contain" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Administration</div>
              <div class="text-sm leading-5 text-white text-opacity-80">150+ Jobs</div>
            </button>
          </div>
        </div>
        <div class="px-20 pb-16 max-sm:px-4 max-sm:pb-10">
          <div class="flex justify-between items-center mb-6">
            <div class="text-2xl font-bold leading-8 text-gray-900">{{ $t('Sponsored Jobs') }}</div>
            <a href="#search" class="flex gap-1.5 items-center">
              <span class="text-sm font-medium text-blue-500">View All Jobs</span>
              <i class="ti ti-arrow-right text-base text-blue-500" />
            </a>
          </div>
          <p v-if="featuredError" class="mb-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ featuredError }} <a href="/jobs" class="font-semibold underline">Browse all jobs</a></p>
          <div v-else-if="featuredLoading" class="rounded-xl border border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-600">{{ $t('Loading featured jobs…') }}</div>
          <div v-else-if="featuredJobs.length" class="flex flex-col gap-4">
            <article v-for="job in featuredJobs" :key="job.id" class="flex flex-wrap items-center gap-4 rounded-xl border border-slate-200 p-5 transition hover:border-blue-200 hover:shadow-sm">
              <div class="grid h-14 w-14 shrink-0 place-items-center rounded-lg bg-blue-50 text-lg font-bold text-blue-900">{{ job.company?.slice(0, 2)?.toUpperCase() || 'CO' }}</div>
              <div class="min-w-0 flex-1">
                <div class="mb-1 flex flex-wrap items-center gap-2.5"><span class="text-base font-semibold leading-6 text-slate-900">{{ job.title }}</span><span class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-bold text-blue-900"><i class="ti ti-ad" aria-hidden="true"/>{{ $t('Sponsored') }}</span><span class="rounded bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-800">{{ formatLabel(job.employment_type) }}</span><span v-if="!isEmployer && job.application_status" class="rounded px-2 py-0.5 text-xs font-semibold" :class="applicationStatusClass(job.application_status)">Application: {{ applicationStatusLabel(job.application_status) }}</span></div>
              <div class="flex flex-wrap items-center gap-2 text-sm"><a :href="`/companies/${job.company_id}`" class="font-medium text-blue-800 hover:underline">{{ job.company }}</a><span class="text-slate-400">·</span><span class="text-slate-600">{{ job.location }}</span><span class="text-slate-400">·</span><span class="text-slate-600">{{ formatSalary(job) }}</span></div>
              </div>
              <div class="flex shrink-0 items-center gap-3"><span class="text-xs text-slate-500">{{ postedLabel(job.posted_at) }}</span><a :href="`/#details?job=${job.id}`" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]">{{ isEmployer ? 'View job details' : job.application_status ? 'View application' : 'View and apply' }}</a></div>
            </article>
          </div>
          <div v-else class="rounded-xl border border-dashed border-slate-300 p-8 text-center"><p class="text-sm text-slate-600">{{ $t('There are no featured jobs right now.') }}</p><a href="/jobs" class="mt-3 inline-flex font-semibold text-[var(--brand-primary)] hover:underline">{{ $t('Browse all jobs') }}</a></div>
        </div>
      </div>
</template>
