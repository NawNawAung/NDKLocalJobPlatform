<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';

const loading = ref(true);
const error = ref('');
const notice = ref('');
const applications = ref([]);
const jobs = ref([]);
const search = ref('');
const jobFilter = ref('');
const statusFilter = ref('');
const scheduleFor = ref(null);
const scheduleForm = reactive({ interview_at: '', interview_type: 'online', meeting_url: '', notes: '' });
const statuses = ['submitted', 'reviewing', 'shortlisted', 'interview', 'offered', 'hired', 'rejected'];
const updateStatuses = statuses.filter((status) => status !== 'submitted');
let searchTimer;
const countLabel = computed(() => `${applications.value.length} ${applications.value.length === 1 ? 'candidate' : 'candidates'}`);

async function loadCandidates() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await window.axios.get('/api/employer/candidates', { params: { search: search.value.trim() || undefined, job_id: jobFilter.value || undefined, status: statusFilter.value || undefined } });
        applications.value = data.applications?.data ?? [];
        jobs.value = data.jobs ?? [];
    } catch (exception) {
        error.value = exception.response?.status === 403 ? 'Candidate pipeline is available to employer accounts.' : 'Could not load candidates. Please try again.';
    } finally { loading.value = false; }
}

async function updateStatus(application, status) {
    error.value = '';
    try {
        await window.axios.patch(`/api/employer/applications/${application.id}`, { status });
        application.status = status;
        notice.value = `Application moved to ${status.replaceAll('_', ' ')}.`;
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not update the application.'; }
}

async function messageCandidate(application) {
    error.value = '';
    try {
        const { data } = await window.axios.post(`/api/applications/${application.id}/conversation`);
        window.__pendingConversationId = data.conversation.id;
        window.location.hash = 'messages';
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not open a conversation.'; }
}

async function scheduleInterview() {
    if (!scheduleFor.value) return;
    error.value = '';
    try {
        await window.axios.post(`/api/employer/applications/${scheduleFor.value.id}/interviews`, scheduleForm);
        scheduleFor.value.status = 'interview';
        notice.value = 'Interview scheduled and candidate status updated.';
        scheduleFor.value = null;
        Object.assign(scheduleForm, { interview_at: '', interview_type: 'online', meeting_url: '', notes: '' });
    } catch (exception) {
        error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not schedule the interview.';
    }
}

watch([jobFilter, statusFilter], loadCandidates);
watch(search, () => { clearTimeout(searchTimer); searchTimer = setTimeout(loadCandidates, 250); });
onMounted(loadCandidates);
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7 flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm font-semibold text-[var(--brand-primary)]">Recruiting</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Candidate pipeline</h1><p class="mt-2 text-sm text-slate-600">Review applicants to your job listings, update their stage, and contact them.</p></div><span class="rounded-full bg-blue-50 px-3 py-1.5 text-sm font-semibold text-blue-900">{{ countLabel }}</span></div>
            <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p><p v-if="notice" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
            <div class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(220px,1fr)_220px_190px]"><label class="relative"><span class="sr-only">Search applicants</span><i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"/><input v-model="search" type="search" placeholder="Search name or job title" class="filter-field pl-9"></label><label><span class="sr-only">Filter by job</span><select v-model="jobFilter" class="filter-field"><option value="">All your jobs</option><option v-for="job in jobs" :key="job.id" :value="String(job.id)">{{ job.title }}</option></select></label><label><span class="sr-only">Filter by stage</span><select v-model="statusFilter" class="filter-field"><option value="">All stages</option><option v-for="status in statuses" :key="status" :value="status">{{ status.replaceAll('_', ' ') }}</option></select></label></div>

            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">Loading applicants…</div>
            <div v-else-if="applications.length" class="space-y-4">
                <article v-for="application in applications" :key="application.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4"><div class="flex min-w-0 gap-3"><span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-blue-100 text-lg font-bold text-blue-900">{{ application.candidate.name?.slice(0, 1)?.toUpperCase() || '?' }}</span><div class="min-w-0"><h2 class="truncate text-lg font-bold text-slate-900">{{ application.candidate.name }}</h2><p class="mt-0.5 text-sm text-slate-700">{{ application.candidate.title || 'Professional title not provided' }}</p><p class="mt-1 text-xs text-slate-500">{{ application.job_title }} · {{ application.candidate.location || 'Location not provided' }}<span v-if="application.candidate.experience !== null"> · {{ application.candidate.experience }} years experience</span></p><p class="mt-1 text-xs text-slate-500">Applied {{ application.submitted_at ? new Date(application.submitted_at).toLocaleDateString() : '—' }}</p></div></div><label class="text-xs font-semibold text-slate-500">Application stage<select :value="application.status" class="mt-1 block rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold capitalize text-slate-800" @change="updateStatus(application, $event.target.value)"><option v-for="status in updateStatuses" :key="status" :value="status">{{ status.replaceAll('_', ' ') }}</option></select></label></div>
                    <div v-if="application.candidate.skills?.length" class="mt-4 flex flex-wrap gap-2"><span v-for="skill in application.candidate.skills" :key="skill" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">{{ skill }}</span></div><p v-if="application.cover_letter" class="mt-4 whitespace-pre-line rounded-lg bg-slate-50 p-3 text-sm leading-6 text-slate-700">{{ application.cover_letter }}</p>
                    <div class="mt-5 flex flex-wrap gap-2 border-t border-slate-100 pt-4"><a v-if="application.candidate.has_cv" :href="`/api/employer/applications/${application.id}/resume`" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="ti ti-download" aria-hidden="true"/>Download CV</a><span v-else class="inline-flex items-center gap-1.5 rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500"><i class="ti ti-file-off" aria-hidden="true"/>No CV provided</span><button type="button" class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-900 hover:bg-blue-50" @click="messageCandidate(application)"><i class="ti ti-message" aria-hidden="true"/>Message candidate</button><button type="button" class="inline-flex items-center gap-1.5 rounded-lg bg-[var(--brand-primary)] px-3 py-2 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]" @click="scheduleFor = application"><i class="ti ti-calendar-plus" aria-hidden="true"/>Schedule interview</button></div>
                </article>
            </div>
            <div v-else class="rounded-xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center"><i class="ti ti-users mx-auto text-4xl text-slate-300" aria-hidden="true"/><h2 class="mt-3 font-semibold text-slate-800">No matching applicants</h2><p class="mt-1 text-sm text-slate-500">Applications to your job listings will appear here.</p><a href="/#post-job" class="mt-4 inline-flex rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white">Post a job</a></div>
        </div>

        <div v-if="scheduleFor" class="fixed inset-0 z-[60] grid place-items-center bg-slate-950/40 p-4" @click.self="scheduleFor = null"><form class="w-full max-w-lg space-y-4 rounded-2xl bg-white p-6 shadow-xl" @submit.prevent="scheduleInterview"><div><h2 class="text-lg font-bold text-slate-900">Schedule an interview</h2><p class="mt-1 text-sm text-slate-600">{{ scheduleFor.candidate.name }} · {{ scheduleFor.job_title }}</p></div><label class="block text-sm font-semibold text-slate-700">Date and time<input v-model="scheduleForm.interview_at" required type="datetime-local" class="filter-field mt-1"></label><label class="block text-sm font-semibold text-slate-700">Interview type<select v-model="scheduleForm.interview_type" class="filter-field mt-1"><option value="online">Online</option><option value="phone">Phone</option><option value="in_person">In person</option></select></label><label v-if="scheduleForm.interview_type === 'online'" class="block text-sm font-semibold text-slate-700">Meeting URL (optional)<input v-model="scheduleForm.meeting_url" type="url" class="filter-field mt-1" placeholder="https://…"></label><label class="block text-sm font-semibold text-slate-700">Notes (optional)<textarea v-model="scheduleForm.notes" rows="3" class="filter-field mt-1" placeholder="Anything the candidate should know"></textarea></label><div class="flex justify-end gap-2"><button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700" @click="scheduleFor = null">Cancel</button><button class="rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white">Schedule</button></div></form></div>
    </section>
</template>

<style scoped>
@reference "../../css/app.css";
.filter-field { @apply w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100; }
</style>
