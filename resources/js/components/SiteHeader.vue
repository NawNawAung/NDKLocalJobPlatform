<script setup>
import { ref } from 'vue';

defineProps({
    isAuthenticated: { type: Boolean, default: false },
    csrfToken: { type: String, default: '' },
});

const menuOpen = ref(false);
const links = [
    { label: 'Jobs', href: '/#search' },
    { label: 'Salary Insights', href: '/#insights' },
    { label: 'Skills', href: '/#skills-assessment' },
    { label: 'Candidates', href: '/#pipeline' },
    { label: 'Messages', href: '/#messages' },
    { label: 'Dashboard', href: '/#dashboard' },
    { label: 'My Profile', href: '/#profile' },
];
</script>

<template>
    <header class="site-header sticky top-0 z-50 border-b bg-white/95 backdrop-blur">
        <nav class="mx-auto flex min-h-16 max-w-[1440px] items-center justify-between gap-6 px-5 sm:px-8" aria-label="Main navigation">
            <a href="/" class="flex h-14 shrink-0 items-center" aria-label="NDK Myanmar home">
                <img :src="'/images/brand/NDKJobPlatformLogo.jpeg'" alt="NDK Myanmar" class="h-12 w-auto max-w-52 object-contain" />
            </a>

            <div class="hidden items-center gap-5 xl:flex">
                <a v-for="link in links" :key="link.href" :href="link.href" class="site-nav-link rounded-md px-2 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[var(--brand-primary)]">{{ link.label }}</a>
            </div>

            <div class="hidden shrink-0 items-center gap-3 md:flex">
                <template v-if="isAuthenticated">
                    <a href="/#post-job" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">Post a Job</a>
                    <form action="/logout" method="post">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button type="submit" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-100">Sign out</button>
                    </form>
                </template>
                <template v-else>
                    <a href="/login" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Sign in</a>
                    <a href="/register" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">Create account</a>
                </template>
            </div>

            <button type="button" class="grid h-10 w-10 place-items-center rounded-lg text-xl text-slate-700 hover:bg-slate-100 xl:hidden" :aria-expanded="menuOpen" aria-label="Toggle navigation" @click="menuOpen = !menuOpen">
                <i :class="menuOpen ? 'ti ti-x' : 'ti ti-menu-2'" aria-hidden="true" />
            </button>
        </nav>

        <div v-if="menuOpen" class="border-t border-slate-200 bg-white px-5 py-3 xl:hidden">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <a v-for="link in links" :key="link.href" :href="link.href" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="menuOpen = false">{{ link.label }}</a>
                <template v-if="isAuthenticated">
                    <a href="/#post-job" class="mt-1 rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-center text-sm font-semibold text-white" @click="menuOpen = false">Post a Job</a>
                    <form action="/logout" method="post" class="mt-1">
                        <input type="hidden" name="_token" :value="csrfToken">
                        <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50">Sign out</button>
                    </form>
                </template>
                <template v-else>
                    <a href="/login" class="mt-1 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="menuOpen = false">Sign in</a>
                    <a href="/register" class="rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-center text-sm font-semibold text-white" @click="menuOpen = false">Create account</a>
                </template>
            </div>
        </div>
    </header>
</template>
