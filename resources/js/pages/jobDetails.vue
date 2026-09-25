<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const authenticated = Boolean(bootstrap.authenticated);
const role = bootstrap.role;
const job = ref(null);
const similarJobs = ref([]);
const loading = ref(true);
const submitting = ref(false);
const saving = ref(false);
const error = ref('');
const notice = ref('');
const hasApplied = ref(false);
const applicationStatus = ref('');
const hasResume = ref(false);
const resumeName = ref('');
const coverLetter = ref('');
const cvFile = ref(null);
const applicationForm = ref(null);
const saved = ref(false);
const profileCanApply = ref(true);
const canApply = computed(() => role === 'job_seeker' && Boolean(bootstrap.authenticated) && profileCanApply.value);

function currentJobId() {
    const query = window.location.hash.split('?')[1] ?? '';
    return new URLSearchParams(query).get('job');
}
function formatLabel(value) { return value ? value.replaceAll('_', ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()) : ''; }
function formatSalary() {
    if (job.value?.salary_min == null && job.value?.salary_max == null) return 'Salary not disclosed';
    const currency = job.value.salary_currency || 'MMK';
    const amount = (value) => new Intl.NumberFormat('en-US').format(value);
    if (job.value.salary_min != null && job.value.salary_max != null) return `${amount(job.value.salary_min)} – ${amount(job.value.salary_max)} ${currency} / month`;
    return job.value.salary_min != null ? `From ${amount(job.value.salary_min)} ${currency} / month` : `Up to ${amount(job.value.salary_max)} ${currency} / month`;
}
function postedLabel(value) {
    if (!value) return 'Recently posted';
    const days = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 86400000));
    if (days === 0) return 'Today';
    if (days === 1) return '1 day ago';
    if (days < 7) return `${days} days ago`;
    if (days < 30) return `${Math.floor(days / 7)} weeks ago`;
    return new Date(value).toLocaleDateString();
}
function requirementsList(value) { return (value ?? '').split(/\r?\n/).map((line) => line.replace(/^\s*[-•*]\s*/, '').trim()).filter(Boolean); }
function handleCv(event) { cvFile.value = event.target.files?.[0] ?? null; }

async function loadJob() {
    const id = currentJobId();
    job.value = null;
    similarJobs.value = [];
    hasApplied.value = false;
    applicationStatus.value = '';
    error.value = '';
    notice.value = '';
    if (!id) {
        loading.value = false;
        error.value = 'Choose a job from the job search results to view its details.';
        return;
    }

    loading.value = true;
    try {
        const { data } = await window.axios.get(`/api/jobs/${encodeURIComponent(id)}`);
        job.value = data.job;
        hasApplied.value = data.has_applied;
        applicationStatus.value = data.application_status ?? '';
        hasResume.value = data.has_resume;
        resumeName.value = data.resume_name ?? '';
        profileCanApply.value = data.profile_active;
        saved.value = data.saved;
        if (data.job.category) {
            const related = await window.axios.get('/api/jobs/search', { params: { category: data.job.category } });
            similarJobs.value = (related.data.data ?? []).filter((item) => item.id !== data.job.id).slice(0, 3);
        }
    } catch (exception) {
        error.value = exception.response?.status === 404
            ? 'This job is no longer available.'
            : 'Could not load this job. Please try again.';
    } finally { loading.value = false; }
}

async function submitApplication() {
    if (!job.value || !canApply.value || submitting.value || hasApplied.value) return;
    error.value = '';
    notice.value = '';
    submitting.value = true;
    const payload = new FormData();
    if (coverLetter.value.trim()) payload.append('cover_letter', coverLetter.value.trim());
    if (cvFile.value) payload.append('cv', cvFile.value);
    try {
        const { data } = await window.axios.post(`/api/jobs/${job.value.id}/applications`, payload, { headers: { 'Content-Type': 'multipart/form-data' } });
        hasApplied.value = true;
        applicationStatus.value = data.application.status;
        notice.value = data.message;
    } catch (exception) {
        error.value = exception.response?.data?.errors
            ? Object.values(exception.response.data.errors).flat().join(' ')
            : exception.response?.data?.message ?? 'Your application could not be submitted. Please try again.';
    } finally { submitting.value = false; }
}

