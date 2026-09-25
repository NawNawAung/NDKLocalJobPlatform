<script setup>
import { computed, nextTick, reactive, ref, watch } from 'vue';

const bootstrap = window.__AUTH_BOOTSTRAP__ ?? {};
const source = bootstrap.profile ?? {};
const old = bootstrap.old ?? {};
const hasErrors = Object.keys(bootstrap.errors ?? {}).length > 0;
const profile = reactive({
    ...source,
    user: { name: '', email: '', ...(source.user ?? {}) },
    region: source.region ?? null,
    township: source.township ?? null,
    experiences: (source.experiences ?? []).map((item) => ({ ...item, started_on: item.started_on?.slice(0, 10) ?? '', ended_on: item.ended_on?.slice(0, 10) ?? '' })),
    educations: [...(source.educations ?? [])],
});
const draft = reactive({
    ...source,
    ...old,
    user: { name: old.name ?? profile.user.name, email: old.email ?? profile.user.email },
    experiences: (old.experiences ?? profile.experiences).map((item) => ({ ...item })),
    educations: (old.educations ?? profile.educations).map((item) => ({ ...item })),
});
const editing = ref(hasErrors);
const regionId = ref(String(old.region_id ?? profile.region_id ?? ''));
const townshipId = ref(String(old.township_id ?? profile.township_id ?? ''));
const skillsText = ref(old.skills_text ?? (profile.skills ?? []).join(', '));
const languagesText = ref(old.languages_text ?? (profile.languages ?? []).join(', '));
const regions = computed(() => bootstrap.regions ?? []);
const townships = computed(() => regions.value.find((item) => String(item.id) === regionId.value)?.townships ?? []);
const errors = bootstrap.errors ?? {};
const notice = bootstrap.status ?? '';

watch(regionId, () => { townshipId.value = ''; });

const initials = computed(() => profile.user.name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase());
const completenessItems = computed(() => [
    { label: 'Name and email', done: Boolean(profile.user.name && profile.user.email), points: 8 },
    { label: 'Phone number', done: Boolean(profile.phone), points: 4 },
    { label: 'Region or state', done: Boolean(profile.region_id), points: 8 },
    { label: 'Professional headline', done: Boolean(profile.professional_title), points: 8 },
    { label: 'Professional summary', done: Boolean(profile.bio?.trim()), points: 8 },
    { label: 'Skills', done: Boolean(profile.skills?.length), points: 10 },
    { label: 'Languages', done: Boolean(profile.languages?.length), points: 4 },
    { label: 'Experience level', done: profile.years_experience !== null && profile.years_experience !== undefined, points: 4 },
    { label: 'Desired role', done: Boolean(profile.desired_job_title), points: 3 },
    { label: 'Employment type', done: Boolean(profile.employment_type), points: 2 },
    { label: 'Work mode', done: Boolean(profile.work_mode), points: 2 },
    { label: 'Availability', done: Boolean(profile.availability), points: 3 },
    { label: 'Resume / CV', done: Boolean(profile.cv_path), points: 10 },
    { label: 'Work experience', done: Boolean(profile.experiences?.length), points: 15 },
    { label: 'Education', done: Boolean(profile.educations?.length), points: 11 },
]);
const completeness = computed(() => completenessItems.value.reduce((sum, item) => sum + (item.done ? item.points : 0), 0));
const incompleteItems = computed(() => completenessItems.value.filter((item) => !item.done));

