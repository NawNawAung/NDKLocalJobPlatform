<script setup>
import { ref } from 'vue';

const keyword = ref('');
const location = ref('');
const mobileMenuOpen = ref(false);
const searchNotice = ref('');
const savedJobs = ref([]);
const locations = ['Yangon', 'Mandalay', 'Naypyidaw', 'Remote'];
function submitSearch() {
    const terms = [keyword.value.trim(), location.value].filter(Boolean);
    searchNotice.value = terms.length ? `Searching jobs for ${terms.join(' in ')}.` : 'Browse all available jobs.';
}
function usePopularSearch(term) {
    if (locations.includes(term)) location.value = term;
    else keyword.value = term;
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
        <div class="px-8 py-20 bg-slate-300 max-sm:px-4 max-sm:py-12">
          <div class="mx-auto text-center max-w-[800px]">
            <div class="mb-3 text-5xl font-extrabold tracking-normal text-gray-900 leading-[52px] max-sm:text-3xl max-sm:leading-9">Find Your Next Opportunity in Myanmar</div>
            <div class="mb-8 text-base leading-6 text-slate-500 max-sm:text-sm">Connecting local talent with top companies in Yangon, Mandalay, and across states &amp; divisions.</div>
            <div class="flex overflow-hidden gap-0 items-center bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.08)] max-sm:flex-col max-sm:rounded-lg">
              <div class="flex flex-1 gap-2 items-center px-4 py-3.5 border border-r max-sm:w-full max-sm:border max-sm:border-r max-sm:border-b">
                <i class="ti ti-search text-lg text-gray-400" />
                <input v-model="keyword" type="search" placeholder="Job Title or Keyword" class="flex-1 text-sm text-gray-700 bg-transparent outline-none" @keydown.enter.prevent="submitSearch" />
              </div>
              <div class="flex flex-1 gap-2 items-center px-4 py-3.5 max-sm:w-full">
                <i class="ti ti-map-pin text-lg text-gray-400" />
                <select v-model="location" class="flex-1 text-sm text-gray-700 bg-transparent cursor-pointer outline-none">
                  <option value="">Township / Location</option>
                  <option v-for="item in locations" :key="item" :value="item">{{ item }}</option>
                </select>
                <i class="ti ti-chevron-down text-base text-gray-400" />
              </div>
              <button type="button" class="px-7 py-3.5 text-sm font-semibold text-white bg-cyan-900 cursor-pointer max-sm:w-full max-sm:text-center" @click="submitSearch">Search</button>
            </div>
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
            <p v-if="searchNotice" class="mt-3 text-sm text-slate-600" role="status">{{ searchNotice }}</p>
          </div>
        </div>
        <div class="px-20 py-16 max-sm:px-4 max-sm:py-10">
          <div class="mb-6 text-2xl font-bold leading-8 text-gray-900">Top Hiring Companies</div>
          <div class="grid grid-cols-4 gap-5 max-md:grid-cols-2 max-sm:grid-cols-1">
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" src="https://placehold.co/64x64/c8d9ef/c8d9ef" alt="KBZ Group Logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">KBZ Group</div>
                <div class="text-sm leading-5 text-gray-500">42 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" src="https://placehold.co/64x64/c8d9ef/c8d9ef" alt="Grab Myanmar Logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">Grab Myanmar</div>
                <div class="text-sm leading-5 text-gray-500">18 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" src="https://placehold.co/64x64/c8d9ef/c8d9ef" alt="Wave Money Logo" />
              <div class="text-center">
                <div class="text-base font-semibold leading-6 text-gray-900">Wave Money</div>
                <div class="text-sm leading-5 text-gray-500">12 Open Jobs</div>
              </div>
            </div>
            <div class="flex flex-col gap-3 items-center p-6 rounded-xl border border border-solid transition-shadow ease-in-out cursor-pointer duration">
              <img class="w-[64px] h-[64px] rounded-[8px] object-cover" src="https://placehold.co/64x64/c8d9ef/c8d9ef" alt="CDSG Group Logo" />
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
            <div class="flex flex-col gap-3 p-6 rounded-xl cursor-pointer bg-slate-400">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-white bg-opacity-20">
                <i class="ti ti-code text-xl text-white" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Technology</div>
              <div class="text-sm leading-5 text-white text-opacity-80">340+ Jobs</div>
            </div>
            <div class="flex flex-col gap-3 p-6 rounded-xl cursor-pointer bg-slate-400">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-white bg-opacity-20">
                <i class="ti ti-building-bank text-xl text-white" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Finance &amp; Banking</div>
              <div class="text-sm leading-5 text-white text-opacity-80">210+ Jobs</div>
            </div>
            <div class="flex flex-col gap-3 p-6 rounded-xl cursor-pointer bg-slate-400">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-white bg-opacity-20">
                <i class="ti ti-tool text-xl text-white" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Engineering</div>
              <div class="text-sm leading-5 text-white text-opacity-80">180+ Jobs</div>
            </div>
            <div class="flex flex-col gap-3 p-6 rounded-xl cursor-pointer bg-slate-400">
              <div class="flex justify-center items-center w-10 h-10 rounded-lg bg-white bg-opacity-20">
                <i class="ti ti-folder text-xl text-white" />
              </div>
              <div class="text-base font-bold leading-6 text-white">Administration</div>
              <div class="text-sm leading-5 text-white text-opacity-80">150+ Jobs</div>
            </div>
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
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" src="https://placehold.co/56x56/c8d9ef/c8d9ef" alt="KBZ Group Logo" />
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
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" src="https://placehold.co/56x56/c8d9ef/c8d9ef" alt="Grab Myanmar Logo" />
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
              <img class="w-[56px] h-[56px] rounded-[8px] object-cover flex-shrink-0" src="https://placehold.co/56x56/c8d9ef/c8d9ef" alt="Wave Money Logo" />
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
