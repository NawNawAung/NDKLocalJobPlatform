<template>
    <footer class="site-footer bg-[var(--brand-ink)] text-slate-300">
        <div class="mx-auto flex max-w-[1440px] flex-col gap-7 px-5 py-8 sm:px-8 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <a href="/" class="inline-flex items-center text-white" aria-label="NDK Myanmar home"><img :src="'/images/brand/NDKJobPlatformLogo.jpeg'" alt="NDK Myanmar" class="h-12 w-auto max-w-52 object-contain" /></a>
                <p class="mt-2 text-sm text-slate-400">Myanmar’s local job and career platform.</p>
            </div>
            <nav class="flex flex-wrap gap-x-6 gap-y-3 text-sm" aria-label="Footer navigation">
                <a href="/#search" class="transition hover:text-white">Find Jobs</a>
                <a href="/#dashboard" class="transition hover:text-white">For Employers</a>
                <a href="/#insights" class="transition hover:text-white">Salary Insights</a>
                <a href="/#skills-assessment" class="transition hover:text-white">Skills Assessment</a>
                <a href="/#messages" class="transition hover:text-white">Messages</a>
            </nav>
        </div>
        <div class="border-t border-white/10 px-5 py-4 text-center text-xs text-slate-400 sm:px-8">© 2026 NDK Myanmar. Connecting local talent with opportunity.</div>
    </footer>
</template>
