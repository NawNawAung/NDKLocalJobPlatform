<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { requestAuthentication } from '../authPrompt.js';

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
const isOwnListing = ref(false);
const hasResume = ref(false);
const resumeName = ref('');
const coverLetter = ref('');
const cvFile = ref(null);
const applicationForm = ref(null);
const saved = ref(false);
const profileCanApply = ref(true);
const reporting = ref(false);
const reportDialog = ref(false);
const reportTarget = ref({ type: 'job', id: null });
const reportReason = ref('');
const reportDetails = ref('');
const reportError = ref('');
const reportReasonInput = ref(null);
let reportTrigger = null;
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
        isOwnListing.value = Boolean(data.is_own_listing);
        hasResume.value = data.has_resume;
        resumeName.value = data.resume_name ?? '';
        profileCanApply.value = data.profile_active;
        saved.value = data.saved;
        if (data.job.category_id) {
            const related = await window.axios.get('/api/jobs/search', { params: { category_id: data.job.category_id } });
            similarJobs.value = (related.data.data ?? []).filter((item) => item.id !== data.job.id).slice(0, 3);
        }
    } catch (exception) {
        error.value = exception.response?.status === 404
            ? 'This job is no longer available.'
            : 'Could not load this job. Please try again.';
    } finally {
        loading.value = false;
        if (authenticated && job.value) await resumeRequestedAction();
    }
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
        const { data } = await window.axios.post(`/api/jobs/${job.value.id}/applications`, payload, { headers: { 'Content-Type': 'multipart/form-data' }, authPromptContext: 'apply' });
        hasApplied.value = true;
        applicationStatus.value = data.application.status;
        notice.value = data.message;
    } catch (exception) {
        if (exception.response?.status !== 401) {
            error.value = exception.response?.data?.errors
                ? Object.values(exception.response.data.errors).flat().join(' ')
                : exception.response?.data?.message ?? 'Your application could not be submitted. Please try again.';
        }
    } finally { submitting.value = false; }
}

async function toggleSaved() {
    if (!authenticated) {
        requestAuthentication('save');
        return;
    }
    if (role !== 'job_seeker') {
        error.value = 'Sign in with a job seeker account to save jobs.';
        return;
    }
    saving.value = true;
    error.value = '';
    try {
        await window.axios.request({ method: saved.value ? 'delete' : 'post', url: `/api/jobs/${job.value.id}/save`, authPromptContext: 'save' });
        saved.value = !saved.value;
        notice.value = saved.value ? 'Job saved to your profile.' : 'Job removed from your saved list.';
    } catch (exception) {
        if (exception.response?.status !== 401) error.value = exception.response?.data?.message ?? 'Could not update your saved jobs.';
    }
    finally { saving.value = false; }
}

