<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const loading = ref(true);
const saving = ref(false);
const error = ref('');
const notice = ref('');
const profileEditing = ref(false);
const dashboard = ref({ profile: {}, stats: {}, jobs: [], recent_applicants: [], upcoming_interviews: [] });
const profileForm = reactive({ name: '', email: '', company_name: '', company_description: '', location: '' });
const jobs = computed(() => dashboard.value.jobs ?? []);

function applyProfile(profile) { Object.assign(profileForm, profile); }
function formatDate(value) {
    if (!value) return '—';
    return new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value));
}
function formatStatus(status) { return status?.replaceAll('_', ' ') ?? 'unknown'; }

async function loadDashboard() {
    loading.value = true;
    error.value = '';
    try {
        const { data } = await window.axios.get('/api/employer/dashboard');
        dashboard.value = data;
        applyProfile(data.profile ?? {});
    } catch (exception) {
        error.value = exception.response?.status === 403
            ? 'This dashboard is available to employer accounts.'
            : 'Could not load your employer workspace. Please refresh and try again.';
    } finally { loading.value = false; }
}

async function saveProfile() {
    saving.value = true;
    error.value = '';
    notice.value = '';
    try {
        await window.axios.patch('/api/employer/profile', profileForm);
        dashboard.value.profile = { ...profileForm, is_verified: dashboard.value.profile.is_verified };
        profileEditing.value = false;
        notice.value = 'Company profile saved.';
    } catch (exception) {
        error.value = exception.response?.data?.errors
            ? Object.values(exception.response.data.errors).flat().join(' ')
            : exception.response?.data?.message ?? 'Could not save your company profile.';
    } finally { saving.value = false; }
}

