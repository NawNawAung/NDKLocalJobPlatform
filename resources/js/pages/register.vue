<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({ bootstrap: { type: Object, default: () => window.__AUTH_BOOTSTRAP__ ?? {} } });
const old = props.bootstrap.old ?? {};
const role = ref(old.role ?? 'job_seeker');
const regionId = ref(String(old.region_id ?? ''));
const townshipId = ref(String(old.township_id ?? ''));
const regions = computed(() => props.bootstrap.regions ?? []);
const townships = computed(() => regions.value.find((region) => String(region.id) === regionId.value)?.townships ?? []);

watch(regionId, () => {
    townshipId.value = '';
});

function errorFor(field) {
    return props.bootstrap.errors?.[field]?.[0] ?? '';
}

function regionLabel(region) {
    const suffix = region.type === 'state' ? 'State' : region.type === 'union_territory' ? 'Union Territory' : 'Region';
    return `${region.name} ${suffix}`;
}
</script>

<template>
    <section class="px-5 py-10 sm:px-8 sm:py-14">
        <div class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="mb-8 text-center">
                <span class="text-sm font-bold tracking-wide text-[var(--brand-primary)]">JOIN NDK</span>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Create your account</h1>
                <p class="mt-2 text-sm text-slate-600">Get started with opportunities and talent across Myanmar.</p>
            </div>

            <form action="/register" method="post" class="grid gap-x-5 gap-y-5 sm:grid-cols-2">
                <input type="hidden" name="_token" :value="bootstrap.csrfToken">

                <div class="sm:col-span-2">
                    <label for="register-role" class="mb-1.5 block text-sm font-semibold text-slate-700">I want to join as</label>
                    <select id="register-role" v-model="role" name="role" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                        <option value="job_seeker">Job seeker</option>
                        <option value="employer">Employer</option>
                    </select>
                    <p v-if="errorFor('role')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('role') }}</p>
                </div>

                <div>
                    <label for="register-name" class="mb-1.5 block text-sm font-semibold text-slate-700">{{ role === 'employer' ? 'Your name' : 'Full name' }}</label>
                    <input id="register-name" name="name" type="text" autocomplete="name" required maxlength="255" :value="old.name ?? ''" :aria-invalid="Boolean(errorFor('name'))" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="Enter your name">
                    <p v-if="errorFor('name')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('name') }}</p>
                </div>

                <div>
                    <label for="register-email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email address</label>
                    <input id="register-email" name="email" type="email" autocomplete="email" required maxlength="255" :value="old.email ?? ''" :aria-invalid="Boolean(errorFor('email'))" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="you@example.com">
                    <p v-if="errorFor('email')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('email') }}</p>
                </div>

                <div v-if="role === 'employer'" class="sm:col-span-2">
                    <label for="company-name" class="mb-1.5 block text-sm font-semibold text-slate-700">Company name</label>
                    <input id="company-name" name="company_name" type="text" autocomplete="organization" maxlength="255" :required="role === 'employer'" :value="old.company_name ?? ''" :aria-invalid="Boolean(errorFor('company_name'))" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="Your organization">
                    <p v-if="errorFor('company_name')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('company_name') }}</p>
                </div>

                <div>
                    <label for="register-region" class="mb-1.5 block text-sm font-semibold text-slate-700">Region or state</label>
                    <select id="register-region" v-model="regionId" name="region_id" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100">
                        <option value="">Choose a region or state</option>
                        <option v-for="region in regions" :key="region.id" :value="String(region.id)">{{ regionLabel(region) }}</option>
                    </select>
                    <p v-if="errorFor('region_id')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('region_id') }}</p>
                </div>

                <div>
                    <label for="register-township" class="mb-1.5 block text-sm font-semibold text-slate-700">Township <span class="font-normal text-slate-500">(optional)</span></label>
                    <select id="register-township" v-model="townshipId" name="township_id" :disabled="!regionId || townships.length === 0" class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100 disabled:bg-slate-50 disabled:text-slate-400">
                        <option value="">{{ regionId ? (townships.length ? 'Choose a township' : 'No townships listed yet') : 'Choose a region first' }}</option>
                        <option v-for="township in townships" :key="township.id" :value="String(township.id)">{{ township.name }}</option>
                    </select>
                    <p v-if="errorFor('township_id')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('township_id') }}</p>
                </div>

                <div>
                    <label for="register-password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                    <input id="register-password" name="password" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="At least 8 characters">
                    <p v-if="errorFor('password')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('password') }}</p>
                </div>

                <div>
                    <label for="register-password-confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Confirm password</label>
                    <input id="register-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm outline-none focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="Enter the password again">
                </div>

                <div class="sm:col-span-2">
                    <button type="submit" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] focus:outline-none focus:ring-4 focus:ring-blue-200">Create account</button>
                    <p class="mt-5 text-center text-sm text-slate-600">Already have an account? <a href="/login" class="font-semibold text-[var(--brand-primary)] hover:underline">Sign in</a></p>
                </div>
            </form>
        </div>
    </section>
</template>
