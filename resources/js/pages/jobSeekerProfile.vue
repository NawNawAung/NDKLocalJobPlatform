<script setup>
import { computed, onUnmounted, reactive, ref } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const source = bootstrap.profile ?? {};
const old = bootstrap.old ?? {};
const socialPlatforms = [
    { key: 'linkedin', label: 'LinkedIn' },
    { key: 'github', label: 'GitHub' },
    { key: 'facebook', label: 'Facebook' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'x', label: 'X' },
];
const employmentOptions = [
    { value: 'full_time', label: 'Full time' }, { value: 'part_time', label: 'Part time' },
    { value: 'contract', label: 'Contract' }, { value: 'temporary', label: 'Temporary' }, { value: 'internship', label: 'Internship' },
];
const workModeOptions = [
    { value: 'on_site', label: 'On site' }, { value: 'hybrid', label: 'Hybrid' }, { value: 'remote', label: 'Remote' }, { value: 'any', label: 'Any' },
];
const availabilityOptions = [
    { value: 'immediately', label: 'Immediately' }, { value: 'two_weeks', label: 'Within two weeks' },
    { value: 'one_month', label: 'Within one month' }, { value: 'not_looking', label: 'Not currently looking' },
];

const profile = reactive({
    ...source,
    user: { name: '', email: '', ...(source.user ?? {}) },
    social_links: { linkedin: '', github: '', facebook: '', instagram: '', x: '', ...(source.social_links ?? {}) },
    experiences: (source.experiences ?? []).map((item) => ({ ...item, started_on: item.started_on?.slice(0, 10) ?? '', ended_on: item.ended_on?.slice(0, 10) ?? '' })),
    educations: (source.educations ?? []).map((item) => ({ ...item })),
});
const draft = reactive({
    name: old.name ?? profile.user.name,
    email: old.email ?? profile.user.email,
    phone: old.phone ?? profile.phone ?? '',
    region_id: String(old.region_id ?? profile.region_id ?? ''),
    township_id: String(old.township_id ?? profile.township_id ?? ''),
    professional_title: old.professional_title ?? profile.professional_title ?? '',
    years_experience: old.years_experience ?? profile.years_experience ?? '',
    bio: old.bio ?? profile.bio ?? '',
    portfolio_url: old.portfolio_url ?? profile.portfolio_url ?? '',
    social_links: { ...profile.social_links, ...(old.social_links ?? {}) },
    skills_text: old.skills_text ?? (profile.skills ?? []).join(', '),
    languages_text: old.languages_text ?? (profile.languages ?? []).join(', '),
    desired_job_title: old.desired_job_title ?? profile.desired_job_title ?? '',
    employment_type: old.employment_type ?? profile.employment_type ?? '',
    work_mode: old.work_mode ?? profile.work_mode ?? '',
    availability: old.availability ?? profile.availability ?? '',
    expected_salary_min: old.expected_salary_min ?? profile.expected_salary_min ?? '',
    expected_salary_max: old.expected_salary_max ?? profile.expected_salary_max ?? '',
    job_alerts_enabled: Boolean(old.job_alerts_enabled ?? profile.job_alerts_enabled),
    profile_searchable: Boolean(old.profile_searchable ?? profile.profile_searchable),
    experiences: (old.experiences ?? profile.experiences).map((item) => ({ ...item })),
    educations: (old.educations ?? profile.educations).map((item) => ({ ...item })),
    password: '',
    password_confirmation: '',
});
const initialSnapshot = ref('');
const saving = ref(false);
const successMessage = ref(bootstrap.status ?? '');
const errorMessage = ref('');
const errors = reactive({ ...(bootstrap.errors ?? {}) });
const photoInput = ref(null);
const cvInput = ref(null);
const photoSelected = ref(false);
const cvSelected = ref(false);
const regions = computed(() => bootstrap.regions ?? []);
const townships = computed(() => regions.value.find((item) => String(item.id) === String(draft.region_id))?.townships ?? []);
const initials = computed(() => profile.user.name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase());

