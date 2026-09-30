<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const regions = bootstrap.regions ?? [];
const filters = reactive({ keyword: '', region_id: '', township_id: '', experience_min: '', employment_type: '' });
const townships = computed(() => regions.find(region => String(region.id) === filters.region_id)?.townships ?? []);
const candidates = ref([]);
const remaining = ref(null);
const unlimited = ref(false);
const upgradeRequired = ref(false);
const loading = ref(false);
const error = ref('');
const notice = ref('');
const cvDownloading = ref(null);

function formatLabel(value) { return value ? value.replaceAll('_', ' ').replace(/\b\w/g, letter => letter.toUpperCase()) : ''; }
async function loadCandidates() {
    loading.value = true; error.value = ''; notice.value = '';
    try {
        const params = Object.fromEntries(Object.entries(filters).filter(([, value]) => value !== ''));
        const { data } = await window.axios.get('/api/employer/talent', { params });
        candidates.value = data.candidates ?? [];
        remaining.value = data.remaining;
        unlimited.value = data.unlimited;
        upgradeRequired.value = data.upgrade_required;
        if (remaining.value !== null) notice.value = `${remaining.value} new profile preview${remaining.value === 1 ? '' : 's'} remaining this month.`;
    } catch (exception) {
        candidates.value = [];
        upgradeRequired.value = Boolean(exception.response?.data?.upgrade_required);
        error.value = exception.response?.data?.message ?? 'Could not search candidate profiles.';
    } finally { loading.value = false; }
}
async function downloadCv(candidate) {
    cvDownloading.value = candidate.id; error.value = '';
    try {
        const { data, headers } = await window.axios.get(candidate.cv_url, { responseType: 'blob' });
        const blobUrl = URL.createObjectURL(data);
        const link = document.createElement('a');
        link.href = blobUrl;
        link.download = `${candidate.name || 'candidate'}-resume`;
        link.click();
        URL.revokeObjectURL(blobUrl);
        void headers;
    } catch (exception) {
        let message = exception.response?.data?.message;
        if (!message && exception.response?.data instanceof Blob) {
            try { message = JSON.parse(await exception.response.data.text()).message; } catch { /* Response was not a JSON error. */ }
        }
        upgradeRequired.value = exception.response?.status === 403;
        error.value = message ?? 'Could not download this CV; check your plan’s monthly limit.';
    }
    finally { cvDownloading.value = null; }
}
onMounted(loadCandidates);
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8"><div class="mx-auto max-w-7xl">
        <div class="mb-7"><p class="text-sm font-semibold text-[var(--brand-primary)]">Employer workspace</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Candidate search</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Discover job seekers who opted in to employer search. Contact details and CV downloads follow your plan’s entitlements.</p></div>
        <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }} <a v-if="upgradeRequired" href="/#billing" class="ml-1 font-semibold underline">Review plans</a></p>
        <p v-if="notice" class="mb-4 rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-900" role="status">{{ notice }}</p>
        <div class="grid items-start gap-5 lg:grid-cols-[260px_minmax(0,1fr)]">
            <form class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm" @submit.prevent="loadCandidates"><h2 class="font-semibold text-slate-900">Search filters</h2><label class="block text-xs font-semibold text-slate-600">Title or skill<input v-model="filters.keyword" type="search" placeholder="e.g. Accountant, Vue" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label><label class="block text-xs font-semibold text-slate-600">Region<select v-model="filters.region_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal" @change="filters.township_id = ''"><option value="">All regions</option><option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }}</option></select></label><label class="block text-xs font-semibold text-slate-600">Township<select v-model="filters.township_id" :disabled="!filters.region_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal disabled:bg-slate-50"><option value="">Any township</option><option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option></select></label><label class="block text-xs font-semibold text-slate-600">Minimum experience (years)<input v-model="filters.experience_min" type="number" min="0" max="60" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal"></label><label class="block text-xs font-semibold text-slate-600">Employment preference<select v-model="filters.employment_type" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">Any</option><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="temporary">Temporary</option><option value="internship">Internship</option></select></label><button type="submit" :disabled="loading" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:opacity-50">{{ loading ? 'Searching…' : 'Search candidates' }}</button></form>
            <div><div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Searching opt-in profiles…</div><div v-else-if="candidates.length" class="space-y-3"><article v-for="candidate in candidates" :key="candidate.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-4"><div><p class="font-bold text-slate-950">{{ candidate.name }}</p><p class="mt-1 text-sm font-medium text-blue-800">{{ candidate.title || 'Professional title not provided' }}</p><p class="mt-1 text-xs text-slate-500">{{ candidate.location || 'Location not provided' }}<span v-if="candidate.experience !== null"> · {{ candidate.experience }} years’ experience</span><span v-if="candidate.employment_type"> · {{ formatLabel(candidate.employment_type) }}</span></p><p v-if="candidate.email" class="mt-2 text-sm text-slate-700"><a :href="`mailto:${candidate.email}`" class="font-medium text-blue-800 hover:underline">{{ candidate.email }}</a></p></div><button v-if="candidate.cv_url" type="button" :disabled="cvDownloading === candidate.id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="downloadCv(candidate)">{{ cvDownloading === candidate.id ? 'Preparing…' : 'Download CV' }}</button><span v-else-if="candidate.has_cv" class="rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-600">CV requires a paid plan</span></div><div v-if="candidate.skills?.length" class="mt-3 flex flex-wrap gap-1.5"><span v-for="skill in candidate.skills" :key="skill" class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-900">{{ skill }}</span></div></article></div><div v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center"><i class="ti ti-users text-4xl text-slate-300" aria-hidden="true"/><h2 class="mt-3 font-semibold text-slate-900">No discoverable profiles found</h2><p class="mt-1 text-sm text-slate-600">Try broadening your filters. Job seekers must opt in before appearing here.</p></div></div>
        </div>
        <div v-if="upgradeRequired" class="mt-5 rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-950">Unlock full candidate search, contact details, and CV access with a paid employer plan. <a href="/#billing" class="ml-1 font-semibold underline">Compare plans</a></div>
    </div></section>
</template>
