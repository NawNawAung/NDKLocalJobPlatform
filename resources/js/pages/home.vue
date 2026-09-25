<script setup>
import { computed, ref } from 'vue';

const keyword = ref('');
const regionId = ref('');
const townshipId = ref('');
const category = ref('');
const experienceLevel = ref('');
const employmentType = ref('');
const workMode = ref('');
const salaryMin = ref('');
const salaryMax = ref('');
const datePosted = ref('');
const advancedFiltersOpen = ref(false);
const mobileMenuOpen = ref(false);
const savedJobs = ref([]);
const regions = window.__AUTH_BOOTSTRAP__?.regions ?? [];
const townships = computed(() => regions.find((region) => String(region.id) === regionId.value)?.townships ?? []);

function submitSearch() {
    const params = new URLSearchParams();
    const filters = {
        keyword: keyword.value.trim(),
        region_id: regionId.value,
        township_id: townshipId.value,
        category: category.value.trim(),
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
    category.value = value;
    submitSearch();
}
function toggleSavedJob(title) {
    savedJobs.value = savedJobs.value.includes(title) ? savedJobs.value.filter((item) => item !== title) : [...savedJobs.value, title];
}
</script>

<template>
      <div class="bg-white min-h-screen">
        <header class="bg-white border-b">
          <nav class="flex justify-between items-center px-8 py-0 h-16 max-sm:px-4" aria-label="Main navigation">
          <div class="flex gap-2 items-center">
            <div class="flex gap-1.5 items-center">
              <div class="w-2 h-2 bg-gray-500 rounded-full" />
              <span class="text-lg font-bold tracking-normal leading-6 text-gray-900">NDK</span>
              <span class="px-1.5 py-0.5 text-xs font-semibold tracking-wider text-blue-500 uppercase bg-blue-100 rounded">MYANMAR</span>
            </div>
          </div>
          <div class="flex gap-8 items-center max-sm:hidden">
            <div class="flex gap-8 items-center">
              <span class="pt-6 pb-5 text-sm font-semibold leading-5 text-gray-900 border-2 border-blue-500 cursor-pointer">Jobs</span>
              <span class="text-sm leading-5 text-gray-500 cursor-pointer">Companies</span>
              <span class="text-sm leading-5 text-gray-500 cursor-pointer">Resources</span>
            </div>
          </div>
          <div class="flex gap-3 items-center">
            <div class="flex gap-3 items-center max-sm:hidden">
              <i class="ti ti-search text-xl text-gray-500 cursor-pointer" />
              <div class="flex gap-1.5 items-center px-3 py-1.5 bg-blue-50 rounded-3xl border border-blue-200 border-solid cursor-pointer">
                <div class="w-4 h-4 bg-blue-500 rounded-full" />
                <span class="text-sm font-medium text-blue-500">Recruiter Mode</span>
              </div>
              <span class="text-sm font-medium text-gray-700 cursor-pointer">Sign In</span>
              <div class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg cursor-pointer">Post a Job</div>
            </div>
            <button type="button" class="hidden items-center max-sm:flex" :aria-expanded="mobileMenuOpen" aria-label="Toggle navigation menu" @click="mobileMenuOpen = !mobileMenuOpen"><i class="ti ti-menu-2 text-2xl text-gray-700" /></button>
          </div>
          </nav>
          <div v-if="mobileMenuOpen" class="hidden max-sm:flex flex-col gap-3 border-t px-4 py-4 text-sm"><a href="#search" @click="mobileMenuOpen = false">Jobs</a><a href="#companies" @click="mobileMenuOpen = false">Companies</a><a href="#resources" @click="mobileMenuOpen = false">Resources</a><a href="#sign-in" @click="mobileMenuOpen = false">Sign In</a><a href="#post-job" @click="mobileMenuOpen = false">Post a Job</a></div>
        </header>
        <div class="bg-gradient-to-br from-blue-50 via-white to-slate-100 px-5 py-16 sm:px-8 sm:py-20">
          <div class="mx-auto max-w-5xl text-center">
            <div class="mb-3 text-4xl font-extrabold tracking-tight text-[var(--brand-ink)] leading-tight sm:text-5xl">Find Your Next Opportunity in Myanmar</div>
            <div class="mx-auto mb-8 max-w-2xl text-base leading-6 text-slate-600">Search open roles by title, location, experience, work arrangement, and salary.</div>
            <form class="rounded-2xl border border-slate-200 bg-white p-4 text-left shadow-lg shadow-blue-900/5 sm:p-6" @submit.prevent="submitSearch">
              <div class="grid gap-4 md:grid-cols-[1.25fr_1fr_1fr_auto]">
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Job title or keyword</span>
                  <span class="flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-3 focus-within:border-[var(--brand-primary)] focus-within:ring-4 focus-within:ring-blue-100">
                    <i class="ti ti-search text-slate-400" aria-hidden="true" />
                    <input v-model="keyword" type="search" class="w-full text-sm text-slate-800 outline-none" placeholder="e.g. Accountant, Python, sales" />
                  </span>
                </label>
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Region or state</span>
                  <select v-model="regionId" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-800 outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" @change="townshipId = ''">
                    <option value="">All Myanmar</option>
                    <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }} {{ region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region' }}</option>
                  </select>
                </label>
                <label class="block">
                  <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Township</span>
                  <select v-model="townshipId" :disabled="!regionId || townships.length === 0" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-800 outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400">
                    <option value="">Any township</option>
                    <option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option>
                  </select>
                </label>
                <button type="submit" class="self-end rounded-lg bg-[var(--brand-primary)] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">Search jobs</button>
              </div>

              <div class="mt-4 border-t border-slate-100 pt-4">
                <button type="button" class="inline-flex items-center gap-2 text-sm font-semibold text-[var(--brand-primary)] hover:text-[var(--brand-primary-hover)]" :aria-expanded="advancedFiltersOpen" @click="advancedFiltersOpen = !advancedFiltersOpen">
                  <i class="ti" :class="advancedFiltersOpen ? 'ti-adjustments-x' : 'ti-adjustments-horizontal'" aria-hidden="true" />
                  {{ advancedFiltersOpen ? 'Hide filters' : 'More filters' }}
                  <i class="ti" :class="advancedFiltersOpen ? 'ti-chevron-up' : 'ti-chevron-down'" aria-hidden="true" />
                </button>
                <div v-if="advancedFiltersOpen" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Role or category</span>
                    <input v-model="category" type="search" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="e.g. Technology, Finance">
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Experience</span>
                    <select v-model="experienceLevel" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">Any experience</option><option value="entry">Entry level (0–1 years)</option><option value="junior">Junior (1–3 years)</option><option value="mid">Mid level (3–5 years)</option><option value="senior">Senior (5+ years)</option><option value="lead">Lead / manager</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Employment type</span>
                    <select v-model="employmentType" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">Any employment type</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="internship">Internship</option><option value="temporary">Temporary</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Work arrangement</span>
                    <select v-model="workMode" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">Any work arrangement</option><option value="on_site">On site</option><option value="hybrid">Hybrid</option><option value="remote">Remote</option>
                    </select>
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Minimum monthly salary (MMK)</span>
                    <input v-model="salaryMin" type="number" min="0" step="50000" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="No minimum">
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Maximum monthly salary (MMK)</span>
                    <input v-model="salaryMax" type="number" min="0" step="50000" inputmode="numeric" class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="No maximum">
                  </label>
                  <label class="block">
                    <span class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-slate-500">Date posted</span>
                    <select v-model="datePosted" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                      <option value="">Any time</option><option value="24h">Past 24 hours</option><option value="7d">Past week</option><option value="30d">Past month</option>
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
        <div class="px-20 py-16 max-sm:px-4 max-sm:py-10">
          <div class="mb-6 text-2xl font-bold leading-8 text-gray-900">Top Hiring Companies</div>
          <div class="grid grid-cols-4 gap-5 max-md:grid-cols-2 max-sm:grid-cols-1">
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" :src="'/images/companies/kbzLogo.png'" alt="KBZ Group logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">KBZ Group</div>
                <div class="text-sm leading-5 text-gray-500">42 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" :src="'/images/companies/grabLogo.webp'" alt="Grab Myanmar logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">Grab Myanmar</div>
                <div class="text-sm leading-5 text-gray-500">18 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" :src="'/images/companies/waveLogo.jpg'" alt="Wave Money logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">Wave Money</div>
                <div class="text-sm leading-5 text-gray-500">12 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" :src="'/images/companies/cdsgLogo.png'" alt="CDSG Group logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">CDSG Group</div>
                <div class="text-sm leading-5 text-gray-500">25 Open Jobs</div>
              </div>
            </div>
          </div>
        </div>
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
            <div class="text-2xl font-bold leading-8 text-gray-900">Recommended For You</div>
            <a href="#search" class="flex gap-1.5 items-center">
              <span class="text-sm font-medium text-blue-500">View All Jobs</span>
              <i class="ti ti-arrow-right text-base text-blue-500" />
            </a>
          </div>
          <div class="flex flex-col gap-4">
            <div class="flex gap-4 items-center p-5 rounded-xl border border border-solid max-sm:flex-col max-sm:items-start">
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" :src="'/images/companies/kbzLogo.png'" alt="KBZ Group logo" />
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2.5 items-center mb-1">
                  <span class="text-base font-semibold leading-6 text-gray-900">Senior Developer</span>
                  <span class="px-2 py-0.5 text-xs font-medium text-blue-500 bg-blue-100 rounded">Full-time</span>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                  <span class="text-sm font-medium text-blue-500">KBZ Group</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">Yangon</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">1,500,000 - 1,800,000 MMK</span>
                </div>
              </div>
              <div class="flex gap-3 items-center flex-[shrink] max-sm:justify-between max-sm:w-full">
                <span class="text-sm text-gray-400">2 days ago</span>
                <button type="button" class="flex justify-center items-center w-9 h-9 rounded-lg border border border-solid" :aria-pressed="savedJobs.includes('Senior Developer')" aria-label="Save Senior Developer" @click="toggleSavedJob('Senior Developer')"><i class="ti ti-bookmark text-base" :class="savedJobs.includes('Senior Developer') ? 'text-blue-600' : 'text-gray-500'" /></button>
                <a href="#details" class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg">Apply Now</a>
              </div>
            </div>
            <div class="flex gap-4 items-center p-5 rounded-xl border border border-solid max-sm:flex-col max-sm:items-start">
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" :src="'/images/companies/grabLogo.webp'" alt="Grab Myanmar logo" />
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2.5 items-center mb-1">
                  <span class="text-base font-semibold leading-6 text-gray-900">Operations Executive</span>
                  <span class="px-2 py-0.5 text-xs font-medium text-blue-500 bg-blue-100 rounded">Full-time</span>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                  <span class="text-sm font-medium text-blue-500">Grab Myanmar</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">Mandalay</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">800,000 - 1,200,000 MMK</span>
                </div>
              </div>
              <div class="flex gap-3 items-center flex-[shrink] max-sm:justify-between max-sm:w-full">
                <span class="text-sm text-gray-400">1 week ago</span>
                <button type="button" class="flex justify-center items-center w-9 h-9 rounded-lg border border border-solid" :aria-pressed="savedJobs.includes('Operations Executive')" aria-label="Save Operations Executive" @click="toggleSavedJob('Operations Executive')"><i class="ti ti-bookmark text-base" :class="savedJobs.includes('Operations Executive') ? 'text-blue-600' : 'text-gray-500'" /></button>
                <a href="#details" class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg">Apply Now</a>
              </div>
            </div>
            <div class="flex gap-4 items-center p-5 rounded-xl border border border-solid max-sm:flex-col max-sm:items-start">
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" :src="'/images/companies/waveLogo.jpg'" alt="Wave Money logo" />
              <div class="flex-1 min-w-0">
                <div class="flex flex-wrap gap-2.5 items-center mb-1">
                  <span class="text-base font-semibold leading-6 text-gray-900">Customer Support Officer</span>
                  <span class="px-2 py-0.5 text-xs font-medium text-gray-700 bg-gray-100 rounded">Contract</span>
                </div>
                <div class="flex flex-wrap gap-2 items-center">
                  <span class="text-sm font-medium text-blue-500">Wave Money</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">Yangon</span>
                  <span class="text-sm text-gray-400">·</span>
                  <span class="text-sm text-gray-500">450,000 - 600,000 MMK</span>
                </div>
              </div>
              <div class="flex gap-3 items-center flex-[shrink] max-sm:justify-between max-sm:w-full">
                <span class="text-sm text-gray-400">Just now</span>
                <button type="button" class="flex justify-center items-center w-9 h-9 rounded-lg border border border-solid" :aria-pressed="savedJobs.includes('Customer Support Officer')" aria-label="Save Customer Support Officer" @click="toggleSavedJob('Customer Support Officer')"><i class="ti ti-bookmark text-base" :class="savedJobs.includes('Customer Support Officer') ? 'text-blue-600' : 'text-gray-500'" /></button>
                <a href="#details" class="px-4 py-2 text-sm font-semibold text-white bg-cyan-900 rounded-lg">Apply Now</a>
              </div>
            </div>
          </div>
        </div>
        <div class="px-20 py-10 bg-cyan-900 max-sm:px-4 max-sm:py-8">
          <div class="flex justify-between items-center max-sm:flex-col max-sm:gap-6 max-sm:items-start">
            <div class="flex gap-2 items-center">
              <div class="flex gap-1.5 items-center">
                <div class="w-2 h-2 bg-blue-300 rounded-full" />
                <span class="text-base font-bold text-white">NDK</span>
              </div>
              <span class="text-sm text-blue-300">· Myanmar's Job Platform</span>
            </div>
            <div class="flex gap-6 items-center max-sm:flex-wrap max-sm:gap-4">
              <span class="text-sm text-blue-300 cursor-pointer">About NDK</span>
              <span class="text-sm text-blue-300 cursor-pointer">Privacy Policy</span>
              <span class="text-sm text-blue-300 cursor-pointer">Terms</span>
              <span class="text-sm text-blue-300 cursor-pointer">Contact</span>
            </div>
          </div>
          <div class="mt-6 text-xs text-center text-slate-400">© 2026 NDK Job Platform. Structured Wireframe representation.</div>
        </div>
      </div>
</template>