function snapshot() {
    const { password, password_confirmation, ...safeDraft } = draft;
    return JSON.stringify(safeDraft);
}
initialSnapshot.value = snapshot();
const hasChanges = computed(() => snapshot() !== initialSnapshot.value || photoSelected.value || cvSelected.value || Boolean(draft.password));
const skillsForCompleteness = computed(() => draft.skills_text.split(/[\n,]+/).map((value) => value.trim()).filter(Boolean));
const languagesForCompleteness = computed(() => draft.languages_text.split(/[\n,]+/).map((value) => value.trim()).filter(Boolean));
const completenessItems = computed(() => [
    { label: 'Name and email', done: Boolean(draft.name && draft.email), points: 8 },
    { label: 'Phone number', done: Boolean(draft.phone), points: 4 },
    { label: 'Region or state', done: Boolean(draft.region_id), points: 8 },
    { label: 'Professional headline', done: Boolean(draft.professional_title), points: 8 },
    { label: 'Professional summary', done: Boolean(draft.bio.trim()), points: 8 },
    { label: 'Skills', done: skillsForCompleteness.value.length > 0, points: 10 },
    { label: 'Languages', done: languagesForCompleteness.value.length > 0, points: 4 },
    { label: 'Experience level', done: draft.years_experience !== '' && draft.years_experience !== null, points: 4 },
    { label: 'Desired role', done: Boolean(draft.desired_job_title), points: 3 },
    { label: 'Employment type', done: Boolean(draft.employment_type), points: 2 },
    { label: 'Work mode', done: Boolean(draft.work_mode), points: 2 },
    { label: 'Availability', done: Boolean(draft.availability), points: 3 },
    { label: 'Resume / CV', done: Boolean(profile.cv_path) || Boolean(cvInput.value?.files?.length), points: 10 },
    { label: 'Work experience', done: draft.experiences.some((item) => item.job_title && item.employer_name), points: 15 },
    { label: 'Education', done: draft.educations.some((item) => item.institution), points: 11 },
]);
const completeness = computed(() => completenessItems.value.reduce((total, item) => total + (item.done ? item.points : 0), 0));
const incompleteItems = computed(() => completenessItems.value.filter((item) => !item.done));
const employerReviewStatuses = ['shortlisted', 'interview', 'offered', 'hired', 'rejected'];
const reviewDrafts = reactive({});
const reviewOpenId = ref(null);
const reviewSavingId = ref(null);
const reviewError = ref('');

function fieldError(field) { return errors[field]?.[0] ?? ''; }
function nestedError(section, index, field) { return errors[`${section}.${index}.${field}`]?.[0] ?? ''; }
function clearError(field) { delete errors[field]; }
function clearNestedError(section, index, field) { delete errors[`${section}.${index}.${field}`]; }
function selectFile(field, event) {
    if (field === 'profile_photo') photoSelected.value = Boolean(event.target.files?.length);
    if (field === 'cv') cvSelected.value = Boolean(event.target.files?.length);
    clearError(field);
}
function onRegionChange() {
    draft.township_id = '';
    clearError('township_id');
}
function addExperience() {
    draft.experiences.push({ job_title: '', employer_name: '', location: '', started_on: '', ended_on: '', is_current: false, description: '' });
}
function addEducation() {
    draft.educations.push({ institution: '', qualification: '', field_of_study: '', started_year: '', graduated_year: '', description: '' });
}
function removeExperience(index) { draft.experiences.splice(index, 1); }
function removeEducation(index) { draft.educations.splice(index, 1); }

function buildFormData() {
    const data = new FormData();
    const scalarFields = [
        'name', 'email', 'password', 'password_confirmation', 'phone', 'region_id', 'township_id',
        'professional_title', 'years_experience', 'bio', 'portfolio_url', 'skills_text', 'languages_text',
        'desired_job_title', 'employment_type', 'work_mode', 'availability', 'expected_salary_min', 'expected_salary_max',
    ];
    data.append('_method', 'PATCH');
    scalarFields.forEach((field) => {
        if (draft[field] !== '' && draft[field] !== null && draft[field] !== undefined) data.append(field, draft[field]);
    });
    data.append('job_alerts_enabled', draft.job_alerts_enabled ? '1' : '0');
    data.append('profile_searchable', draft.profile_searchable ? '1' : '0');
    socialPlatforms.forEach(({ key }) => data.append(`social_links[${key}]`, draft.social_links[key] ?? ''));
    draft.experiences.forEach((item, index) => {
        ['job_title', 'employer_name', 'location', 'started_on', 'ended_on', 'description'].forEach((field) => {
            data.append(`experiences[${index}][${field}]`, item[field] ?? '');
        });
        data.append(`experiences[${index}][is_current]`, item.is_current ? '1' : '0');
    });
    draft.educations.forEach((item, index) => {
        ['institution', 'qualification', 'field_of_study', 'started_year', 'graduated_year', 'description'].forEach((field) => {
            data.append(`educations[${index}][${field}]`, item[field] ?? '');
        });
    });
    if (photoInput.value?.files?.[0]) data.append('profile_photo', photoInput.value.files[0]);
    if (cvInput.value?.files?.[0]) data.append('cv', cvInput.value.files[0]);
    return data;
}

