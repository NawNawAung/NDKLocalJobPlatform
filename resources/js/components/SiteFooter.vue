<script setup>
defineProps({
    isAuthenticated: { type: Boolean, default: false },
    userRole: { type: String, default: '' },
});
</script>

<template>
    <footer class="site-footer bg-[var(--brand-ink)] text-slate-300">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-7 px-5 py-8 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="/" class="inline-flex items-center" aria-label="NDK Job Platform home"><img :src="'/images/brand/NDKJobPlatformLogo-auth.jpeg'" alt="NDK Job Platform" class="h-20 w-20 object-contain" /></a>
                <p class="mt-2 text-sm text-slate-400">Myanmar’s local job and career platform.</p>
            </div>
            <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm" aria-label="Footer navigation">
                <a href="/#search" class="transition hover:text-white">Find Jobs</a>
                <a v-if="isAuthenticated && userRole === 'employer'" href="/#dashboard" class="transition hover:text-white">For Employers</a>
                <a href="/#insights" class="transition hover:text-white">Salary Insights</a>
                <a v-if="isAuthenticated && userRole === 'job_seeker'" href="/#skills-assessment" class="transition hover:text-white">Skills Assessment</a>
                <a v-if="isAuthenticated" href="/#messages" class="transition hover:text-white">Messages</a>
            </nav>
            <div class="flex items-center gap-3" aria-label="Social media platforms">
                <span role="img" aria-label="Facebook" title="Facebook" class="grid h-10 w-10 place-items-center rounded-full border border-white/15 text-lg text-slate-300"><i class="ti ti-brand-facebook" aria-hidden="true" /></span>
                <span role="img" aria-label="Instagram" title="Instagram" class="grid h-10 w-10 place-items-center rounded-full border border-white/15 text-lg text-slate-300"><i class="ti ti-brand-instagram" aria-hidden="true" /></span>
                <span role="img" aria-label="LinkedIn" title="LinkedIn" class="grid h-10 w-10 place-items-center rounded-full border border-white/15 text-lg text-slate-300"><i class="ti ti-brand-linkedin" aria-hidden="true" /></span>
                <span role="img" aria-label="YouTube" title="YouTube" class="grid h-10 w-10 place-items-center rounded-full border border-white/15 text-lg text-slate-300"><i class="ti ti-brand-youtube" aria-hidden="true" /></span>
            </div>
        </div>
        <div class="border-t border-white/10 px-5 py-4 text-center text-xs text-slate-400 sm:px-8">© 2026 NDK Myanmar. Connecting local talent with opportunity.</div>
    </footer>
</template>