async function resumeRequestedAction() {
    const hashParts = window.location.hash.slice(1).split('?');
    const query = new URLSearchParams(hashParts[1] ?? '');
    const action = query.get('auth_action');
    if (!['apply', 'save', 'reportJob', 'reportEmployer'].includes(action)) return;

    query.delete('auth_action');
    const cleanHash = `${hashParts[0]}${query.size ? `?${query.toString()}` : ''}`;
    window.history.replaceState(window.history.state, '', `${window.location.pathname}${window.location.search}#${cleanHash}`);

    if (action === 'apply') {
        await nextTick();
        applicationForm.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else if (action === 'save' && !saved.value) {
        await toggleSaved();
    } else if (action === 'reportJob' || action === 'reportEmployer') {
        await reportJob(action === 'reportJob' ? 'job' : 'employer', action === 'reportJob' ? job.value.id : job.value.company.employer_id);
    }
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

async function reportJob(targetType = 'job', targetId = job.value?.id) {
    if (!authenticated) { requestAuthentication(targetType === 'job' ? 'reportJob' : 'reportEmployer'); return; }
    reportTrigger = document.activeElement;
    reportTarget.value = { type: targetType, id: targetId };
    reportReason.value = '';
    reportDetails.value = '';
    reportError.value = '';
    reportDialog.value = true;
    await nextTick();
    reportReasonInput.value?.focus();
}

async function submitReport() {
    if (reporting.value) return;
    if (!reportReason.value) { reportError.value = 'Choose a reason for this report.'; return; }
    if (reportDetails.value.length > 2000) { reportError.value = 'Report details must be 2000 characters or fewer.'; return; }
    reporting.value = true; reportError.value = ''; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.post('/api/reports', {
            target_type: reportTarget.value.type,
            target_id: reportTarget.value.id,
            reason: reportReason.value,
            details: reportDetails.value.trim() || null,
        }, { authPromptContext: 'report' });
        notice.value = data.message;
        reporting.value = false;
        closeReportDialog();
    } catch (exception) {
        if (exception.response?.status === 401) {
            reporting.value = false;
            closeReportDialog();
            requestAuthentication('report');
            return;
        }
        const validation = exception.response?.data?.errors;
        reportError.value = validation ? Object.values(validation).flat().join(' ') : exception.response?.data?.message ?? 'Could not submit this report.';
    } finally { reporting.value = false; }
}

function closeReportDialog() {
    if (!reporting.value) {
        reportDialog.value = false;
        nextTick(() => reportTrigger?.focus?.());
    }
}

function scrollToApplication() {
    if (!canApply.value) {
        if (!authenticated) {
            requestAuthentication('apply');
            return;
        }
        error.value = role !== 'job_seeker' ? 'Only job seeker accounts can apply for jobs.' : 'Your job seeker profile is inactive. Update your profile before applying.';
        return;
    }
    applicationForm.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function onHashChange() { loadJob(); }
function onReportKeydown(event) {
    if (!reportDialog.value) return;
    if (event.key === 'Escape') { closeReportDialog(); return; }
    if (event.key !== 'Tab') return;
    const dialog = document.querySelector('[aria-labelledby="report-dialog-title"]');
    const focusable = dialog?.querySelectorAll('button:not(:disabled), select:not(:disabled), textarea:not(:disabled)');
    if (!focusable?.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}
onMounted(() => { window.addEventListener('hashchange', onHashChange); window.addEventListener('keydown', onReportKeydown); loadJob(); });
onUnmounted(() => { window.removeEventListener('hashchange', onHashChange); window.removeEventListener('keydown', onReportKeydown); });
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <a href="/#search" class="inline-flex items-center gap-1 text-sm font-semibold text-[var(--brand-primary)] hover:underline"><i class="ti ti-arrow-left" aria-hidden="true"/>{{ $t('Back to jobs') }}</a>
            <div v-if="error && !job && !loading" class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900" role="alert">{{ $t(error) }}<a href="/jobs" class="ml-2 font-semibold underline">{{ $t('Browse jobs') }}</a></div>
            <div v-else-if="loading" class="mt-6 rounded-xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500">{{ $t('Loading job details…') }}</div>
            <template v-else-if="job">
                <p v-if="error" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $t(error) }}</p><p v-if="notice" class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ $t(notice) }}</p>
                <div class="mt-5 grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_310px]">
                    <div class="space-y-5">
                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8">
                            <div class="flex flex-wrap items-start justify-between gap-4"><div class="min-w-0"><p class="text-sm font-semibold text-[var(--brand-primary)]">{{ job.company.name }}<span v-if="job.company.is_verified" class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-800"><i class="ti ti-circle-check" aria-hidden="true"/>{{ $t('Verified') }}</span></p><h1 class="mt-2 text-2xl font-bold tracking-tight text-[var(--brand-ink)] sm:text-3xl">{{ job.title }}</h1><p class="mt-2 text-sm text-slate-600">{{ job.location }} · {{ postedLabel(job.posted_at) }}</p><span v-if="job.is_featured" class="mt-2 inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-900"><i class="ti ti-ad" aria-hidden="true"/>{{ $t('Sponsored') }}</span></div><span class="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-900">{{ $t(formatLabel(job.employment_type)) }}<span v-if="job.experience_level"> · {{ $t(formatLabel(job.experience_level)) }}</span></span></div>
                            <div class="mt-6 grid gap-4 border-y border-slate-100 py-5 sm:grid-cols-3"><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Monthly salary') }}</p><p class="mt-1 font-semibold text-slate-900">{{ formatSalary() }}</p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Work arrangement') }}</p><p class="mt-1 font-semibold text-slate-900">{{ $t(formatLabel(job.work_mode) || 'Not specified') }}</p></div><div><p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('Category') }}</p><p class="mt-1 font-semibold text-slate-900">{{ job.category }}</p></div></div>
                            <div class="mt-6 flex flex-wrap gap-3"><button v-if="role !== 'employer'" type="button" :disabled="hasApplied" class="rounded-lg bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-[var(--brand-primary-hover)] disabled:cursor-default disabled:opacity-70" @click="scrollToApplication">{{ hasApplied ? $t('Application submitted') : $t('Apply for this job') }}</button><button v-if="role !== 'employer'" type="button" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="toggleSaved"><i :class="saved ? 'ti ti-bookmark-filled text-blue-700' : 'ti ti-bookmark'" aria-hidden="true"/>{{ saved ? $t('Saved') : $t('Save job') }}</button><a v-if="role === 'employer' && isOwnListing" href="/#dashboard" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]"><i class="ti ti-briefcase" aria-hidden="true"/>{{ $t('Manage your listing') }}</a><button type="button" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="shareJob"><i class="ti ti-share" aria-hidden="true"/>{{ $t('Share') }}</button><button v-if="!isOwnListing && role !== 'admin'" type="button" :disabled="reporting" class="inline-flex items-center gap-2 rounded-lg border border-amber-200 px-4 py-3 text-sm font-semibold text-amber-900 hover:bg-amber-50 disabled:opacity-50" @click="reportJob('job')"><i class="ti ti-flag" aria-hidden="true"/>{{ reporting ? $t('Submitting…') : $t('Report listing') }}</button><button v-if="!isOwnListing && role !== 'admin'" type="button" :disabled="reporting" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="reportJob('employer', job.company.employer_id)"><i class="ti ti-building-exclamation" aria-hidden="true"/>{{ $t('Report employer') }}</button></div>
                            <p v-if="role === 'employer'" class="mt-3 rounded-lg bg-blue-50 px-4 py-3 text-sm leading-6 text-blue-950">{{ isOwnListing ? $t('This is one of your company’s listings. Manage its status and applications from your employer dashboard.') : $t('Market reference listing published by another employer. Applications are available to job seekers.') }}<a v-if="isOwnListing" href="/#dashboard" class="ml-1 font-semibold underline">{{ $t('Open employer dashboard') }}</a></p>
                            <p v-if="hasApplied" class="mt-3 text-sm text-emerald-800" role="status">{{ $t('You applied for this job. Current status:') }} <span class="font-semibold capitalize">{{ $t(formatLabel(applicationStatus)) }}</span>.</p>
                            <p v-if="job.application_deadline" class="mt-3 text-xs text-slate-500">{{ $t('Applications close') }} {{ new Date(job.application_deadline).toLocaleDateString() }}.</p>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><h2 class="text-lg font-bold text-slate-900">{{ $t('About the role') }}</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ job.description }}</p><template v-if="requirementsList(job.requirements).length"><h3 class="mt-7 text-base font-bold text-slate-900">{{ $t('Requirements') }}</h3><ul class="mt-3 space-y-2"> <li v-for="(requirement, index) in requirementsList(job.requirements)" :key="index" class="flex gap-2.5 text-sm leading-6 text-slate-700"><i class="ti ti-check mt-1 shrink-0 text-blue-700" aria-hidden="true"/><span>{{ requirement }}</span></li></ul></template></article>

                        <article v-if="role !== 'employer'" ref="applicationForm" class="scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8"><h2 class="text-lg font-bold text-slate-900">Apply for {{ job.title }}</h2><p class="mt-1 text-sm text-slate-600">Your application and contact details will be shared with this employer.</p>
                            <div v-if="!authenticated" class="mt-5 rounded-lg bg-blue-50 p-4 text-sm text-blue-950">{{ $t('Create an account to submit an application. You can continue browsing jobs without one.') }}</div>
                            <div v-else-if="role !== 'job_seeker'" class="mt-5 rounded-lg bg-slate-100 p-4 text-sm text-slate-700">This account is registered as an employer and cannot apply for jobs.</div>
                            <div v-else-if="hasApplied" class="mt-5 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900">Your application has been received. You can follow its status from your profile.</div>
                            <form v-else class="mt-5 space-y-4" @submit.prevent="submitApplication"><p v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ $t(error) }}</p><p v-if="notice" class="rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ $t(notice) }}</p><div v-if="hasResume" class="rounded-lg bg-blue-50 px-4 py-3 text-sm text-blue-950"><i class="ti ti-file-check mr-1" aria-hidden="true"/>Your profile CV will be attached{{ resumeName ? ` (${resumeName})` : '' }}. Upload a new file below to use a different CV.</div><label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">Cover letter <span class="font-normal text-slate-500">(optional)</span></span><textarea v-model="coverLetter" rows="5" maxlength="5000" placeholder="Briefly explain your interest and relevant experience." class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100"/></label><label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">CV or resume <span class="font-normal text-slate-500">(optional if your profile has one)</span></span><input type="file" accept=".pdf,.doc,.docx" class="block w-full rounded-lg border border-slate-300 p-2 text-sm text-slate-700 file:mr-3 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-blue-900" @change="handleCv"><span class="mt-1 block text-xs text-slate-500">PDF, DOC, or DOCX · up to 10 MB</span></label><button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-5 py-3 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:cursor-wait disabled:opacity-60"><i :class="submitting ? 'ti ti-loader-2 animate-spin' : 'ti ti-send'" aria-hidden="true"/>{{ submitting ? 'Submitting…' : 'Submit application' }}</button></form>
                        </article>
                    </div>

                    <aside class="space-y-5"><section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="text-xs font-bold uppercase tracking-wide text-slate-500">About the company</h2><a :href="`/companies/${job.company.employer_id}`" class="mt-2 inline-block text-lg font-bold text-slate-900 hover:text-blue-800 hover:underline">{{ job.company.name }}</a><div class="mt-2 flex items-center gap-2"><span v-if="job.company.average_rating !== null" class="inline-flex items-center gap-1 font-semibold text-amber-700"><i class="ti ti-star-filled" aria-hidden="true"/>{{ Number(job.company.average_rating).toFixed(1) }}</span><span class="text-sm text-slate-500">{{ job.company.review_count }} {{ job.company.review_count === 1 ? 'review' : 'reviews' }}</span></div><p class="mt-1 text-sm text-slate-600">{{ job.company.location || job.location }}</p><p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{{ job.company.description || 'Company details have not been added yet.' }}</p><a v-if="job.company.website_url" :href="job.company.website_url" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block text-sm font-semibold text-blue-800 hover:underline">Company website</a><div v-if="Object.values(job.company.social_links ?? {}).some(Boolean)" class="mt-3 flex flex-wrap gap-3 text-sm"><template v-for="(url, platform) in job.company.social_links" :key="platform"><a v-if="url" :href="url" target="_blank" rel="noopener noreferrer" class="font-medium capitalize text-blue-800 hover:underline">{{ platform === 'x' ? 'X' : platform }}</a></template></div><div v-if="job.company.reviews?.length" class="mt-4 space-y-3 border-t border-slate-100 pt-4"><article v-for="(review, index) in job.company.reviews" :key="`${review.created_at}-${index}`" class="text-sm"><p class="font-semibold text-slate-800"><i class="ti ti-star-filled mr-1 text-amber-600" aria-hidden="true"/>{{ review.rating }}/5 <span v-if="review.title">· {{ review.title }}</span></p><p class="mt-1 text-slate-600">{{ review.review }}</p></article></div><a :href="`/companies/${job.company.employer_id}`" class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-blue-800 hover:underline">View company profile<i class="ti ti-arrow-right" aria-hidden="true"/></a></section><section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold text-slate-900">Similar jobs</h2><div v-if="similarJobs.length" class="mt-3 divide-y divide-slate-100"><a v-for="similar in similarJobs" :key="similar.id" :href="`/#details?job=${similar.id}`" class="block py-3 first:pt-0"><span class="block text-sm font-semibold text-blue-900 hover:underline">{{ similar.title }}</span><span class="mt-1 block text-xs text-slate-500">{{ similar.company }} · {{ similar.location }}</span></a></div><p v-else class="mt-3 text-sm text-slate-500">No similar openings are available right now.</p></section></aside>
                </div>
            </template>
        </div>
    </section>
    <Teleport to="body">
        <div v-if="reportDialog" class="fixed inset-0 z-[100] flex items-end justify-center bg-slate-950/55 p-0 backdrop-blur-[2px] sm:items-center sm:p-5" @click.self="closeReportDialog">
            <section class="w-full max-w-xl rounded-t-2xl bg-white p-5 shadow-2xl sm:rounded-2xl sm:p-7" role="dialog" aria-modal="true" aria-labelledby="report-dialog-title" aria-describedby="report-dialog-description">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <div class="mb-3 grid h-11 w-11 place-items-center rounded-xl bg-amber-50 text-xl text-amber-800"><i class="ti ti-flag" aria-hidden="true"/></div>
                        <h2 id="report-dialog-title" class="text-xl font-bold text-slate-950">{{ $t('Report content') }}</h2>
                        <p id="report-dialog-description" class="mt-1 text-sm leading-6 text-slate-600">{{ $t(reportTarget.type === 'job' ? 'Tell us why this listing should be reviewed.' : 'Tell us why this employer should be reviewed.') }}</p>
                    </div>
                    <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-lg text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-4 focus:ring-blue-100" :aria-label="$t('Close')" :disabled="reporting" @click="closeReportDialog"><i class="ti ti-x" aria-hidden="true"/></button>
                </div>
                <form class="mt-5 space-y-4" @submit.prevent="submitReport">
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $t('Reason') }} <span class="text-red-700">*</span></span>
                        <select ref="reportReasonInput" v-model="reportReason" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-100">
                            <option value="" disabled>{{ $t('Select a reason') }}</option>
                            <option value="fraud">{{ $t('Fraud or scam') }}</option>
                            <option value="misleading">{{ $t('Misleading information') }}</option>
                            <option value="abusive">{{ $t('Abusive or harmful content') }}</option>
                            <option value="inappropriate">{{ $t('Inappropriate content') }}</option>
                            <option value="other">{{ $t('Other reason') }}</option>
                        </select>
                    </label>
                    <label class="block"><span class="mb-1.5 block text-sm font-semibold text-slate-700">{{ $t('Additional details') }} <span class="font-normal text-slate-500">({{ $t('optional') }})</span></span>
                        <textarea v-model="reportDetails" rows="4" maxlength="2000" :placeholder="$t('Share any details that may help our review team.')" class="w-full resize-y rounded-lg border border-slate-300 px-3.5 py-3 text-sm leading-6 text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-100"/>
                        <span class="mt-1 block text-right text-xs text-slate-500">{{ reportDetails.length }}/2000</span>
                    </label>
                    <p v-if="reportError" class="rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-800" role="alert">{{ $t(reportError) }}</p>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-100 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" :disabled="reporting" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-60" @click="closeReportDialog">{{ $t('Cancel') }}</button>
                        <button type="submit" :disabled="reporting || !reportReason" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:cursor-wait disabled:opacity-60"><i :class="reporting ? 'ti ti-loader-2 animate-spin' : 'ti ti-send'" aria-hidden="true"/>{{ reporting ? $t('Submitting report…') : $t('Submit report') }}</button>
                    </div>
                </form>
            </section>
        </div>
    </Teleport>
</template>