async function toggleSaved() {
    if (!authenticated || role !== 'job_seeker') {
        error.value = 'Sign in with a job seeker account to save jobs.';
        return;
    }
    saving.value = true;
    error.value = '';
    try {
        await window.axios.request({ method: saved.value ? 'delete' : 'post', url: `/api/jobs/${job.value.id}/save` });
        saved.value = !saved.value;
        notice.value = saved.value ? 'Job saved to your profile.' : 'Job removed from your saved list.';
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not update your saved jobs.'; }
    finally { saving.value = false; }
}

async function shareJob() {
    const shareData = { title: job.value.title, text: `Job opening: ${job.value.title}`, url: window.location.href };
    try {
        if (navigator.share) await navigator.share(shareData);
        else { await navigator.clipboard.writeText(shareData.url); notice.value = 'Job link copied.'; }
    } catch (exception) {
        if (exception.name !== 'AbortError') error.value = 'Could not share this job link.';
    }
}

function scrollToApplication() {
    if (!canApply.value) {
        error.value = !authenticated ? 'Sign in or create a job seeker account to apply.' : role !== 'job_seeker' ? 'Only job seeker accounts can apply for jobs.' : 'Your job seeker profile is inactive. Update your profile before applying.';
        return;
    }
    applicationForm.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

function onHashChange() { loadJob(); }
onMounted(() => { window.addEventListener('hashchange', onHashChange); loadJob(); });
onUnmounted(() => window.removeEventListener('hashchange', onHashChange));
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <a href="/#search" class="inline-flex items-center gap-1 text-sm font-semibold text-[var(--brand-primary)] hover:underline"><i class="ti ti-arrow-left" aria-hidden="true"/>Back to jobs</a>
            <div v-if="error && !job && !loading" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900" role="alert">{{ error }}<a href="/jobs" class="ml-2 font-semibold underline">Browse jobs</a></div>
            <div v-else-if="loading" class="mt-6 rounded-xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">Loading job details…</div>
            <template v-else-if="job">
                <p v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p><p v-if="notice" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
                <div class="mt-5 grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_310px]">
                    <div class="space-y-5">
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-4"><div class="min-w-0"><p class="text-sm font-semibold text-[var(--brand-primary)]">{{ job.company.name }}<span v-if="job.company.is_verified" class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800"><i class="ti ti-circle-check" aria-hidden="true"/>Verified</span></p><h1 class="mt-2 text-2xl font-bold tracking-tight text-[var(--brand-ink)] sm:text-3xl">{{ job.title }}</h1><p class="mt-2 text-sm text-slate-600">{{ job.location }} · {{ postedLabel(job.posted_at) }}</p></div><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-900">{{ formatLabel(job.employment_type) }}<span v-if="job.experience_level"> · {{ formatLabel(job.experience_level) }}</span></span></div>
                            <div class="mt-6 grid gap-4 border-y border-slate-100 py-5 sm:grid-cols-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Monthly salary</p><p class="mt-1 font-semibold text-slate-900">{{ formatSalary() }}</p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Work arrangement</p><p class="mt-1 font-semibold text-slate-900">{{ formatLabel(job.work_mode) || 'Not specified' }}</p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</p><p class="mt-1 font-semibold text-slate-900">{{ job.category }}</p></div></div>
                            <div class="mt-6 flex flex-wrap gap-3"><button type="button" :disabled="hasApplied" class="rounded-lg bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[var(--brand-primary-hover)] disabled:cursor-default disabled:opacity-70" @click="scrollToApplication">{{ hasApplied ? 'Application submitted' : 'Apply for this job' }}</button><button type="button" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="toggleSaved"><i :class="saved ? 'ti ti-bookmark-filled text-blue-700' : 'ti ti-bookmark'" aria-hidden="true"/>{{ saved ? 'Saved' : 'Save job' }}</button><button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="shareJob"><i class="ti ti-share" aria-hidden="true"/>Share</button></div>
                            <p v-if="hasApplied" class="mt-3 text-sm text-emerald-800" role="status">You applied for this job. Current status: <span class="font-semibold capitalize">{{ formatLabel(applicationStatus) }}</span>.</p>
                            <p v-if="job.application_deadline" class="mt-3 text-xs text-slate-500">Applications close {{ new Date(job.application_deadline).toLocaleDateString() }}.</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><h2 class="text-lg font-bold text-slate-900">About the role</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ job.description }}</p><template v-if="requirementsList(job.requirements).length"><h3 class="mt-7 text-base font-bold text-slate-900">Requirements</h3><ul class="mt-3 space-y-2"> <li v-for="(requirement, index) in requirementsList(job.requirements)" :key="index" class="flex gap-2.5 text-sm leading-6 text-slate-700"><i class="ti ti-check mt-1 shrink-0 text-blue-700" aria-hidden="true"/><span>{{ requirement }}</span></li></ul></template></article>

                        <article ref="applicationForm" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><h2 class="text-lg font-bold text-slate-900">Apply for {{ job.title }}</h2><p class="mt-1 text-sm text-slate-600">Your application and contact details will be shared with this employer.</p>
                            <div v-if="!authenticated" class="mt-5 rounded-lg bg-blue-50 p-4 text-sm text-blue-950">Please <a href="/login" class="font-semibold underline">sign in</a> or <a href="/register" class="font-semibold underline">create a job seeker account</a> to apply.</div>
                            <div v-else-if="role !== 'job_seeker'" class="mt-5 rounded-lg bg-slate-100 p-4 text-sm text-slate-700">This account is registered as an employer and cannot apply for jobs.</div>
                            <div v-else-if="hasApplied" class="mt-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900">Your application has been received. You can follow its status from your profile.</div>
                            <form v-else class="mt-5 space-y-4" @submit.prevent="submitApplication"><p v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p><p v-if="notice" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p><div v-if="hasResume" class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-950"><i class="ti ti-file-check mr-1" aria-hidden="true"/>Your profile CV will be attached{{ resumeName ? ` (${resumeName})` : '' }}. Upload a new file below to use a different CV.</div><label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Cover letter <span class="font-normal text-slate-500">(optional)</span></span><textarea v-model="coverLetter" rows="5" maxlength="5000" placeholder="Briefly explain your interest and relevant experience." class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"/></label><label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">CV or resume <span class="font-normal text-slate-500">(optional if your profile has one)</span></span><input type="file" accept=".pdf,.doc,.docx" class="block w-full rounded-lg border border-slate-300 p-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-900" @change="handleCv"><span class="mt-1 block text-xs text-slate-500">PDF, DOC, or DOCX · up to 10 MB</span></label><button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:cursor-wait disabled:opacity-60"><i :class="submitting ? 'ti ti-loader-2 animate-spin' : 'ti ti-send'" aria-hidden="true"/>{{ submitting ? 'Submitting…' : 'Submit application' }}</button></form>
                        </article>
                    </div>

                    <aside class="space-y-5"><section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">About the company</h2><h3 class="mt-2 text-lg font-bold text-slate-900">{{ job.company.name }}</h3><p class="mt-1 text-sm text-slate-600">{{ job.company.location || job.location }}</p><p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ job.company.description || 'Company details have not been added yet.' }}</p></section><section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Similar jobs</h2><div v-if="similarJobs.length" class="mt-3 divide-y divide-slate-100"><a v-for="similar in similarJobs" :key="similar.id" :href="`/#details?job=${similar.id}`" class="block py-3 first:pt-0"><span class="block text-sm font-semibold text-blue-900 hover:underline">{{ similar.title }}</span><span class="mt-1 block text-xs text-slate-500">{{ similar.company }} · {{ similar.location }}</span></a></div><p v-else class="mt-3 text-sm text-slate-500">No similar openings are available right now.</p></section></aside>
                </div>
            </template>
        </div>
    </section>
</template>
