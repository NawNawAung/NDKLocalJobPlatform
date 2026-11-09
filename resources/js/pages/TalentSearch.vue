<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const regions = bootstrap.regions ?? [];
const filterKeys = ['keyword', 'region_id', 'township_id', 'experience_min', 'employment_type'];
const filters = reactive({ keyword: '', region_id: '', township_id: '', experience_min: '', employment_type: '' });
const filtersOpen = ref(false);
const townships = computed(() => regions.find(region => String(region.id) === filters.region_id)?.townships ?? []);
const candidates = ref([]);
const pagination = ref({ current_page: 1, last_page: 1, total: 0, from: null, to: null });
const page = ref(1);
const loading = ref(false);
const error = ref('');
const cvDownloading = ref(null);

function formatLabel(value) { return value ? value.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : ''; }

function readLocationState() {
    const queryString = window.location.hash.split('?')[1] ?? '';
    const params = new URLSearchParams(queryString);
    filterKeys.forEach(key => { filters[key] = params.get(key) ?? ''; });
    page.value = Math.max(1, Number.parseInt(params.get('page') ?? '1', 10) || 1);
}

function syncLocationState() {
    const params = new URLSearchParams();
    filterKeys.forEach(key => {
        if (filters[key] !== '') params.set(key, filters[key]);
    });
    if (page.value > 1) params.set('page', String(page.value));
    const query = params.toString();
    const hash = `#talent${query ? `?${query}` : ''}`;
    window.history.replaceState(window.history.state, '', `${window.location.pathname}${window.location.search}${hash}`);
}

async function loadCandidates() {
    loading.value = true;
    error.value = '';
    try {
        const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
        params.page = page.value;
        const { data } = await window.axios.get('/api/employer/talent', { params });
        candidates.value = data.candidates ?? [];
        pagination.value = data.pagination ?? { current_page: 1, last_page: 1, total: candidates.value.length, from: null, to: null };
    } catch (exception) {
        candidates.value = [];
        error.value = exception.response?.data?.message ?? 'Could not search candidate profiles.';
    } finally { loading.value = false; }
}

function submitCandidateFilters() {
    filtersOpen.value = false;
    page.value = 1;
    syncLocationState();
    loadCandidates();
}

function clearFilters() {
    filterKeys.forEach(key => { filters[key] = ''; });
    page.value = 1;
    syncLocationState();
    loadCandidates();
}

function goToPage(nextPage) {
    if (loading.value || nextPage < 1 || nextPage > pagination.value.last_page) return;
    page.value = nextPage;
    syncLocationState();
    loadCandidates();
}

function onHashChange() {
    if (!window.location.hash.startsWith('#talent')) return;
    readLocationState();
    loadCandidates();
}

async function downloadCv(candidate) {
    cvDownloading.value = candidate.id;
    error.value = '';
    try {
        const { data } = await window.axios.get(candidate.cv_url, { responseType: 'blob' });
        const blobUrl = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `${candidate.name || 'candidate'}-resume`;
        link.click();
        URL.revokeObjectURL(blobUrl);
    } catch (exception) {
        let message = exception.response?.data?.message;
        if (!message && exception.response?.data instanceof Blob) {
            try { message = JSON.parse(await exception.response.data.text()).message; } catch { /* Response was not a JSON error. */ }
        }
        error.value = message ?? 'Could not download this CV.';
    } finally { cvDownloading.value = null; }
}