function fieldError(field) { return errors[field]?.[0] ?? ''; }
function nestedError(section, index, field) { return errors[`${section}.${index}.${field}`]?.[0] ?? ''; }
function labelFor(options, value) { return options.find((item) => item.value === value)?.label ?? ''; }
function formatDate(value) {
    if (!value) return '';
    return new Intl.DateTimeFormat(undefined, { month: 'short', year: 'numeric' }).format(new Date(`${value.slice(0, 10)}T00:00:00`));
}
function addExperience() {
    draft.experiences.push({ job_title: '', employer_name: '', location: '', started_on: '', ended_on: '', is_current: false, description: '' });
}
function addEducation() {
    draft.educations.push({ institution: '', qualification: '', field_of_study: '', started_year: '', graduated_year: '', description: '' });
}
function openEditor() {
    Object.assign(draft, {
        ...source,
        user: { name: profile.user.name, email: profile.user.email },
        experiences: profile.experiences.map((item) => ({ ...item })),
        educations: profile.educations.map((item) => ({ ...item })),
    });
    regionId.value = String(profile.region_id ?? '');
    townshipId.value = String(profile.township_id ?? '');
    skillsText.value = (profile.skills ?? []).join(', ');
    languagesText.value = (profile.languages ?? []).join(', ');
    editing.value = true;
    nextTick(() => document.getElementById('profile-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
}

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
</script>

<template>
    <section v-if="profile.user.name" class="min-h-[60vh] bg-slate-50 px-5 py-8 sm:px-8 sm:py-12">
        <div class="mx-auto max-w-6xl">
            <div v-if="notice" class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</div>
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-5">
                        <div class="grid h-20 w-20 shrink-0 place-items-center overflow-hidden rounded-full bg-blue-100 text-xl font-bold text-blue-800 ring-4 ring-blue-50 sm:h-24 sm:w-24">
                            <img v-if="profile.profile_photo_url" :src="profile.profile_photo_url" :alt="`${profile.user.name} profile photo`" class="h-full w-full object-cover">
                            <span v-else aria-label="Profile photo not uploaded">{{ initials }}</span>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-blue-700">Job seeker profile</p>
                            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{{ profile.user.name }}</h1>
                            <p class="mt-1 text-base font-medium text-blue-800">{{ profile.professional_title || 'Add a professional headline' }}</p>
                            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-slate-600">
                                <span><i class="ti ti-mail mr-1" aria-hidden="true" />{{ profile.user.email }}</span>
                                <span v-if="profile.phone"><i class="ti ti-phone mr-1" aria-hidden="true" />{{ profile.phone }}</span>
                                <span v-if="profile.region"><i class="ti ti-map-pin mr-1" aria-hidden="true" />{{ profile.township ? `${profile.township.name}, ` : '' }}{{ profile.region.name }}</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" class="inline-flex items-center justify-center gap-2 self-start rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] sm:self-center" @click="openEditor">
                        <i class="ti ti-edit" aria-hidden="true" /> Edit profile
                    </button>
                </div>
                <div class="mt-6 grid gap-3 border-t border-slate-100 pt-5 sm:grid-cols-3">
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Applications</p><p class="mt-1 text-xl font-bold text-slate-900">{{ profile.applications_count ?? 0 }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Saved jobs</p><p class="mt-1 text-xl font-bold text-slate-900">{{ profile.saved_jobs_count ?? 0 }}</p></div>
                    <div class="rounded-xl bg-slate-50 p-4"><p class="text-xs font-medium uppercase tracking-wide text-slate-500">Experience</p><p class="mt-1 text-xl font-bold text-slate-900">{{ profile.years_experience ?? '—' }}<span v-if="profile.years_experience !== null && profile.years_experience !== undefined" class="ml-1 text-sm font-medium">years</span></p></div>
                </div>
            </div>

            <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
                <div class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="about-title">
                        <h2 id="about-title" class="text-lg font-bold text-slate-950">Professional summary</h2>
                        <p v-if="profile.bio" class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ profile.bio }}</p>
                        <p v-else class="mt-3 text-sm text-slate-500">No professional summary added yet.</p>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="experience-title">
                        <div class="flex items-center justify-between gap-4">
                            <div><h2 id="experience-title" class="text-lg font-bold text-slate-950">Work experience</h2><p class="mt-1 text-sm text-slate-500">Your employment history and achievements.</p></div>
                            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ profile.experiences.length }}</span>
                        </div>
                        <div v-if="profile.experiences.length" class="mt-6 divide-y divide-slate-100">
                            <article v-for="item in profile.experiences" :key="item.id ?? `${item.job_title}-${item.employer_name}`" class="py-5 first:pt-0 last:pb-0">
                                <h3 class="font-semibold text-slate-900">{{ item.job_title }}</h3>
                                <p class="mt-1 text-sm font-medium text-blue-800">{{ item.employer_name }}<span v-if="item.location" class="font-normal text-slate-500"> · {{ item.location }}</span></p>
                                <p class="mt-1 text-xs text-slate-500">{{ formatDate(item.started_on) }}<span v-if="item.started_on"> – </span>{{ item.is_current ? 'Present' : formatDate(item.ended_on) }}</p>
                                <p v-if="item.description" class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ item.description }}</p>
                            </article>
                        </div>
                        <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-5 text-sm text-slate-600">Add your work history to show employers where you have built experience.</p>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="education-title">
                        <div class="flex items-center justify-between gap-4"><div><h2 id="education-title" class="text-lg font-bold text-slate-950">Education</h2><p class="mt-1 text-sm text-slate-500">Your education and qualifications.</p></div><span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">{{ profile.educations.length }}</span></div>
                        <div v-if="profile.educations.length" class="mt-6 divide-y divide-slate-100">
                            <article v-for="item in profile.educations" :key="item.id ?? `${item.institution}-${item.qualification}`" class="py-5 first:pt-0 last:pb-0">
                                <h3 class="font-semibold text-slate-900">{{ item.qualification || item.institution }}</h3>
                                <p class="mt-1 text-sm text-blue-800">{{ item.institution }}<span v-if="item.field_of_study"> · {{ item.field_of_study }}</span></p>
                                <p v-if="item.started_year || item.graduated_year" class="mt-1 text-xs text-slate-500">{{ item.started_year || '' }}<span v-if="item.started_year && item.graduated_year"> – </span>{{ item.graduated_year || '' }}</p>
                                <p v-if="item.description" class="mt-2 text-sm leading-6 text-slate-700">{{ item.description }}</p>
                            </article>
                        </div>
                        <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-5 text-sm text-slate-600">Add your education or professional qualifications.</p>
                    </section>

                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="skills-title">
                        <h2 id="skills-title" class="text-lg font-bold text-slate-950">Skills and languages</h2>
                        <div class="mt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Skills</h3>
                            <div v-if="profile.skills?.length" class="mt-2 flex flex-wrap gap-2"><span v-for="skill in profile.skills" :key="skill" class="rounded-full border border-blue-100 bg-blue-50 px-3 py-1.5 text-sm font-medium text-blue-900">{{ skill }}</span></div>
                            <p v-else class="mt-2 text-sm text-slate-500">No skills added yet.</p>
                        </div>
                        <div class="mt-5">
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500">Languages</h3>
                            <div v-if="profile.languages?.length" class="mt-2 flex flex-wrap gap-2"><span v-for="language in profile.languages" :key="language" class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700">{{ language }}</span></div>
                            <p v-else class="mt-2 text-sm text-slate-500">No languages added yet.</p>
                        </div>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="completeness-title">
                        <div class="flex items-center justify-between gap-3"><h2 id="completeness-title" class="font-bold text-slate-950">Profile completeness</h2><span class="text-lg font-bold text-blue-800">{{ completeness }}%</span></div>
                        <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="completeness" aria-valuemin="0" aria-valuemax="100" :aria-label="`Profile ${completeness}% complete`"><div class="h-full rounded-full bg-blue-700 transition-all" :style="{ width: `${completeness}%` }" /></div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">{{ completeness === 100 ? 'Your profile has all recommended sections.' : 'Complete more sections to give employers a clearer view of your experience.' }}</p>
                        <ul v-if="incompleteItems.length" class="mt-4 space-y-2 border-t border-slate-100 pt-4">
                            <li v-for="item in incompleteItems" :key="item.label" class="flex items-start gap-2 text-sm text-slate-600"><i class="ti ti-circle-dashed mt-0.5 text-blue-600" aria-hidden="true" /><span>{{ item.label }}</span></li>
                        </ul>
                    </section>
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="preferences-title">
                        <h2 id="preferences-title" class="font-bold text-slate-950">Job preferences</h2>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Desired role</dt><dd class="mt-1 font-medium text-slate-800">{{ profile.desired_job_title || 'Not specified' }}</dd></div>
                            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Employment type</dt><dd class="mt-1 font-medium text-slate-800">{{ labelFor(employmentOptions, profile.employment_type) || 'Not specified' }}</dd></div>
                            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Work mode</dt><dd class="mt-1 font-medium text-slate-800">{{ labelFor(workModeOptions, profile.work_mode) || 'Not specified' }}</dd></div>
                            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Availability</dt><dd class="mt-1 font-medium text-slate-800">{{ labelFor(availabilityOptions, profile.availability) || 'Not specified' }}</dd></div>
                            <div><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">New job notifications</dt><dd class="mt-1 font-medium text-slate-800">{{ profile.job_alerts_enabled ? 'On' : 'Off' }}</dd></div>
                            <div v-if="profile.expected_salary_min || profile.expected_salary_max"><dt class="text-xs font-medium uppercase tracking-wide text-slate-500">Expected monthly salary</dt><dd class="mt-1 font-medium text-slate-800">{{ profile.expected_salary_min ? Number(profile.expected_salary_min).toLocaleString() : 'Any' }} – {{ profile.expected_salary_max ? Number(profile.expected_salary_max).toLocaleString() : 'Any' }} MMK</dd></div>
                        </dl>
                    </section>
                    <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm" aria-labelledby="resume-title">
                        <h2 id="resume-title" class="font-bold text-slate-950">Resume / CV</h2>
                        <a v-if="profile.cv_path" href="/profile/resume" class="mt-3 inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50"><i class="ti ti-file-download" aria-hidden="true" />Download my resume</a>
                        <p v-else class="mt-2 text-sm text-slate-600">You haven’t uploaded a resume yet.</p>
                    </section>
                </aside>
            </div>

            <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="applications-title">
                <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="applications-title" class="text-lg font-bold text-slate-950">My applications</h2><p class="mt-1 text-sm text-slate-600">Review the current status of applications you submitted.</p></div><a href="/jobs" class="text-sm font-semibold text-[var(--brand-primary)] hover:underline">Browse jobs</a></div>
                <div v-if="profile.applications?.length" class="mt-5 divide-y divide-slate-100">
                    <article v-for="application in profile.applications" :key="application.id" class="flex flex-wrap items-center justify-between gap-3 py-4 first:pt-0 last:pb-0">
                        <div class="min-w-0"><h3 class="truncate font-semibold text-slate-900">{{ application.job?.title || 'Job listing unavailable' }}</h3><p class="mt-1 text-sm text-slate-600">{{ application.job?.employer?.company_name || 'Employer' }}<span v-if="application.job?.location"> · {{ application.job.location }}</span></p><p class="mt-1 text-xs text-slate-500">Applied {{ application.submitted_at ? new Date(application.submitted_at).toLocaleDateString() : '—' }}</p></div>
                        <span class="shrink-0 rounded-full px-3 py-1.5 text-xs font-semibold capitalize" :class="application.status === 'rejected' ? 'bg-red-50 text-red-800' : application.status === 'hired' || application.status === 'offered' ? 'bg-emerald-50 text-emerald-800' : 'bg-blue-50 text-blue-900'">{{ application.status?.replaceAll('_', ' ') }}</span>
                    </article>
                </div>
                <p v-else class="mt-5 rounded-lg bg-slate-50 px-4 py-6 text-center text-sm text-slate-600">You haven’t applied to any jobs yet.</p>
            </section>

            <section id="profile-editor" class="mt-8 scroll-mt-24 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8" aria-labelledby="edit-profile-title">
                <button type="button" class="flex w-full items-center justify-between text-left" :aria-expanded="editing" @click="editing = !editing">
                    <span><span id="edit-profile-title" class="block text-lg font-bold text-slate-950">Edit your information</span><span class="mt-1 block text-sm text-slate-600">Update your contact details, career history, and preferences.</span></span>
                    <i :class="editing ? 'ti ti-chevron-up' : 'ti ti-chevron-down'" class="text-xl text-slate-500" aria-hidden="true" />
                </button>
                <div v-if="editing" class="mt-6 grid gap-6 border-t border-slate-100 pt-6 lg:grid-cols-[190px_minmax(0,1fr)] lg:items-start">
                    <nav class="flex gap-2 overflow-x-auto pb-2 text-sm lg:sticky lg:top-24 lg:flex-col lg:overflow-visible lg:pb-0" aria-label="Profile edit sections">
                        <a href="#edit-contact" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Contact & location</a>
                        <a href="#edit-summary" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Professional summary</a>
                        <a href="#edit-skills-section" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Skills & languages</a>
                        <a href="#edit-preferences" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Job preferences</a>
                        <a href="#edit-experience" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Work experience</a>
                        <a href="#edit-education" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Education</a>
                        <a href="#edit-documents" class="shrink-0 rounded-lg px-3 py-2 font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-800">Photo & resume</a>
                    </nav>
                <form action="/profile" method="post" enctype="multipart/form-data" class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                    <input type="hidden" name="_token" :value="bootstrap.csrfToken"><input type="hidden" name="_method" value="PATCH">
                    <div id="edit-contact" class="sm:col-span-2 scroll-mt-24"><h3 class="font-semibold text-slate-900">Contact and location</h3></div>
                    <div><label for="edit-name" class="form-label">Full name</label><input id="edit-name" name="name" v-model="draft.user.name" required maxlength="255" class="form-field"><p v-if="fieldError('name')" class="field-error">{{ fieldError('name') }}</p></div>
                    <div><label for="edit-email" class="form-label">Email address</label><input id="edit-email" name="email" v-model="draft.user.email" type="email" required maxlength="255" class="form-field"><p v-if="fieldError('email')" class="field-error">{{ fieldError('email') }}</p></div>
                    <div><label for="edit-phone" class="form-label">Phone</label><input id="edit-phone" name="phone" v-model="draft.phone" type="tel" maxlength="40" class="form-field"><p v-if="fieldError('phone')" class="field-error">{{ fieldError('phone') }}</p></div>
                    <div><label for="edit-region" class="form-label">Region or state</label><select id="edit-region" v-model="regionId" name="region_id" class="form-field"><option value="">Choose a region or state</option><option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ region.name }} {{ region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region' }}</option></select><p v-if="fieldError('region_id')" class="field-error">{{ fieldError('region_id') }}</p></div>
                    <div><label for="edit-township" class="form-label">Township</label><select id="edit-township" v-model="townshipId" name="township_id" :disabled="!regionId || townships.length === 0" class="form-field disabled:bg-slate-50"><option value="">Select township</option><option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option></select></div>
                    <div id="edit-summary" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Professional summary</h3></div>
                    <div><label for="edit-headline" class="form-label">Professional headline</label><input id="edit-headline" name="professional_title" v-model="draft.professional_title" maxlength="160" class="form-field"><p v-if="fieldError('professional_title')" class="field-error">{{ fieldError('professional_title') }}</p></div>
                    <div><label for="edit-years" class="form-label">Years of experience</label><input id="edit-years" name="years_experience" v-model="draft.years_experience" type="number" min="0" max="60" class="form-field"><p v-if="fieldError('years_experience')" class="field-error">{{ fieldError('years_experience') }}</p></div>
                    <div class="sm:col-span-2"><label for="edit-bio" class="form-label">Professional summary</label><textarea id="edit-bio" name="bio" v-model="draft.bio" rows="4" maxlength="5000" class="form-field"></textarea><p v-if="fieldError('bio')" class="field-error">{{ fieldError('bio') }}</p></div>
                    <div id="edit-skills-section" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Skills and languages</h3></div>
                    <div><label for="edit-skills" class="form-label">Skills, separated by commas</label><textarea id="edit-skills" name="skills_text" v-model="skillsText" rows="3" maxlength="2000" class="form-field"></textarea><p v-if="fieldError('skills_text')" class="field-error">{{ fieldError('skills_text') }}</p></div>
                    <div><label for="edit-languages" class="form-label">Languages, separated by commas</label><textarea id="edit-languages" name="languages_text" v-model="languagesText" rows="3" maxlength="1000" class="form-field"></textarea><p v-if="fieldError('languages_text')" class="field-error">{{ fieldError('languages_text') }}</p></div>
                    <div><label for="edit-desired-role" class="form-label">Desired job title</label><input id="edit-desired-role" name="desired_job_title" v-model="draft.desired_job_title" maxlength="160" class="form-field"></div>
                    <div><label for="edit-availability" class="form-label">Availability</label><select id="edit-availability" name="availability" v-model="draft.availability" class="form-field"><option value="">Select availability</option><option v-for="option in availabilityOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                    <div><label for="edit-employment" class="form-label">Employment type</label><select id="edit-employment" name="employment_type" v-model="draft.employment_type" class="form-field"><option value="">Select employment type</option><option v-for="option in employmentOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                    <div><label for="edit-work-mode" class="form-label">Work mode</label><select id="edit-work-mode" name="work_mode" v-model="draft.work_mode" class="form-field"><option value="">Select work mode</option><option v-for="option in workModeOptions" :key="option.value" :value="option.value">{{ option.label }}</option></select></div>
                    <div><label for="edit-salary-min" class="form-label">Minimum expected salary (MMK / month)</label><input id="edit-salary-min" name="expected_salary_min" v-model="draft.expected_salary_min" type="number" min="0" class="form-field"><p v-if="fieldError('expected_salary_min')" class="field-error">{{ fieldError('expected_salary_min') }}</p></div>
                    <div><label for="edit-salary-max" class="form-label">Maximum expected salary (MMK / month)</label><input id="edit-salary-max" name="expected_salary_max" v-model="draft.expected_salary_max" type="number" min="0" class="form-field"><p v-if="fieldError('expected_salary_max')" class="field-error">{{ fieldError('expected_salary_max') }}</p></div>

                    <div id="edit-preferences" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Job preferences</h3></div>
                    <label class="sm:col-span-2 flex cursor-pointer items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 p-4"><input v-model="draft.job_alerts_enabled" name="job_alerts_enabled" type="checkbox" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 accent-blue-700"><span><span class="block text-sm font-semibold text-slate-900">Notify me about new jobs</span><span class="mt-1 block text-xs leading-5 text-slate-600">Receive an in-platform notification when an employer publishes a new listing. You can turn this off at any time.</span></span></label>
                    <div id="edit-experience" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Work experience</h3></div>
                    <fieldset v-for="(item, index) in draft.experiences" :key="item.id ?? `new-experience-${index}`" class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:col-span-2 sm:grid-cols-2">
                        <legend class="px-2 text-sm font-semibold text-slate-700">Position {{ index + 1 }}</legend>
                        <div><label :for="`edit-exp-title-${index}`" class="form-label">Job title</label><input :id="`edit-exp-title-${index}`" v-model="item.job_title" :name="`experiences[${index}][job_title]`" maxlength="160" class="form-field"><p v-if="nestedError('experiences', index, 'job_title')" class="field-error">{{ nestedError('experiences', index, 'job_title') }}</p></div>
                        <div><label :for="`edit-exp-employer-${index}`" class="form-label">Employer</label><input :id="`edit-exp-employer-${index}`" v-model="item.employer_name" :name="`experiences[${index}][employer_name]`" maxlength="160" class="form-field"><p v-if="nestedError('experiences', index, 'employer_name')" class="field-error">{{ nestedError('experiences', index, 'employer_name') }}</p></div>
                        <div><label :for="`edit-exp-location-${index}`" class="form-label">Location</label><input :id="`edit-exp-location-${index}`" v-model="item.location" :name="`experiences[${index}][location]`" maxlength="160" class="form-field"></div>
                        <div class="grid grid-cols-2 gap-3"><label class="form-label">Start date<input v-model="item.started_on" :name="`experiences[${index}][started_on]`" type="date" class="form-field mt-1.5"></label><label class="form-label">End date<input v-model="item.ended_on" :name="`experiences[${index}][ended_on]`" type="date" :disabled="item.is_current" class="form-field mt-1.5 disabled:bg-slate-50"></label></div>
                        <label class="flex items-center gap-2 text-sm text-slate-700"><input v-model="item.is_current" :name="`experiences[${index}][is_current]`" type="checkbox" value="1" class="rounded border-slate-300 accent-blue-700">I currently work here</label>
                        <div class="sm:col-span-2"><label :for="`edit-exp-description-${index}`" class="form-label">Responsibilities and achievements</label><textarea :id="`edit-exp-description-${index}`" v-model="item.description" :name="`experiences[${index}][description]`" rows="2" maxlength="5000" class="form-field"></textarea></div>
                        <button v-if="draft.experiences.length > 1" type="button" class="justify-self-start text-sm font-medium text-red-700 hover:underline" @click="draft.experiences.splice(index, 1)">Remove position</button>
                    </fieldset>
                    <button type="button" class="sm:col-span-2 justify-self-start rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50" @click="addExperience">+ Add position</button>

                    <div id="edit-education" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Education</h3></div>
                    <fieldset v-for="(item, index) in draft.educations" :key="item.id ?? `new-education-${index}`" class="grid gap-4 rounded-xl border border-slate-200 p-4 sm:col-span-2 sm:grid-cols-2">
                        <legend class="px-2 text-sm font-semibold text-slate-700">Education {{ index + 1 }}</legend>
                        <div><label :for="`edit-edu-school-${index}`" class="form-label">Institution</label><input :id="`edit-edu-school-${index}`" v-model="item.institution" :name="`educations[${index}][institution]`" maxlength="180" class="form-field"><p v-if="nestedError('educations', index, 'institution')" class="field-error">{{ nestedError('educations', index, 'institution') }}</p></div>
                        <div><label :for="`edit-edu-qualification-${index}`" class="form-label">Qualification</label><input :id="`edit-edu-qualification-${index}`" v-model="item.qualification" :name="`educations[${index}][qualification]`" maxlength="160" class="form-field"></div>
                        <div><label :for="`edit-edu-field-${index}`" class="form-label">Field of study</label><input :id="`edit-edu-field-${index}`" v-model="item.field_of_study" :name="`educations[${index}][field_of_study]`" maxlength="160" class="form-field"></div>
                        <div class="grid grid-cols-2 gap-3"><label class="form-label">Start year<input v-model="item.started_year" :name="`educations[${index}][started_year]`" type="number" min="1900" :max="new Date().getFullYear()" class="form-field mt-1.5"></label><label class="form-label">Completion year<input v-model="item.graduated_year" :name="`educations[${index}][graduated_year]`" type="number" min="1900" :max="new Date().getFullYear() + 10" class="form-field mt-1.5"></label></div>
                        <div class="sm:col-span-2"><label :for="`edit-edu-description-${index}`" class="form-label">Additional details</label><textarea :id="`edit-edu-description-${index}`" v-model="item.description" :name="`educations[${index}][description]`" rows="2" maxlength="3000" class="form-field"></textarea></div>
                        <button v-if="draft.educations.length > 1" type="button" class="sm:col-span-2 justify-self-start text-sm font-medium text-red-700 hover:underline" @click="draft.educations.splice(index, 1)">Remove education</button>
                    </fieldset>
                    <button type="button" class="sm:col-span-2 justify-self-start rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50" @click="addEducation">+ Add education</button>

                    <div id="edit-documents" class="sm:col-span-2 scroll-mt-24 border-t border-slate-100 pt-5"><h3 class="font-semibold text-slate-900">Profile photo and resume</h3></div>
                    <div><label for="edit-photo" class="form-label">Replace profile photo</label><input id="edit-photo" name="profile_photo" type="file" accept="image/jpeg,image/png,image/webp" class="form-field text-xs"></div>
                    <div><label for="edit-resume" class="form-label">Upload or replace resume / CV</label><input id="edit-resume" name="cv" type="file" accept=".pdf,.doc,.docx" class="form-field text-xs"><p v-if="fieldError('cv')" class="field-error">{{ fieldError('cv') }}</p></div>
                    <div><label for="edit-password" class="form-label">Change password <span class="font-normal text-slate-500">(leave blank to keep current)</span></label><input id="edit-password" name="password" type="password" autocomplete="new-password" minlength="8" class="form-field"></div>
                    <div><label for="edit-password-confirmation" class="form-label">Confirm new password</label><input id="edit-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" class="form-field"></div>
                    <div class="sm:col-span-2 flex flex-wrap items-center gap-3 border-t border-slate-100 pt-5">
                        <button type="submit" class="rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-[var(--brand-primary-hover)]">Save profile</button>
                        <button type="button" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="editing = false">Cancel</button>
                        <p v-if="fieldError('profile_photo')" class="field-error">{{ fieldError('profile_photo') }}</p>
                    </div>
                </form>
                </div>
            </section>
        </div>
    </section>
    <section v-else class="mx-auto max-w-3xl px-5 py-16 text-center">
        <h1 class="text-2xl font-bold text-slate-900">Your seeker profile is unavailable</h1>
        <p class="mt-2 text-sm text-slate-600">Sign in with a job seeker account to view and update your profile.</p>
    </section>
</template>

<style scoped>
@reference "../../css/app.css";
.form-label { @apply mb-1.5 block text-sm font-semibold text-slate-700; }
.form-field { @apply w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100; }
.field-error { @apply mt-1.5 text-sm text-red-700; }
</style>
