<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import NotificationBell from './NotificationBell.vue';

const props = defineProps({
    isAuthenticated: { type: Boolean, default: false },
    csrfToken: { type: String, default: '' },
    userRole: { type: String, default: '' },
    userName: { type: String, default: '' },
    userEmail: { type: String, default: '' },
    profilePhotoUrl: { type: String, default: '' },
});

const menuOpen = ref(false);
const profileMenuOpen = ref(false);
const currentUserName = ref(props.userName);
const currentUserEmail = ref(props.userEmail);
const currentProfilePhotoUrl = ref(props.profilePhotoUrl);
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
function handleProfileUpdated(event) {
    currentUserName.value = event.detail?.name ?? currentUserName.value;
    currentUserEmail.value = event.detail?.email ?? currentUserEmail.value;
    currentProfilePhotoUrl.value = event.detail?.photoUrl ?? currentProfilePhotoUrl.value;
}
onMounted(() => window.addEventListener('profile-updated', handleProfileUpdated));
onUnmounted(() => window.removeEventListener('profile-updated', handleProfileUpdated));
const closeProfileMenuOnEscape = (event) => {
    if (event.key === 'Escape') profileMenuOpen.value = false;
};
const closeProfileMenuOutside = (event) => {
    if (!(event.target instanceof Element) || !event.target.closest('[data-profile-menu]')) profileMenuOpen.value = false;
};
onMounted(() => {
    window.addEventListener('keydown', closeProfileMenuOnEscape);
    document.addEventListener('pointerdown', closeProfileMenuOutside);
});
onUnmounted(() => {
    window.removeEventListener('keydown', closeProfileMenuOnEscape);
    document.removeEventListener('pointerdown', closeProfileMenuOutside);
});

const userInitials = computed(() => currentUserName.value.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase());
const profileActions = computed(() => {
    if (props.userRole === 'job_seeker') return [
        { label: 'View profile', href: '/profile', icon: 'ti-user' },
        { label: 'Edit profile', href: '/profile#profile-form', icon: 'ti-edit' },
    ];
    if (props.userRole === 'employer') return [
        { label: 'Employer dashboard', href: '/#dashboard', icon: 'ti-layout-dashboard' },
        { label: 'Edit company profile', href: '/#dashboard?editProfile=1', icon: 'ti-building' },
    ];
    if (props.userRole === 'admin') return [
        { label: 'Admin dashboard', href: '/admin', icon: 'ti-shield' },
    ];
    return [];
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
        { label: 'Talent', href: '/#talent' },
        { label: 'Candidates', href: '/#pipeline' },
        { label: 'Dashboard', href: '/#dashboard' },
        { label: 'Billing', href: '/#billing' },
    ] : []),
    ...(props.isAuthenticated && props.userRole === 'admin' ? [
        { label: 'Admin Dashboard', href: '/admin' },
        { label: 'Billing review', href: '/admin#admin-billing' },
    ] : []),
    ...(props.isAuthenticated ? [{ label: 'Messages', href: '/#messages' }] : []),
];

function isActive(link) {
    if (link.href === '/admin') return currentPath.value === '/admin' && !currentHash.value;
    if (link.href.startsWith('/admin#')) return currentPath.value === '/admin' && currentHash.value === link.href.slice('/admin'.length);
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
                    <button type="button" data-profile-menu aria-label="Open account menu" :aria-expanded="profileMenuOpen" class="flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1 pr-3 text-left transition hover:border-blue-300 hover:bg-blue-50" @click.stop="profileMenuOpen = !profileMenuOpen">
                        <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-blue-100 text-sm font-bold text-blue-800">
                            <img v-if="currentProfilePhotoUrl" :src="currentProfilePhotoUrl" :alt="`${currentUserName} profile photo`" class="h-full w-full object-cover">
                            <span v-else>{{ userInitials || 'U' }}</span>
                        </span>
                        <span class="max-w-32 truncate text-sm font-semibold text-slate-700">{{ currentUserName || 'Account' }}</span>
                        <i class="ti ti-chevron-down text-sm text-slate-500" aria-hidden="true" />
                    </button>
                </template>
                <template v-else>
                    <a href="/login" class="rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100">Sign in</a>
                    <a href="/register" class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)]">Create account</a>
                </template>
            </div>

            <div class="flex items-center gap-2 md:hidden">
                <NotificationBell v-if="isAuthenticated" />
                <button v-if="isAuthenticated" type="button" data-profile-menu aria-label="Open account menu" :aria-expanded="profileMenuOpen" class="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-full border border-slate-200 bg-blue-50 text-sm font-bold text-blue-800" @click.stop="profileMenuOpen = !profileMenuOpen">
                    <img v-if="currentProfilePhotoUrl" :src="currentProfilePhotoUrl" :alt="`${currentUserName} profile photo`" class="h-full w-full object-cover">
                    <span v-else>{{ userInitials || 'U' }}</span>
                </button>
            </div>

            <button type="button" class="grid h-10 w-10 place-items-center rounded-lg text-xl text-slate-700 hover:bg-slate-100 xl:hidden" :aria-expanded="menuOpen" aria-label="Toggle navigation" @click="menuOpen = !menuOpen">
                <i :class="menuOpen ? 'ti ti-x' : 'ti ti-menu-2'" aria-hidden="true" />
            </button>
        </nav>

        <div v-if="profileMenuOpen" data-profile-menu class="absolute right-4 top-[calc(100%-0.25rem)] z-[60] w-72 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl sm:right-8">
            <div class="border-b border-slate-100 px-4 py-3">
                <p class="truncate text-sm font-semibold text-slate-900">{{ currentUserName }}</p>
                <p class="truncate text-xs text-slate-500">{{ currentUserEmail }}</p>
            </div>
            <nav class="p-2" aria-label="Account actions">
                <a v-for="action in profileActions" :key="action.href" :href="action.href" class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-blue-50 hover:text-blue-800" @click="profileMenuOpen = false">
                    <i :class="`ti ${action.icon}`" class="text-base" aria-hidden="true" />{{ action.label }}
                </a>
            </nav>
            <form action="/logout" method="post" class="border-t border-slate-100 p-2">
                <input type="hidden" name="_token" :value="csrfToken">
                <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-slate-700 transition hover:bg-slate-50"><i class="ti ti-logout text-base" aria-hidden="true" />Sign out</button>
            </form>
        </div>

        <div v-if="menuOpen" class="border-t border-slate-200 bg-white px-5 py-3 xl:hidden">
            <div class="mx-auto flex max-w-[1440px] flex-col gap-1">
                <a v-for="link in links" :key="link.href" :href="link.href" :aria-current="isActive(link) ? 'page' : undefined" :class="isActive(link) ? 'bg-blue-50 font-semibold text-blue-800 ring-1 ring-blue-200' : 'font-medium text-slate-700 hover:bg-slate-50'" class="rounded-lg px-3 py-2.5 text-sm" @click="menuOpen = false">{{ link.label }}</a>
                <template v-if="isAuthenticated">
                    <a v-if="userRole === 'employer'" href="/#post-job" class="mt-1 rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-center text-sm font-semibold text-white" @click="menuOpen = false">Post a Job</a>
                </template>
                <template v-else>
                    <a href="/login" class="mt-1 rounded-lg px-3 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="menuOpen = false">Sign in</a>
                    <a href="/register" class="rounded-lg bg-[var(--brand-primary)] px-3 py-2.5 text-center text-sm font-semibold text-white" @click="menuOpen = false">Create account</a>
                </template>
            </div>
        </div>
    </header>
</template>