function updateProfileFromResponse(updated) {
    Object.assign(profile, updated);
    profile.user = updated.user ?? profile.user;
    profile.social_links = { linkedin: '', github: '', facebook: '', instagram: '', x: '', ...(updated.social_links ?? {}) };
    profile.experiences = (updated.experiences ?? []).map((item) => ({ ...item, started_on: item.started_on?.slice(0, 10) ?? '', ended_on: item.ended_on?.slice(0, 10) ?? '' }));
    profile.educations = (updated.educations ?? []).map((item) => ({ ...item }));
    Object.assign(draft, {
        name: profile.user.name,
        email: profile.user.email,
        phone: profile.phone ?? '',
        region_id: String(profile.region_id ?? ''),
        township_id: String(profile.township_id ?? ''),
        professional_title: profile.professional_title ?? '',
        years_experience: profile.years_experience ?? '',
        bio: profile.bio ?? '',
        portfolio_url: profile.portfolio_url ?? '',
        social_links: { ...profile.social_links },
        skills_text: (profile.skills ?? []).join(', '),
        languages_text: (profile.languages ?? []).join(', '),
        desired_job_title: profile.desired_job_title ?? '',
        employment_type: profile.employment_type ?? '',
        work_mode: profile.work_mode ?? '',
        availability: profile.availability ?? '',
        expected_salary_min: profile.expected_salary_min ?? '',
        expected_salary_max: profile.expected_salary_max ?? '',
        job_alerts_enabled: Boolean(profile.job_alerts_enabled),
        profile_searchable: Boolean(profile.profile_searchable),
        experiences: profile.experiences.map((item) => ({ ...item })),
        educations: profile.educations.map((item) => ({ ...item })),
        password: '',
        password_confirmation: '',
    });
    if (photoInput.value) photoInput.value.value = '';
    if (cvInput.value) cvInput.value.value = '';
    photoSelected.value = false;
    cvSelected.value = false;
    initialSnapshot.value = snapshot();
    window.dispatchEvent(new CustomEvent('profile-updated', { detail: { name: profile.user.name, email: profile.user.email, photoUrl: profile.profile_photo_url ?? '' } }));
}

let successTimer;
onUnmounted(() => clearTimeout(successTimer));
async function saveProfile() {
    if (saving.value || !hasChanges.value) return;
    saving.value = true;
    successMessage.value = '';
    errorMessage.value = '';
    Object.keys(errors).forEach((key) => delete errors[key]);
    try {
        const { data } = await window.axios.post('/profile', buildFormData(), { headers: { Accept: 'application/json' } });
        updateProfileFromResponse(data.profile);
        successMessage.value = data.message ?? 'Your profile has been saved.';
        clearTimeout(successTimer);
        successTimer = setTimeout(() => { successMessage.value = ''; }, 5000);
    } catch (exception) {
        if (exception.response?.status === 422 && exception.response.data?.errors) {
            Object.assign(errors, exception.response.data.errors);
            errorMessage.value = 'Some fields need your attention. Review the messages below.';
            requestAnimationFrame(() => document.querySelector('[data-field-error]')?.scrollIntoView({ behavior: 'smooth', block: 'center' }));
        } else {
            errorMessage.value = exception.response?.data?.message ?? 'Could not save your profile. Please try again.';
        }
    } finally { saving.value = false; }
}

function canReviewEmployer(application) {
    const employerId = application.job?.employer?.id;
    return employerId && employerReviewStatuses.includes(application.status) && !profile.employer_reviews_by_id?.[employerId];
}
function toggleEmployerReview(application) {
    const employerId = application.job.employer.id;
    reviewOpenId.value = reviewOpenId.value === employerId ? null : employerId;
    reviewDrafts[employerId] ??= { rating: 5, title: '', review: '' };
    reviewError.value = '';
}
async function submitEmployerReview(application) {
    const employerId = application.job?.employer?.id;
    if (!employerId || reviewSavingId.value) return;
    reviewSavingId.value = employerId;
    reviewError.value = '';
    try {
        const { data } = await window.axios.post(`/api/applications/${application.id}/employer-review`, reviewDrafts[employerId]);
        profile.employer_reviews_by_id ??= {};
        profile.employer_reviews_by_id[employerId] = { status: data.status, rating: reviewDrafts[employerId].rating };
        reviewOpenId.value = null;
        successMessage.value = data.message;
    } catch (exception) {
        reviewError.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not submit the employer review.';
    } finally { reviewSavingId.value = null; }
}
</script>

