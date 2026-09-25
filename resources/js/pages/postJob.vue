<script setup>
import { reactive, ref } from 'vue';

const saving = ref(false);
const error = ref('');
const notice = ref('');
const jobDraft = window.__employerJobDraft ?? null;
const editingId = ref(jobDraft?.id ?? null);
const form = reactive({
    title: '', category: '', location: '', employment_type: 'full_time',
    experience_level: '', work_mode: 'on_site', salary_min: '', salary_max: '',
    application_deadline: '', description: '', requirements: '',
});
if (jobDraft) Object.assign(form, {
    title: jobDraft.title ?? '', category: jobDraft.category ?? '', location: jobDraft.location ?? '',
    employment_type: jobDraft.employment_type ?? 'full_time', experience_level: jobDraft.experience_level ?? '',
    work_mode: jobDraft.work_mode ?? 'on_site', salary_min: jobDraft.salary_min ?? '', salary_max: jobDraft.salary_max ?? '',
    application_deadline: jobDraft.application_deadline ?? '', description: jobDraft.description ?? '', requirements: jobDraft.requirements ?? '',
});
const categories = ['Administration', 'Customer Service', 'Education', 'Engineering', 'Finance', 'Healthcare', 'Human Resources', 'Hospitality', 'Logistics', 'Marketing', 'Sales', 'Technology', 'Other'];

async function publishJob() {
    saving.value = true;
    error.value = '';
    notice.value = '';
    const payload = { ...form };
    for (const key of ['salary_min', 'salary_max']) payload[key] = payload[key] === '' ? null : Number(payload[key]);
    for (const key of ['experience_level', 'application_deadline', 'requirements']) if (!payload[key]) payload[key] = null;
    try {
        const { data } = editingId.value
            ? await window.axios.patch(`/api/employer/jobs/${editingId.value}`, payload)
            : await window.axios.post('/api/employer/jobs', payload);
        notice.value = data.message || (editingId.value ? 'Job updated.' : 'Your job is live.');
        delete window.__employerJobDraft;
        window.location.hash = 'dashboard';
    } catch (exception) {
        error.value = exception.response?.data?.errors
            ? Object.values(exception.response.data.errors).flat().join(' ')
            : exception.response?.data?.message ?? 'Could not publish this job. Please check the fields and try again.';
    } finally { saving.value = false; }
}
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl">
            <div class="mb-6"><a href="/#dashboard" class="inline-flex items-center gap-1 text-sm font-semibold text-[var(--brand-primary)] hover:underline"><i class="ti ti-arrow-left" aria-hidden="true" /> Employer dashboard</a><p class="mt-5 text-sm font-semibold text-[var(--brand-primary)]">Hiring</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">{{ editingId ? 'Edit job listing' : 'Post a job' }}</h1><p class="mt-2 text-sm leading-6 text-slate-600">Share a clear description and requirements. Your published listing will be visible in job search.</p></div>
            <form class="space-y-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" @submit.prevent="publishJob">
                <p v-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>
                <p v-if="notice" class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <label class="sm:col-span-2"><span class="form-label">Job title <b class="text-red-600">*</b></span><input v-model="form.title" required maxlength="255" placeholder="e.g. Senior Frontend Developer" class="form-field"></label>
                    <label><span class="form-label">Job category <b class="text-red-600">*</b></span><select v-model="form.category" required class="form-field"><option value="" disabled>Select a category</option><option v-for="category in categories" :key="category">{{ category }}</option></select></label>
                    <label><span class="form-label">Location <b class="text-red-600">*</b></span><input v-model="form.location" required maxlength="255" placeholder="Yangon, Myanmar or Remote" class="form-field"></label>
                    <label><span class="form-label">Employment type <b class="text-red-600">*</b></span><select v-model="form.employment_type" required class="form-field"><option value="full_time">Full time</option><option value="part_time">Part time</option><option value="contract">Contract</option><option value="temporary">Temporary</option><option value="internship">Internship</option></select></label>
                    <label><span class="form-label">Experience level</span><select v-model="form.experience_level" class="form-field"><option value="">Any experience</option><option value="entry">Entry level</option><option value="junior">Junior</option><option value="mid">Mid level</option><option value="senior">Senior</option><option value="lead">Lead</option><option value="executive">Executive</option></select></label>
                    <label><span class="form-label">Work mode</span><select v-model="form.work_mode" class="form-field"><option value="on_site">On site</option><option value="hybrid">Hybrid</option><option value="remote">Remote</option></select></label>
                    <label><span class="form-label">Application deadline</span><input v-model="form.application_deadline" type="date" :min="new Date(Date.now() + 86400000).toISOString().slice(0, 10)" class="form-field"></label>
                    <fieldset class="sm:col-span-2"><legend class="form-label">Monthly salary range <span class="font-normal text-slate-500">(MMK, optional)</span></legend><div class="grid gap-3 sm:grid-cols-2"><label><span class="sr-only">Minimum salary</span><input v-model="form.salary_min" type="number" min="0" step="1000" placeholder="Minimum" class="form-field"></label><label><span class="sr-only">Maximum salary</span><input v-model="form.salary_max" type="number" min="0" step="1000" placeholder="Maximum" class="form-field"></label></div></fieldset>
                    <label class="sm:col-span-2"><span class="form-label">Job description <b class="text-red-600">*</b></span><textarea v-model="form.description" required maxlength="20000" rows="7" placeholder="Describe the role, responsibilities, and what success looks like." class="form-field"></textarea><span class="mt-1 block text-right text-xs text-slate-500">{{ form.description.length }}/20,000</span></label>
                    <label class="sm:col-span-2"><span class="form-label">Requirements <span class="font-normal text-slate-500">(optional)</span></span><textarea v-model="form.requirements" maxlength="20000" rows="5" placeholder="Qualifications, skills, and experience. One requirement per line works well." class="form-field"></textarea></label>
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-5"><p class="max-w-md text-xs leading-5 text-slate-500">By publishing, you confirm that this listing is accurate and complies with applicable employment laws.</p><div class="flex gap-3"><a href="/#dashboard" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a><button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--brand-primary-hover)] disabled:cursor-wait disabled:opacity-60"><i :class="saving ? 'ti ti-loader-2 animate-spin' : 'ti ti-send'" aria-hidden="true" />{{ saving ? 'Saving…' : editingId ? 'Save changes' : 'Publish job' }}</button></div></div>
            </form>
        </div>
    </section>
</template>

<style scoped>
@reference "../../css/app.css";
.form-label { @apply mb-1.5 block text-sm font-semibold text-slate-700; }
.form-field { @apply mt-1 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100; }
</style>
