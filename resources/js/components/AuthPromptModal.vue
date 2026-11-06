<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from 'vue';
import { closeAuthenticationPrompt } from '../authPrompt.js';

const props = defineProps({
    open: { type: Boolean, default: false },
    context: { type: String, default: 'account' },
});

const initialAction = ref(null);
const dialog = ref(null);
const messages = {
    apply: ['Sign in to apply', 'Sign in or create a job seeker account to apply for this job.'],
    save: ['Sign in to save jobs', 'Create an account or sign in to save this job to your profile.'],
    report: ['Sign in to report content', 'Create an account or sign in to send this report to the moderation team.'],
    reportJob: ['Sign in to report content', 'Create an account or sign in to send this report to the moderation team.'],
    reportEmployer: ['Sign in to report content', 'Create an account or sign in to send this report to the moderation team.'],
    review: ['Sign in to review an employer', 'Sign in with the eligible job seeker account to submit your employer review.'],
    messages: ['Sign in to view messages', 'Sign in or create an account to access your conversations.'],
    profile: ['Sign in to access your profile', 'Create a job seeker account or sign in to view and manage your profile.'],
    applications: ['Employer account required', 'Create or sign in to an employer account to manage job applications.'],
    postJob: ['Employer account required', 'Create or sign in to an employer account to post and manage jobs.'],
    dashboard: ['Employer account required', 'Sign in with an employer account to access the employer dashboard.'],
    billing: ['Employer account required', 'Sign in with an employer account to access billing and promotion options.'],
    talent: ['Employer account required', 'Sign in with an employer account to find candidates.'],
    admin: ['Administrator sign in required', 'Sign in with your authorized administrator account to continue.'],
    account: ['Sign in to continue', 'Create an account or sign in to continue with this action.'],
};
const message = computed(() => messages[props.context] ?? messages.account);
const protectedContexts = ['applications', 'postJob', 'dashboard', 'billing', 'talent', 'messages', 'profile', 'review', 'admin'];
const adminContext = computed(() => props.context === 'admin');
const returnUrl = computed(() => {
    if (typeof window === 'undefined') return '/';
    const prefix = `${window.location.pathname}${window.location.search}`;
    const hash = window.location.hash;
    if (!['apply', 'save', 'reportJob', 'reportEmployer'].includes(props.context) || !hash.startsWith('#details?')) return `${prefix}${hash}`;
    const [route, query = ''] = hash.slice(1).split('?');
    const params = new URLSearchParams(query);
    params.set('auth_action', props.context);
    return `${prefix}#${route}?${params.toString()}`;
});
const encodedReturnUrl = computed(() => encodeURIComponent(returnUrl.value));
const employerContexts = ['applications', 'postJob', 'dashboard', 'billing', 'talent'];
const jobSeekerContexts = ['apply', 'save', 'review', 'profile'];
const registerHref = computed(() => {
    const role = employerContexts.includes(props.context)
        ? '&role=employer'
        : jobSeekerContexts.includes(props.context) ? '&role=job_seeker' : '';
    return `/register?redirect=${encodedReturnUrl.value}${role}`;
});
const loginHref = computed(() => `${adminContext.value ? '/admin/login' : '/login'}?redirect=${encodedReturnUrl.value}`);

watch(() => props.open, async (open) => {
    if (open) {
        initialAction.value = document.activeElement;
        await nextTick();
        dialog.value?.querySelector('a[href^="/register"]')?.focus();
    } else {
        await nextTick();
        initialAction.value?.focus?.();
    }
});

function onKeydown(event) {
    if (!props.open) return;
    if (event.key === 'Escape') {
        event.preventDefault();
        continueBrowsing();
        return;
    }
    if (event.key !== 'Tab') return;
    const focusable = dialog.value?.querySelectorAll('a[href], button:not(:disabled)');
    if (!focusable?.length) return;
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
}

function continueBrowsing() {
    if (protectedContexts.includes(props.context)) {
        window.location.assign('/');
        return;
    }
    closeAuthenticationPrompt();
}

watch(() => props.open, (open) => {
    if (open) window.addEventListener('keydown', onKeydown);
    else window.removeEventListener('keydown', onKeydown);
}, { immediate: true });
onUnmounted(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[120] flex items-end justify-center bg-slate-950/55 p-0 backdrop-blur-[2px] sm:items-center sm:p-5" @click.self="continueBrowsing">
            <section ref="dialog" class="w-full max-w-md rounded-t-2xl bg-white p-6 shadow-2xl sm:rounded-2xl sm:p-7" role="dialog" aria-modal="true" aria-labelledby="auth-prompt-title" aria-describedby="auth-prompt-description">
                <div class="mx-auto grid h-12 w-12 place-items-center rounded-2xl bg-blue-50 text-2xl text-blue-800"><i class="ti ti-user-lock" aria-hidden="true" /></div>
                <h2 id="auth-prompt-title" class="mt-4 text-center text-xl font-bold text-slate-950">{{ $t(message[0]) }}</h2>
                <p id="auth-prompt-description" class="mt-2 text-center text-sm leading-6 text-slate-600">{{ $t(message[1]) }}</p>
                <div class="mt-6 grid gap-3">
                    <a v-if="adminContext" :href="loginHref" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] focus:outline-none focus:ring-4 focus:ring-blue-200"><i class="ti ti-shield-lock" aria-hidden="true" />{{ $t('Administrator sign in') }}</a>
                    <template v-else>
                        <a :href="registerHref" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] focus:outline-none focus:ring-4 focus:ring-blue-200"><i class="ti ti-user-plus" aria-hidden="true" />{{ $t('Create account') }}</a>
                        <a :href="loginHref" class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-blue-100">{{ $t('Sign in') }}</a>
                    </template>
                    <button type="button" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-blue-100" @click="continueBrowsing">{{ $t('Continue browsing') }}</button>
                </div>
            </section>
        </div>
    </Teleport>
</template>