<template>
    <section v-if="profile.user.name" class="min-h-[60vh] bg-slate-50 px-4 py-8 pb-32 sm:px-8 sm:py-12 sm:pb-32">
        <div class="mx-auto max-w-6xl">
            <div v-if="successMessage" class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 shadow-sm" role="status" aria-live="polite"><i class="ti ti-circle-check mt-0.5 text-lg" aria-hidden="true" /><span>{{ successMessage }}</span><button type="button" class="ml-auto text-emerald-800 hover:text-emerald-950" aria-label="Dismiss message" @click="successMessage = ''"><i class="ti ti-x" aria-hidden="true" /></button></div>
            <div v-if="errorMessage" class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ errorMessage }}</div>

            <form id="profile-form" class="space-y-6" @submit.prevent="saveProfile">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="profile-heading">
                    <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
                        <div class="flex shrink-0 flex-col items-center gap-3">
                            <div class="grid h-24 w-24 place-items-center overflow-hidden rounded-full bg-blue-100 text-2xl font-bold text-blue-800 ring-4 ring-blue-50">
                                <img v-if="profile.profile_photo_url" :src="profile.profile_photo_url" :alt="`${draft.name} profile photo`" class="h-full w-full object-cover">
                                <span v-else>{{ initials }}</span>
                            </div>
                            <label for="profile-photo" class="cursor-pointer text-xs font-semibold text-blue-800 hover:underline">Change photo</label>
                            <input id="profile-photo" ref="photoInput" type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectFile('profile_photo', $event)">
                            <p v-if="fieldError('profile_photo')" class="field-error" data-field-error>{{ fieldError('profile_photo') }}</p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-700">Job seeker profile</p>
                            <h1 id="profile-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Your profile</h1>
                            <p class="mt-1 text-sm text-slate-600">Update your information directly below. Save Changes to apply your updates.</p>
                            <div class="mt-5 grid gap-4 sm:grid-cols-2">
                                <label class="form-label">Full name<input v-model="draft.name" name="name" required maxlength="255" autocomplete="name" class="form-field" :aria-invalid="Boolean(fieldError('name'))" @input="clearError('name')"><span v-if="fieldError('name')" class="field-error" data-field-error>{{ fieldError('name') }}</span></label>
                                <label class="form-label">Email address<input v-model="draft.email" name="email" required type="email" maxlength="255" autocomplete="email" class="form-field" :aria-invalid="Boolean(fieldError('email'))" @input="clearError('email')"><span v-if="fieldError('email')" class="field-error" data-field-error>{{ fieldError('email') }}</span></label>
                                <label class="form-label">Phone number<input v-model="draft.phone" name="phone" type="tel" maxlength="40" autocomplete="tel" class="form-field" :aria-invalid="Boolean(fieldError('phone'))" @input="clearError('phone')"><span v-if="fieldError('phone')" class="field-error" data-field-error>{{ fieldError('phone') }}</span></label>
                                <label class="form-label">Region or state<select v-model="draft.region_id" name="region_id" class="form-field" :aria-invalid="Boolean(fieldError('region_id'))" @change="onRegionChange"><option value="">Choose a region or state</option><option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }} {{ region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region' }}</option></select><span v-if="fieldError('region_id')" class="field-error" data-field-error>{{ fieldError('region_id') }}</span></label>
                                <label class="form-label">Township<select v-model="draft.township_id" name="township_id" :disabled="!draft.region_id || !townships.length" class="form-field disabled:bg-slate-50" :aria-invalid="Boolean(fieldError('township_id'))" @change="clearError('township_id')"><option value="">Select township</option><option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option></select><span v-if="fieldError('township_id')" class="field-error" data-field-error>{{ fieldError('township_id') }}</span></label>
                                <label class="form-label">Professional headline<input v-model="draft.professional_title" name="professional_title" maxlength="160" placeholder="e.g. Senior Software Engineer" class="form-field" :aria-invalid="Boolean(fieldError('professional_title'))" @input="clearError('professional_title')"><span v-if="fieldError('professional_title')" class="field-error" data-field-error>{{ fieldError('professional_title') }}</span></label>
                                <label class="form-label">Years of experience<input v-model="draft.years_experience" name="years_experience" type="number" min="0" max="60" class="form-field" :aria-invalid="Boolean(fieldError('years_experience'))" @input="clearError('years_experience')"><span v-if="fieldError('years_experience')" class="field-error" data-field-error>{{ fieldError('years_experience') }}</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Applications</p><p class="mt-1 text-xl font-bold text-slate-900">{{ profile.applications_count ?? 0 }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saved jobs</p><p class="mt-1 text-xl font-bold text-slate-900">{{ profile.saved_jobs_count ?? 0 }}</p></div>
                        <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Experience</p><p class="mt-1 text-xl font-bold text-slate-900">{{ draft.years_experience || '—' }}<span v-if="draft.years_experience !== ''" class="ml-1 text-sm font-medium">years</span></p></div>
                    </div>
                </section>

                <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                    <div class="space-y-6">
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="about-title">
                            <h2 id="about-title" class="text-lg font-bold text-slate-950">Professional summary</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="form-label">Summary<textarea v-model="draft.bio" name="bio" rows="5" maxlength="5000" placeholder="Summarise your experience, strengths, and career goals." class="form-field" :aria-invalid="Boolean(fieldError('bio'))" @input="clearError('bio')"/><span v-if="fieldError('bio')" class="field-error" data-field-error>{{ fieldError('bio') }}</span></label>
                                <div class="space-y-4"><label class="form-label">Skills <span class="font-normal text-slate-500">(separate with commas)</span><textarea v-model="draft.skills_text" name="skills_text" rows="2" maxlength="2000" placeholder="Communication, Excel, project management" class="form-field" :aria-invalid="Boolean(fieldError('skills_text'))" @input="clearError('skills_text')"/><span v-if="fieldError('skills_text')" class="field-error" data-field-error>{{ fieldError('skills_text') }}</span></label>
                                    <label class="form-label">Languages <span class="font-normal text-slate-500">(separate with commas)</span><textarea v-model="draft.languages_text" name="languages_text" rows="2" maxlength="1000" placeholder="Burmese, English" class="form-field" :aria-invalid="Boolean(fieldError('languages_text'))" @input="clearError('languages_text')"/><span v-if="fieldError('languages_text')" class="field-error" data-field-error>{{ fieldError('languages_text') }}</span></label></div>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="experience-title">
                            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="experience-title" class="text-lg font-bold text-slate-950">Work experience</h2><p class="mt-1 text-sm text-slate-500">Add or update your employment history.</p></div><button type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50" @click="addExperience"><i class="ti ti-plus mr-1" aria-hidden="true" />Add position</button></div>
                            <p v-if="fieldError('experiences')" class="field-error" data-field-error>{{ fieldError('experiences') }}</p>
                            <div v-if="draft.experiences.length" class="mt-5 space-y-4">
                                <fieldset v-for="(item, index) in draft.experiences" :key="item.id ?? `experience-${index}`" class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
                                    <legend class="px-2 text-sm font-semibold text-slate-700">Position {{ index + 1 }}</legend>
                                    <label class="form-label">Job title<input v-model="item.job_title" :name="`experiences[${index}][job_title]`" maxlength="160" class="form-field" @input="clearNestedError('experiences', index, 'job_title')"><span v-if="nestedError('experiences', index, 'job_title')" class="field-error" data-field-error>{{ nestedError('experiences', index, 'job_title') }}</span></label>
                                    <label class="form-label">Employer<input v-model="item.employer_name" :name="`experiences[${index}][employer_name]`" maxlength="160" class="form-field" @input="clearNestedError('experiences', index, 'employer_name')"><span v-if="nestedError('experiences', index, 'employer_name')" class="field-error" data-field-error>{{ nestedError('experiences', index, 'employer_name') }}</span></label>
                                    <label class="form-label">Location<input v-model="item.location" :name="`experiences[${index}][location]`" maxlength="160" class="form-field"></label>
                                    <div class="grid grid-cols-2 gap-3"><label class="form-label">Start date<input v-model="item.started_on" :name="`experiences[${index}][started_on]`" type="date" class="form-field" @input="clearNestedError('experiences', index, 'started_on')"><span v-if="nestedError('experiences', index, 'started_on')" class="field-error" data-field-error>{{ nestedError('experiences', index, 'started_on') }}</span></label><label class="form-label">End date<input v-model="item.ended_on" :name="`experiences[${index}][ended_on]`" type="date" :disabled="item.is_current" class="form-field disabled:bg-slate-50" @input="clearNestedError('experiences', index, 'ended_on')"><span v-if="nestedError('experiences', index, 'ended_on')" class="field-error" data-field-error>{{ nestedError('experiences', index, 'ended_on') }}</span></label></div>
                                    <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="item.is_current" :name="`experiences[${index}][is_current]`" type="checkbox" class="h-4 w-4 rounded border-slate-300 accent-blue-700">I currently work here</label>
                                    <label class="form-label sm:col-span-2">Responsibilities and achievements<textarea v-model="item.description" :name="`experiences[${index}][description]`" rows="3" maxlength="5000" class="form-field"/></label>
                                    <button type="button" class="justify-self-start text-sm font-semibold text-red-700 hover:underline" @click="removeExperience(index)">Remove position</button>
                                </fieldset>
                            </div>
                            <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-5 text-sm text-slate-600">No work experience added. Use Add position to include your history.</p>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="education-title">
                            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="education-title" class="text-lg font-bold text-slate-950">Education</h2><p class="mt-1 text-sm text-slate-500">Add your education and qualifications.</p></div><button type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50" @click="addEducation"><i class="ti ti-plus mr-1" aria-hidden="true" />Add education</button></div>
                            <p v-if="fieldError('educations')" class="field-error" data-field-error>{{ fieldError('educations') }}</p>
                            <div v-if="draft.educations.length" class="mt-5 space-y-4">
                                <fieldset v-for="(item, index) in draft.educations" :key="item.id ?? `education-${index}`" class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:grid-cols-2">
                                    <legend class="px-2 text-sm font-semibold text-slate-700">Education {{ index + 1 }}</legend>
                                    <label class="form-label">Institution<input v-model="item.institution" :name="`educations[${index}][institution]`" maxlength="180" class="form-field" @input="clearNestedError('educations', index, 'institution')"><span v-if="nestedError('educations', index, 'institution')" class="field-error" data-field-error>{{ nestedError('educations', index, 'institution') }}</span></label>
                                    <label class="form-label">Qualification<input v-model="item.qualification" :name="`educations[${index}][qualification]`" maxlength="160" class="form-field"></label>
                                    <label class="form-label">Field of study<input v-model="item.field_of_study" :name="`educations[${index}][field_of_study]`" maxlength="160" class="form-field"></label>
                                    <div class="grid grid-cols-2 gap-3"><label class="form-label">Start year<input v-model="item.started_year" :name="`educations[${index}][started_year]`" type="number" min="1900" :max="new Date().getFullYear()" class="form-field"><span v-if="nestedError('educations', index, 'started_year')" class="field-error" data-field-error>{{ nestedError('educations', index, 'started_year') }}</span></label><label class="form-label">Completion year<input v-model="item.graduated_year" :name="`educations[${index}][graduated_year]`" type="number" min="1900" :max="new Date().getFullYear() + 10" class="form-field"><span v-if="nestedError('educations', index, 'graduated_year')" class="field-error" data-field-error>{{ nestedError('educations', index, 'graduated_year') }}</span></label></div>
                                    <label class="form-label sm:col-span-2">Additional details<textarea v-model="item.description" :name="`educations[${index}][description]`" rows="2" maxlength="3000" class="form-field"/></label>
                                    <button type="button" class="justify-self-start text-sm font-semibold text-red-700 hover:underline" @click="removeEducation(index)">Remove education</button>
                                </fieldset>
                            </div>
                            <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-5 text-sm text-slate-600">No education added. Use Add education to include a qualification.</p>
                        </section>

                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="online-title">
                            <h2 id="online-title" class="text-lg font-bold text-slate-950">Portfolio and social profiles</h2>
                            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                <label class="form-label sm:col-span-2">Portfolio or personal website<input v-model="draft.portfolio_url" name="portfolio_url" type="url" maxlength="2048" placeholder="https://yourportfolio.com" class="form-field" :aria-invalid="Boolean(fieldError('portfolio_url'))" @input="clearError('portfolio_url')"><span v-if="fieldError('portfolio_url')" class="field-error" data-field-error>{{ fieldError('portfolio_url') }}</span></label>
                                <label v-for="platform in socialPlatforms" :key="platform.key" class="form-label">{{ platform.label }} profile<input v-model="draft.social_links[platform.key]" :name="`social_links[${platform.key}]`" type="url" maxlength="2048" :placeholder="`https://${platform.key === 'x' ? 'x.com' : platform.key + '.com'}/your-profile`" class="form-field" :aria-invalid="Boolean(fieldError(`social_links.${platform.key}`))" @input="clearError(`social_links.${platform.key}`)"><span v-if="fieldError(`social_links.${platform.key}`)" class="field-error" data-field-error>{{ fieldError(`social_links.${platform.key}`) }}</span></label>
                            </div>
                        </section>
                    </div>

                    <aside class="space-y-6 lg:sticky lg:top-24">
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="completeness-title">
                            <div class="flex items-center justify-between gap-3"><h2 id="completeness-title" class="font-bold text-slate-950">Profile completeness</h2><span class="text-lg font-bold text-blue-800">{{ completeness }}%</span></div>
                            <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="completeness" aria-valuemin="0" aria-valuemax="100" :aria-label="`Profile ${completeness}% complete`"><div class="h-full rounded-full bg-blue-700 transition-all" :style="{ width: `${completeness}%` }" /></div>
                            <p class="mt-3 text-sm leading-6 text-slate-600">{{ completeness === 100 ? 'Your profile has all recommended sections.' : 'Complete more sections to give employers a clearer view of your experience.' }}</p>
                            <ul v-if="incompleteItems.length" class="mt-4 space-y-2 border-t border-slate-100 pt-4"><li v-for="item in incompleteItems" :key="item.label" class="flex items-start gap-2 text-sm text-slate-600"><i class="ti ti-circle-dashed mt-0.5 text-blue-600" aria-hidden="true" /><span>{{ item.label }}</span></li></ul>
                        </section>
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="preferences-title">
                            <h2 id="preferences-title" class="font-bold text-slate-950">Job preferences</h2>
                            <div class="mt-4 space-y-4">
                                <label class="form-label">Desired job title<input v-model="draft.desired_job_title" name="desired_job_title" maxlength="160" class="form-field"></label>
                                <label class="form-label">Employment type<select v-model="draft.employment_type" name="employment_type" class="form-field" :aria-invalid="Boolean(fieldError('employment_type'))" @change="clearError('employment_type')"><option value="">Select employment type</option><option v-for="option in employmentOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><span v-if="fieldError('employment_type')" class="field-error" data-field-error>{{ fieldError('employment_type') }}</span></label>
                                <label class="form-label">Work mode<select v-model="draft.work_mode" name="work_mode" class="form-field" :aria-invalid="Boolean(fieldError('work_mode'))" @change="clearError('work_mode')"><option value="">Select work mode</option><option v-for="option in workModeOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><span v-if="fieldError('work_mode')" class="field-error" data-field-error>{{ fieldError('work_mode') }}</span></label>
                                <label class="form-label">Availability<select v-model="draft.availability" name="availability" class="form-field" :aria-invalid="Boolean(fieldError('availability'))" @change="clearError('availability')"><option value="">Select availability</option><option v-for="option in availabilityOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select><span v-if="fieldError('availability')" class="field-error" data-field-error>{{ fieldError('availability') }}</span></label>
                                <div class="grid grid-cols-2 gap-3"><label class="form-label">Minimum salary<input v-model="draft.expected_salary_min" name="expected_salary_min" type="number" min="0" placeholder="Any" class="form-field"><span v-if="fieldError('expected_salary_min')" class="field-error" data-field-error>{{ fieldError('expected_salary_min') }}</span></label><label class="form-label">Maximum salary<input v-model="draft.expected_salary_max" name="expected_salary_max" type="number" min="0" placeholder="Any" class="form-field"><span v-if="fieldError('expected_salary_max')" class="field-error" data-field-error>{{ fieldError('expected_salary_max') }}</span></label></div>
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 p-3"><input v-model="draft.job_alerts_enabled" name="job_alerts_enabled" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-blue-700"><span><span class="block text-sm font-semibold text-slate-900">New job notifications</span><span class="mt-1 block text-xs leading-5 text-slate-600">Notify me when employers publish jobs.</span></span></label>
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 p-3"><input v-model="draft.profile_searchable" name="profile_searchable" type="checkbox" class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-blue-700"><span><span class="block text-sm font-semibold text-slate-900">Employer candidate search</span><span class="mt-1 block text-xs leading-5 text-slate-600">Allow employers to discover your profile.</span></span></label>
                            </div>
                        </section>
                        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm" aria-labelledby="documents-title">
                            <h2 id="documents-title" class="font-bold text-slate-950">Documents and security</h2>
                            <div class="mt-4 space-y-4">
                                <div><label for="profile-cv" class="form-label">Resume / CV</label><a v-if="profile.cv_path" href="/profile/resume" class="mb-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-800 hover:underline"><i class="ti ti-download" aria-hidden="true" />Download current resume</a><input id="profile-cv" ref="cvInput" type="file" accept=".pdf,.doc,.docx" class="form-field text-xs" @change="selectFile('cv', $event)"><span v-if="fieldError('cv')" class="field-error" data-field-error>{{ fieldError('cv') }}</span></div>
                                <label class="form-label">Change password <span class="font-normal text-slate-500">(optional)</span><input v-model="draft.password" name="password" type="password" autocomplete="new-password" minlength="8" class="form-field" :aria-invalid="Boolean(fieldError('password'))" @input="clearError('password')"><span v-if="fieldError('password')" class="field-error" data-field-error>{{ fieldError('password') }}</span></label>
                                <label class="form-label">Confirm new password<input v-model="draft.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" class="form-field" :aria-invalid="Boolean(fieldError('password_confirmation'))" @input="clearError('password_confirmation')"><span v-if="fieldError('password_confirmation')" class="field-error" data-field-error>{{ fieldError('password_confirmation') }}</span></label>
                            </div>
                        </section>
                    </aside>
                </div>

                <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur sm:px-8">
                    <div class="mx-auto flex max-w-6xl items-center justify-between gap-3">
                        <p class="hidden text-sm text-slate-600 sm:block" aria-live="polite">{{ hasChanges ? 'You have unsaved profile changes.' : 'Your profile is up to date.' }}</p>
                        <button type="submit" :disabled="saving || !hasChanges" class="ml-auto inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] disabled:cursor-not-allowed disabled:opacity-55" :aria-busy="saving">
                            <i v-if="saving" class="ti ti-loader-2 animate-spin" aria-hidden="true" /><i v-else class="ti ti-device-floppy" aria-hidden="true" />{{ saving ? 'Saving changes…' : 'Save Changes' }}
                        </button>
                    </div>
                </div>
            </form>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-8" aria-labelledby="applications-title">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="applications-title" class="text-lg font-bold text-slate-950">My applications</h2><p class="mt-1 text-sm text-slate-600">Review the current status of applications you submitted.</p></div><a href="/jobs" class="text-sm font-semibold text-[var(--brand-primary)] hover:underline">Browse jobs</a></div>
                <div v-if="profile.applications?.length" class="mt-5 divide-y divide-slate-100">
                    <article v-for="application in profile.applications" :key="application.id" class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                        <div class="min-w-0"><h3 class="truncate font-semibold text-slate-900">{{ application.job?.title || 'Job listing unavailable' }}</h3><p class="mt-1 text-sm text-slate-600">{{ application.job?.employer?.company_name || 'Employer' }}<span v-if="application.job?.location"> · {{ application.job.location }}</span></p><p class="mt-1 text-xs text-slate-500">Applied {{ application.submitted_at ? new Date(application.submitted_at).toLocaleDateString() : '—' }}</p></div>
                        <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold capitalize" :class="application.status === 'rejected' ? 'bg-red-50 text-red-800' : application.status === 'hired' || application.status === 'offered' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-900'">{{ application.status?.replaceAll('_', ' ') }}</span>
                        <span v-if="profile.employer_reviews_by_id?.[application.job?.employer?.id]?.status === 'pending'" class="rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800">Review pending moderation</span>
                        <span v-else-if="profile.employer_reviews_by_id?.[application.job?.employer?.id]?.status === 'approved'" class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800">Employer reviewed</span>
                        <button v-else-if="canReviewEmployer(application)" type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-900 hover:bg-blue-50" @click="toggleEmployerReview(application)">Rate employer</button>
                        <form v-if="reviewOpenId === application.job?.employer?.id" class="w-full rounded-xl border border-slate-200 bg-slate-50 p-4" @submit.prevent="submitEmployerReview(application)">
                            <h4 class="font-semibold text-slate-900">Review {{ application.job?.employer?.company_name }}</h4><p class="mt-1 text-xs leading-5 text-slate-600">Reviews are shown publicly only after moderation. One review is allowed per employer.</p>
                            <p v-if="reviewError" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800" role="alert">{{ reviewError }}</p>
                            <div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="text-xs font-semibold text-slate-600">Rating<select v-model.number="reviewDrafts[application.job.employer.id].rating" required class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900"><option v-for="rating in [5,4,3,2,1]" :key="rating" :value="rating">{{ rating }} / 5</option></select></label><label class="text-xs font-semibold text-slate-600">Title (optional)<input v-model="reviewDrafts[application.job.employer.id].title" maxlength="160" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label><label class="text-xs font-semibold text-slate-600 sm:col-span-2">Your experience<textarea v-model="reviewDrafts[application.job.employer.id].review" required minlength="20" maxlength="3000" rows="3" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900" placeholder="Describe your recruitment experience (at least 20 characters)." /></label></div>
                            <button type="submit" :disabled="reviewSavingId === application.job.employer.id" class="mt-3 inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:opacity-50"><i v-if="reviewSavingId === application.job.employer.id" class="ti ti-loader-2 animate-spin" aria-hidden="true" />{{ reviewSavingId === application.job.employer.id ? 'Submitting…' : 'Submit review' }}</button>
                        </form>
                    </article>
                </div>
                <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">You haven’t applied to any jobs yet.</p>
            </section>
        </div>
    </section>
    <section v-else class="mx-auto max-w-3xl px-5 py-16 text-center"><h1 class="text-2xl font-bold text-slate-900">Your seeker profile is unavailable</h1><p class="mt-2 text-sm text-slate-600">Sign in with a job seeker account to view and update your profile.</p></section>
</template>

<style scoped>
@reference "../../css/app.css";
.form-label { @apply block text-sm font-semibold text-slate-700; }
.form-field { @apply mt-1.5 w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm font-normal text-slate-900 outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100; }
.form-field[aria-invalid="true"] { @apply border-red-400 focus:border-red-500 focus:ring-red-100; }
.field-error { @apply mt-1.5 block text-sm font-medium text-red-700; }
</style>
