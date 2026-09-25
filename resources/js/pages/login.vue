<script setup>
const props = defineProps({ bootstrap: { type: Object, default: () => window.__AUTH_BOOTSTRAP__ ?? {} } });

function errorFor(field) {
    return props.bootstrap.errors?.[field]?.[0] ?? '';
}
</script>

<template>
    <section class="px-5 py-12 sm:px-8 sm:py-16">
        <div class="mx-auto grid max-w-5xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:grid-cols-[0.9fr_1.1fr]">
            <div class="hidden flex-col justify-between bg-[var(--brand-ink)] p-10 text-white lg:flex">
                <div>
                    <span class="inline-flex rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold tracking-wide text-blue-100">NDK JOB PLATFORM</span>
                    <h1 class="mt-8 text-3xl font-bold leading-tight">Find work that moves you forward.</h1>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-blue-100/80">Sign in to explore opportunities, manage applications, and connect with employers across Myanmar.</p>
                </div>
                <p class="text-sm text-blue-100/70">Local opportunities. Meaningful careers.</p>
            </div>

            <div class="p-6 sm:p-10 lg:p-12">
                <a href="/" class="mb-5 flex justify-center" aria-label="NDK Job Platform home">
                    <img :src="'/images/brand/NDKJobPlatformLogo-auth.jpeg'" alt="NDK Job Platform logo" class="block h-20 w-20 object-contain sm:h-24 sm:w-24" />
                </a>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Sign in to your account</h2>
                <p class="mt-2 text-sm text-slate-600">Enter the email and password associated with your account.</p>

                <form action="/login" method="post" class="mt-8 space-y-5">
                    <input type="hidden" name="_token" :value="bootstrap.csrfToken">

                    <div>
                        <label for="login-email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email address</label>
                        <input id="login-email" name="email" type="email" autocomplete="email" required autofocus :value="bootstrap.old?.email ?? ''" :aria-invalid="Boolean(errorFor('email'))" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="you@example.com">
                        <p v-if="errorFor('email')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('email') }}</p>
                    </div>

                    <div>
                        <label for="login-password" class="mb-1.5 block text-sm font-semibold text-slate-700">Password</label>
                        <input id="login-password" name="password" type="password" autocomplete="current-password" required :aria-invalid="Boolean(errorFor('password'))" class="w-full rounded-lg border border-slate-300 px-3.5 py-3 text-sm text-slate-900 outline-none transition focus:border-[var(--brand-primary)] focus:ring-4 focus:ring-blue-100" placeholder="Enter your password">
                        <p v-if="errorFor('password')" class="mt-1.5 text-sm text-red-700" role="alert">{{ errorFor('password') }}</p>
                    </div>

                    <button type="submit" class="w-full rounded-lg bg-[var(--brand-primary)] px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] focus:outline-none focus:ring-4 focus:ring-blue-200">Sign in</button>
                </form>

                <p class="mt-7 text-center text-sm text-slate-600">New to NDK? <a href="/register" class="font-semibold text-[var(--brand-primary)] hover:underline">Create an account</a></p>
            </div>
        </div>
    </section>
</template>
