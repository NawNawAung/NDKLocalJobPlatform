<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({ bootstrap: { type: Object, default: () => window.__AUTH_BOOTSTRAP__ ?? {} } });
const old = props.bootstrap.old ?? {};
const role = ref(old.role ?? 'job_seeker');
const name = ref(old.name ?? '');
const email = ref(old.email ?? '');
const password = ref('');
const passwordConfirmation = ref('');
const companyName = ref(old.company_name ?? '');
const companyDescription = ref(old.company_description ?? '');
const companyLocation = ref(old.location ?? '');
const regionId = ref(String(old.region_id ?? ''));
const townshipId = ref(String(old.township_id ?? ''));
const regions = computed(() => props.bootstrap.regions ?? []);
const townships = computed(() => regions.value.find((region) => String(region.id) === regionId.value)?.townships ?? []);

watch(regionId, () => { townshipId.value = ''; });
function errorFor(field) { return props.bootstrap.errors?.[field]?.[0] ?? ''; }
function regionLabel(region) {
    const suffix = region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region';
    return `${region.name} ${suffix}`;
}
</script>

<template>
    <section class="px-5 py-10 sm:px-8 sm:py-14">
        <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="mb-8 text-center">
                <a href="/" class="mb-4 inline-flex items-center justify-center" aria-label="NDK Job Platform home">
                    <img :src="'/images/brand/NDKJobPlatformLogo-auth.jpeg'" alt="NDK Job Platform logo" class="block h-20 w-20 object-contain sm:h-24 sm:w-24" />
                </a>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Create your account</h1>
                <p class="mt-2 text-sm text-slate-600">Sign up to find opportunities or hire talent across Myanmar.</p>
            </div>

            <form action="/register" method="post" class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                <input type="hidden" name="_token" :value="bootstrap.csrfToken">
                <fieldset class="sm:col-span-2">
                    <legend class="mb-2 block text-sm font-semibold text-slate-700">Account type</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition" :class="role === 'job_seeker' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300'">
                            <input v-model="role" type="radio" name="role" value="job_seeker" required class="mt-1 accent-blue-700">
                            <span><span class="block text-sm font-semibold text-slate-900">I’m looking for a job</span><span class="mt-1 block text-xs leading-5 text-slate-600">Create a job seeker account.</span></span>
                        </label>
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition" :class="role === 'employer' ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 hover:border-slate-300'">
                            <input v-model="role" type="radio" name="role" value="employer" required class="mt-1 accent-blue-700">
                            <span><span class="block text-sm font-semibold text-slate-900">I’m hiring</span><span class="mt-1 block text-xs leading-5 text-slate-600">Create an employer account.</span></span>
                        </label>
                    </div>
                    <p v-if="errorFor('role')" class="field-error">{{ errorFor('role') }}</p>
                </fieldset>

                <div class="sm:col-span-2 border-b border-slate-200 pb-2"><h2 class="text-lg font-semibold text-slate-900">Account details</h2></div>
                <div>
                    <label for="register-name" class="form-label">Full name</label>
                    <input id="register-name" v-model="name" name="name" type="text" autocomplete="name" required maxlength="255" class="form-field" placeholder="Your full name">
                    <p v-if="errorFor('name')" class="field-error">{{ errorFor('name') }}</p>
                </div>
                <div>
                    <label for="register-email" class="form-label">Email address</label>
                    <input id="register-email" v-model="email" name="email" type="email" autocomplete="email" required maxlength="255" class="form-field" placeholder="you@example.com">
                    <p v-if="errorFor('email')" class="field-error">{{ errorFor('email') }}</p>
                </div>

                <template v-if="role === 'job_seeker'">
                    <div class="sm:col-span-2 border-b border-slate-200 pb-2 pt-2"><h2 class="text-lg font-semibold text-slate-900">Location</h2><p class="mt-1 text-sm text-slate-600">Choose where you are based so we can show relevant local opportunities. You can add career details later in your profile.</p></div>
                    <div class="sm:col-span-2">
                        <label for="register-region" class="form-label">Region or state</label>
                        <select id="register-region" v-model="regionId" name="region_id" required class="form-field">
                            <option value="">Choose a region or state</option>
                            <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ regionLabel(region) }}</option>
                        </select>
                        <p v-if="errorFor('region_id')" class="field-error">{{ errorFor('region_id') }}</p>
                    </div>
                </template>

                <div v-if="role === 'employer'" class="sm:col-span-2 grid gap-5 sm:grid-cols-2">
                    <div class="sm:col-span-2 border-b border-slate-200 pb-2 pt-2"><h2 class="text-lg font-semibold text-slate-900">Company details</h2></div>
                    <div>
                        <label for="company-name" class="form-label">Company name</label>
                        <input id="company-name" v-model="companyName" name="company_name" type="text" autocomplete="organization" maxlength="255" required class="form-field" placeholder="Your organization">
                        <p v-if="errorFor('company_name')" class="field-error">{{ errorFor('company_name') }}</p>
                    </div>
                    <div>
                        <label for="company-location" class="form-label">Company address <span class="font-normal text-slate-500">(optional)</span></label>
                        <input id="company-location" v-model="companyLocation" name="location" type="text" maxlength="255" class="form-field" placeholder="Office address or area">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="company-description" class="form-label">About the company <span class="font-normal text-slate-500">(optional)</span></label>
                        <textarea id="company-description" v-model="companyDescription" name="company_description" rows="3" maxlength="5000" class="form-field" placeholder="A short introduction to your organization"></textarea>
                    </div>
                    <div>
                        <label for="company-region" class="form-label">Company region or state</label>
                        <select id="company-region" v-model="regionId" name="region_id" required class="form-field"><option value="">Choose a region or state</option><option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ regionLabel(region) }}</option></select>
                        <p v-if="errorFor('region_id')" class="field-error">{{ errorFor('region_id') }}</p>
                    </div>
                    <div>
                        <label for="company-township" class="form-label">Township <span class="font-normal text-slate-500">(optional)</span></label>
                        <select id="company-township" v-model="townshipId" name="township_id" :disabled="!regionId || townships.length === 0" class="form-field disabled:bg-slate-50"><option value="">Choose a township</option><option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option></select>
                        <p v-if="errorFor('township_id')" class="field-error">{{ errorFor('township_id') }}</p>
                    </div>
                </div>

                <div class="sm:col-span-2 border-b border-slate-200 pb-2 pt-2"><h2 class="text-lg font-semibold text-slate-900">Password</h2></div>
                <div>
                    <label for="register-password" class="form-label">Password</label>
                    <input id="register-password" v-model="password" name="password" type="password" autocomplete="new-password" required minlength="8" class="form-field" placeholder="At least 8 characters">
                    <p v-if="errorFor('password')" class="field-error">{{ errorFor('password') }}</p>
                </div>
                <div>
                    <label for="register-password-confirmation" class="form-label">Confirm password</label>
                    <input id="register-password-confirmation" v-model="passwordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="form-field" placeholder="Enter the password again">
                </div>
                <div class="sm:col-span-2">
                    <button type="submit" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] focus:outline-none focus:ring-4 focus:ring-blue-200">Create account</button>
                    <p class="mt-5 text-center text-sm text-slate-600">Already have an account? <a href="/login" class="font-semibold text-[var(--brand-primary)] hover:underline">Sign in</a></p>
                </div>
            </form>
        </div>
    </section>
</template>

<style scoped>
@reference "../../css/app.css";
.form-label { @apply mb-1.5 block text-sm font-semibold text-slate-700; }
.form-field { @apply w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100; }
.field-error { @apply mt-1.5 text-sm text-red-700; }
</style>