onMounted(() => {
    readLocationState();
    window.addEventListener('hashchange', onHashChange);
    loadCandidates();
});
onBeforeUnmount(() => window.removeEventListener('hashchange', onHashChange));
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7">
                <p class="text-sm font-semibold text-[var(--brand-primary)]">Employer workspace</p>
                <h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Candidate search</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Browse job seekers who have chosen to make their profiles discoverable by employers.</p>
            </div>

            <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $t(error) }}</p>

            <div class="grid items-start gap-5 lg:grid-cols-[260px_minmax(0,1fr)]">
                <form class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" @submit.prevent="submitCandidateFilters">
                    <button type="button" class="flex w-full items-center justify-between font-semibold text-slate-900 lg:hidden" :aria-expanded="filtersOpen" aria-controls="talent-search-filters" @click="filtersOpen = !filtersOpen">
                        <span>Search filters</span><i :class="filtersOpen ? 'ti ti-chevron-up' : 'ti ti-chevron-down'" aria-hidden="true" />
                    </button>
                    <div id="talent-search-filters" :class="filtersOpen ? 'mt-4 block' : 'hidden lg:mt-0 lg:block'" class="space-y-4">
                        <h2 class="hidden font-semibold text-slate-900 lg:block">Search filters</h2>
                        <label class="block text-xs font-semibold text-slate-600">Title or skill
                            <input v-model="filters.keyword" type="search" placeholder="e.g. Accountant, Vue" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Region
                            <select v-model="filters.region_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal" @change="filters.township_id = ''">
                                <option value="">All regions</option><option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }}</option>
                            </select>
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Township
                            <select v-model="filters.township_id" :disabled="!filters.region_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal disabled:bg-slate-50">
                                <option value="">Any township</option><option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option>
                            </select>
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Minimum experience (years)
                            <input v-model="filters.experience_min" type="number" min="0" max="60" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal">
                        </label>
                        <label class="block text-xs font-semibold text-slate-600">Employment preference
                            <select v-model="filters.employment_type" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal">
                                <option value="">Any</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="temporary">Temporary</option><option value="internship">Internship</option>
                            </select>
                        </label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" :disabled="loading" class="rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:opacity-50">{{ loading ? 'Searching…' : 'Search' }}</button>
                            <button type="button" :disabled="loading" class="rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="clearFilters">Clear</button>
                        </div>
                    </div>
                </form>

                <div>
                    <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Searching discoverable profiles…</div>
                    <template v-else-if="candidates.length">
                        <p class="mb-3 text-sm text-slate-600" aria-live="polite">Showing {{ pagination.from }}–{{ pagination.to }} of {{ pagination.total }} candidates</p>
                        <div class="space-y-3">
                            <article v-for="candidate in candidates" :key="candidate.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div class="min-w-0">
                                        <p class="font-bold text-slate-950">{{ candidate.name }}</p>
                                        <p class="mt-1 text-sm font-medium text-blue-800">{{ candidate.title || 'Professional title not provided' }}</p>
                                        <p class="mt-1 text-xs text-slate-500">{{ candidate.location || 'Location not provided' }}<span v-if="candidate.experience !== null"> · {{ candidate.experience }} years’ experience</span><span v-if="candidate.employment_type"> · {{ formatLabel(candidate.employment_type) }}</span></p>
                                        <p v-if="candidate.email" class="mt-2 break-all text-sm text-slate-700"><a :href="`mailto:${candidate.email}`" class="font-medium text-blue-800 hover:underline">{{ candidate.email }}</a></p>
                                        <div v-if="candidate.portfolio_url || Object.values(candidate.social_links ?? {}).some(Boolean)" class="mt-2 flex flex-wrap gap-3 text-sm">
                                            <a v-if="candidate.portfolio_url" :href="candidate.portfolio_url" target="_blank" rel="noopener noreferrer" class="font-semibold text-blue-800 hover:underline">Portfolio</a>
                                            <template v-for="(url, platform) in candidate.social_links" :key="platform"><a v-if="url" :href="url" target="_blank" rel="noopener noreferrer" class="font-medium capitalize text-blue-800 hover:underline">{{ platform === 'x' ? 'X' : platform }}</a></template>
                                        </div>
                                    </div>
                                    <button v-if="candidate.cv_url" type="button" :disabled="cvDownloading === candidate.id" class="shrink-0 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="downloadCv(candidate)">{{ cvDownloading === candidate.id ? 'Preparing…' : 'Download CV' }}</button>
                                </div>
                                <p v-if="candidate.bio" class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600">{{ candidate.bio }}</p>
                                <div v-if="candidate.skills?.length" class="mt-3 flex flex-wrap gap-1.5"><span v-for="skill in candidate.skills" :key="skill" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-900">{{ skill }}</span></div>
                            </article>
                        </div>
                        <nav v-if="pagination.last_page > 1" class="mt-5 flex items-center justify-between gap-3" aria-label="Candidate result pages">
                            <button type="button" :disabled="loading || page <= 1" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" @click="goToPage(page - 1)">Previous</button>
                            <span class="text-sm text-slate-600">Page {{ pagination.current_page }} of {{ pagination.last_page }}</span>
                            <button type="button" :disabled="loading || page >= pagination.last_page" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" @click="goToPage(page + 1)">Next</button>
                        </nav>
                    </template>
                    <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center">
                        <i class="ti ti-users text-4xl text-slate-300" aria-hidden="true"/><h2 class="mt-3 font-semibold text-slate-900">No discoverable profiles found</h2><p class="mt-1 text-sm text-slate-600">Try broadening the filters. Job seekers control whether their profile appears here.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
