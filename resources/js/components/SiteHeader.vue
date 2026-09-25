<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import NotificationBell from './NotificationBell.vue';

const props = defineProps({
    isAuthenticated: { type: Boolean, default: false },
    csrfToken: { type: String, default: '' },
    userRole: { type: String, default: '' },
});

const menuOpen = ref(false);
const currentHash = ref(window.location.hash);
const currentPath = ref(window.location.pathname);
const pageType = window.__AUTH_BOOTSTRAP__?.page;
const updateCurrentLocation = () => {
    currentHash.value = window.location.hash;
    currentPath.value = window.location.pathname;
};
onMounted(() => {
    window.addEventListener('hashchange', updateCurrentLocation);
    window.addEventListener('popstate', updateCurrentLocation);
});
onUnmounted(() => {
    window.removeEventListener('hashchange', updateCurrentLocation);
    window.removeEventListener('popstate', updateCurrentLocation);
});

const publicLinks = [
    { label: 'Home', href: '/' },
    { label: 'Jobs', href: '/#search' },
    { label: 'Salary Insights', href: '/#insights' },
];
const links = [
    ...publicLinks,
    ...(props.isAuthenticated && props.userRole === 'job_seeker' ? [
        { label: 'Skills', href: '/#skills-assessment' },
        { label: 'My Profile', href: '/profile' },
    ] : []),
    ...(props.isAuthenticated && props.userRole === 'employer' ? [
        { label: 'Candidates', href: '/#pipeline' },
        { label: 'Dashboard', href: '/#dashboard' },
    ] : []),
    ...(props.isAuthenticated ? [{ label: 'Messages', href: '/#messages' }] : []),
];

function isActive(link) {
    if (link.href === '/profile') return currentPath.value === '/profile' || currentHash.value === '#profile';
    if (link.href === '/') {
        return currentPath.value === '/' && !currentHash.value && pageType !== 'search';
    }
    if (link.href === '/#search' && pageType === 'search' && !currentHash.value) return true;
    if (link.href === '/#search' && currentHash.value.startsWith('#details')) return true;
    if (link.href === '/#dashboard' && currentHash.value === '#post-job') return true;
    return currentHash.value === link.href.slice(1);
}
</script>

<template>
    <header class="site-header sticky top-0 z-50 border-b bg-white/95 backdrop-blur">
        <nav class="mx-auto flex min-h-16 max-w-[1440px] items-center justify-between gap-6 px-5 sm:px-8" aria-label="Main navigation">
            <a href="/" class="flex h-16 shrink-0 items-center" aria-label="NDK Job Platform home">
                <img :src="'/images/brand/NDKJobPlatformLogo-auth.jpeg'" alt="NDK Job Platform" class="h-16 w-16 object-contain" />
            </a>

            <div class="hidden items-center gap-5 xl:flex">
                <a v-for="link in links" :key="link.href" :href="link.href" :aria-current="isActive(link) ? 'page' : undefined" :class="isActive(link) ? 'bg-blue-50 text-blue-800 ring-1 ring-blue-200 shadow-sm' : 'text-slate-600 hover:bg-slate-50 hover:text-[var(--brand-primary)]'" class="site-nav-link rounded-lg px-3 py-2 text-sm font-semibold transition">{{ link.label }}</a>
            </div>

            <div class="hidden shrink-0 items-center gap-3 md:flex">
                <template v-if="isAuthenticated">
                    <NotificationBell />
                    <a v-if="userRole === 'employer'" href="/#post-job" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">Post a Job</a>
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

            <NotificationBell v-if="isAuthenticated" class="md:hidden" />

            <button type="button" class="grid h-10 w-10 place-items-center rounded-lg text-xl text-slate-700 hover:bg-slate-100 xl:hidden" :aria-expanded="menuOpen" aria-label="Toggle navigation" @click="menuOpen = !menuOpen">
                <i :class="menuOpen ? 'ti ti-x' : 'ti ti-menu-2'" aria-hidden="true" />
            </button>
        </nav>

        <div v-if="menuOpen" class="border-t border-slate-200 bg-white px-5 py-3 xl:hidden">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <a v-for="link in links" :key="link.href" :href="link.href" :aria-current="isActive(link) ? 'page' : undefined" :class="isActive(link) ? 'bg-blue-50 font-semibold text-blue-800 ring-1 ring-blue-200' : 'font-medium text-slate-700 hover:bg-slate-50'" class="rounded-lg px-3 py-2.5 text-sm" @click="menuOpen = false">{{ link.label }}</a>
                <template v-if="isAuthenticated">
                    <a v-if="userRole === 'employer'" href="/#post-job" class="mt-1 rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-center text-sm font-semibold text-white" @click="menuOpen = false">Post a Job</a>
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