async function updateJob(job, status) {
    error.value = '';
    try {
        await window.axios.patch(`/api/employer/jobs/${job.id}`, { status });
        notice.value = `${job.title} is now ${status}.`;
        await loadDashboard();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not update this job.'; }
}

async function deleteJob(job) {
    if (!window.confirm(`Delete “${job.title}”? This hides the listing from job seekers.`)) return;
    try {
        await window.axios.delete(`/api/employer/jobs/${job.id}`);
        notice.value = 'Job listing removed.';
        await loadDashboard();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not remove this job.'; }
}

function editJob(job) {
    window.__employerJobDraft = job;
    window.location.hash = 'post-job';
}

onMounted(loadDashboard);
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="mb-7 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-[var(--brand-primary)]">Employer workspace</p>
                    <h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">{{ dashboard.profile.company_name || 'Your company' }}</h1>
                    <p class="mt-1 text-sm text-slate-600">{{ [dashboard.profile.location, dashboard.profile.township, dashboard.profile.region].filter(Boolean).join(' · ') || 'Add your company location' }}<span v-if="dashboard.profile.is_verified" class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-800"><i class="ti ti-circle-check" aria-hidden="true" /> Verified employer</span></p>
                </div>
                <a href="/#post-job" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]"><i class="ti ti-plus" aria-hidden="true" /> Post a job</a>
            </div>

            <p v-if="error" class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>
            <p v-if="notice" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-8 text-center text-sm text-slate-500">Loading your dashboard…</div>
            <template v-else>
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="item in [
                        { label: 'Active jobs', value: dashboard.stats.active_jobs ?? 0, icon: 'ti-briefcase', tone: 'text-blue-800 bg-blue-50' },
                        { label: 'Applications', value: dashboard.stats.total_applications ?? 0, icon: 'ti-users', tone: 'text-indigo-800 bg-indigo-50' },
                        { label: 'In review', value: dashboard.stats.in_review ?? 0, icon: 'ti-clipboard-list', tone: 'text-amber-800 bg-amber-50' },
                        { label: 'Upcoming interviews', value: dashboard.stats.interviews_upcoming ?? 0, icon: 'ti-calendar-event', tone: 'text-emerald-800 bg-emerald-50' },
                    ]" :key="item.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center justify-between"><span class="text-sm font-medium text-slate-600">{{ item.label }}</span><span class="grid h-9 w-9 place-items-center rounded-lg" :class="item.tone"><i :class="`ti ${item.icon}`" aria-hidden="true" /></span></div>
                        <p class="mt-4 text-3xl font-bold text-slate-900">{{ item.value }}</p>
                    </article>
                </div>

                <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.7fr)_minmax(300px,0.8fr)]">
                    <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
                            <div><h2 class="font-bold text-slate-900">Your job listings</h2><p class="mt-0.5 text-sm text-slate-500">Manage publication status and review applicants.</p></div>
                            <a href="/#pipeline" class="text-sm font-semibold text-[var(--brand-primary)] hover:underline">Candidate pipeline</a>
                        </div>
                        <div v-if="jobs.length" class="divide-y divide-slate-100">
                            <article v-for="job in jobs" :key="job.id" class="flex flex-wrap items-center justify-between gap-4 px-5 py-4">
                                <div class="min-w-0 flex-1"><h3 class="truncate font-semibold text-slate-900">{{ job.title }}</h3><p class="mt-1 text-sm text-slate-500">{{ [job.township, job.location].filter(Boolean).join(' · ') }} · {{ job.employment_type?.replaceAll('_', ' ') }}</p><p class="mt-1 text-xs text-slate-500">{{ job.applications_count }} {{ job.applications_count === 1 ? 'application' : 'applications' }} · Posted {{ formatDate(job.posted_at) }}</p></div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="job.status === 'published' ? 'bg-emerald-50 text-emerald-800' : job.status === 'paused' ? 'bg-amber-50 text-amber-800' : 'bg-slate-100 text-slate-700'">{{ job.status }}</span>
                                    <a href="/#pipeline" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Applicants</a>
                                    <button type="button" class="rounded-lg px-2 py-2 text-xs font-semibold text-blue-800 hover:bg-blue-50" @click="editJob(job)">Edit</button>
                                    <button v-if="job.status === 'published'" type="button" class="rounded-lg px-2 py-2 text-xs font-semibold text-amber-800 hover:bg-amber-50" @click="updateJob(job, 'paused')">Pause</button>
                                    <button v-else-if="job.status === 'paused'" type="button" class="rounded-lg px-2 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-50" @click="updateJob(job, 'published')">Resume</button>
                                    <button v-if="job.status !== 'closed'" type="button" class="rounded-lg px-2 py-2 text-xs font-semibold text-red-700 hover:bg-red-50" @click="updateJob(job, 'closed')">Close</button>
                                    <button type="button" class="rounded-lg px-2 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100" aria-label="Delete listing" @click="deleteJob(job)"><i class="ti ti-trash" aria-hidden="true" /></button>
                                </div>
                            </article>
                        </div>
                        <div v-else class="px-5 py-12 text-center"><i class="ti ti-briefcase mx-auto text-3xl text-slate-300" aria-hidden="true" /><p class="mt-2 font-semibold text-slate-800">No job listings yet</p><p class="mt-1 text-sm text-slate-500">Publish your first opening to start receiving applications.</p><a href="/#post-job" class="mt-4 inline-flex rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white">Create a job</a></div>
                    </section>

                    <div class="space-y-6">
                        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-slate-900">Company profile</h2><p class="mt-0.5 text-sm text-slate-500">Information job seekers see.</p></div><button type="button" class="text-sm font-semibold text-[var(--brand-primary)] hover:underline" @click="profileEditing = !profileEditing">{{ profileEditing ? 'Cancel' : 'Edit' }}</button></div>
                            <form v-if="profileEditing" class="space-y-3 p-5" @submit.prevent="saveProfile">
                                <label class="block text-xs font-semibold text-slate-700">Contact name<input v-model="profileForm.name" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                                <label class="block text-xs font-semibold text-slate-700">Contact email<input v-model="profileForm.email" required type="email" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                                <label class="block text-xs font-semibold text-slate-700">Company name<input v-model="profileForm.company_name" required maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                                <label class="block text-xs font-semibold text-slate-700">Company location<input v-model="profileForm.location" maxlength="255" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label>
                                <label class="block text-xs font-semibold text-slate-700">Company description<textarea v-model="profileForm.company_description" rows="3" maxlength="5000" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" /></label>
                                <button :disabled="saving" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">{{ saving ? 'Saving…' : 'Save profile' }}</button>
                            </form>
                            <dl v-else class="space-y-4 p-5 text-sm"><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Account contact</dt><dd class="mt-1 font-medium text-slate-900">{{ dashboard.profile.name || '—' }}</dd><dd class="text-slate-600">{{ dashboard.profile.email || '—' }}</dd></div><div><dt class="text-xs font-semibold uppercase tracking-wide text-slate-500">Company</dt><dd class="mt-1 font-medium text-slate-900">{{ dashboard.profile.company_name || '—' }}</dd><dd class="text-slate-600">{{ [dashboard.profile.location, dashboard.profile.township, dashboard.profile.region].filter(Boolean).join(' · ') || 'Location not provided' }}</dd><dd class="mt-2 whitespace-pre-line leading-5 text-slate-600">{{ dashboard.profile.company_description || 'Add a company description to help candidates understand your organization.' }}</dd></div></dl>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><div><h2 class="font-bold text-slate-900">Recent applicants</h2><p class="mt-0.5 text-sm text-slate-500">Latest applications to your jobs.</p></div><a href="/#pipeline" class="text-sm font-semibold text-[var(--brand-primary)]">View all</a></div>
                            <div v-if="dashboard.recent_applicants?.length" class="divide-y divide-slate-100"><div v-for="applicant in dashboard.recent_applicants" :key="applicant.id" class="flex items-center justify-between gap-3 px-5 py-3"><div class="min-w-0"><p class="truncate text-sm font-semibold text-slate-900">{{ applicant.name }}</p><p class="truncate text-xs text-slate-500">{{ applicant.professional_title || 'Title not provided' }} · {{ applicant.job_title }}</p></div><span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold capitalize text-blue-800">{{ formatStatus(applicant.status) }}</span></div></div>
                            <p v-else class="px-5 py-8 text-center text-sm text-slate-500">Applications will appear here when candidates apply.</p>
                        </section>

                        <section class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-900">Upcoming interviews</h2></div><div v-if="dashboard.upcoming_interviews?.length" class="divide-y divide-slate-100"><div v-for="interview in dashboard.upcoming_interviews" :key="interview.id" class="flex items-start gap-3 px-5 py-3"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-800"><i class="ti ti-calendar-event" aria-hidden="true" /></span><div><p class="text-sm font-semibold text-slate-900">{{ interview.candidate_name }} · {{ interview.job_title }}</p><p class="mt-1 text-xs text-slate-500">{{ formatDate(interview.interview_at) }} · {{ interview.type }}</p></div></div></div><p v-else class="px-5 py-6 text-sm text-slate-500">No upcoming interviews scheduled.</p></section>
                    </div>
                </div>
            </template>
        </div>
    </section>
</template>
