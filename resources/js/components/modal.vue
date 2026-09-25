<script setup>
import { computed, reactive, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    candidateName: { type: String, default: '' },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});
const emit = defineEmits(['close', 'submit']);
const form = reactive({ date: '', time: '', interview_type: 'online', meeting_url: '', notes: '' });
const dateTimeMin = computed(() => {
    const now = new Date(Date.now() + (6 * 60 + 30) * 60000);
    return now.toISOString().slice(0, 16);
});

watch(() => props.open, (open) => {
    if (open) Object.assign(form, { date: '', time: '', interview_type: 'online', meeting_url: '', notes: '' });
});

function submit() {
    emit('submit', {
        interview_at: `${form.date}T${form.time}:00+06:30`,
        interview_type: form.interview_type,
        meeting_url: form.meeting_url || null,
        notes: form.notes || null,
    });
}
</script>

<template>
    <Teleport to="body">
        <div v-if="open" class="fixed inset-0 z-[70] grid place-items-center overflow-y-auto bg-slate-950/45 p-4" @click.self="emit('close')" @keydown.esc="emit('close')">
            <section class="my-auto w-full max-w-[540px] rounded-2xl bg-white p-6 shadow-2xl sm:p-8" role="dialog" aria-modal="true" aria-labelledby="interview-modal-title">
                <header class="flex items-start justify-between gap-4">
                    <div><h2 id="interview-modal-title" class="text-lg font-extrabold text-slate-900">Schedule interview</h2><p class="mt-1 text-sm text-slate-600">Set up a meeting with {{ candidateName }}.</p></div>
                    <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-500 transition hover:bg-slate-100 hover:text-slate-900" aria-label="Close scheduler" @click="emit('close')"><i class="ti ti-x text-lg" aria-hidden="true"/></button>
                </header>

                <form class="mt-6" @submit.prevent="submit">
                    <fieldset>
                        <legend class="text-xs font-bold uppercase tracking-wide text-slate-700">Interview type</legend>
                        <div class="mt-2 grid grid-cols-3 gap-1 rounded-lg bg-blue-50 p-1">
                            <button v-for="type in [{ id: 'online', label: 'Online video', icon: 'ti-video' }, { id: 'in_person', label: 'In person', icon: 'ti-building' }, { id: 'phone', label: 'Phone call', icon: 'ti-phone' }]" :key="type.id" type="button" class="flex flex-col items-center gap-1 rounded-md px-2 py-2.5 text-xs font-semibold transition sm:flex-row sm:justify-center" :class="form.interview_type === type.id ? 'bg-white text-blue-900 shadow-sm ring-1 ring-blue-100' : 'text-slate-600 hover:bg-white/70'" :aria-pressed="form.interview_type === type.id" @click="form.interview_type = type.id"><i :class="`ti ${type.icon}`" aria-hidden="true"/><span>{{ type.label }}</span></button>
                        </div>
                    </fieldset>

                    <div class="mt-5 grid gap-4 sm:grid-cols-2">
                        <label class="block"><span class="form-label">Date</span><span class="relative block"><i class="ti ti-calendar-event pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"/><input v-model="form.date" required type="date" :min="dateTimeMin.slice(0, 10)" class="form-field pl-10"/></span></label>
                        <label class="block"><span class="form-label">Time <span class="font-normal text-slate-500">(Myanmar time)</span></span><span class="relative block"><i class="ti ti-clock-hour-4 pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"/><input v-model="form.time" required type="time" :min="form.date === dateTimeMin.slice(0, 10) ? dateTimeMin.slice(11, 16) : undefined" class="form-field pl-10"/></span></label>
                    </div>

                    <label v-if="form.interview_type === 'online'" class="mt-5 block"><span class="form-label">Meeting link <span class="font-normal text-slate-500">(optional)</span></span><span class="relative block"><i class="ti ti-link pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true"/><input v-model="form.meeting_url" type="url" maxlength="2000" placeholder="https://meet.example.com/…" class="form-field pl-10"/></span></label>
                    <label class="mt-5 block"><span class="form-label">Notes for the candidate <span class="font-normal text-slate-500">(optional)</span></span><textarea v-model="form.notes" rows="3" maxlength="3000" placeholder="Add any preparation details or instructions." class="form-field resize-y"/></label>

                    <p v-if="errorMessage" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3.5 py-3 text-sm text-red-800" role="alert">{{ errorMessage }}</p>
                    <p class="mt-4 text-xs leading-5 text-slate-500">The candidate will receive an in-platform notification when you schedule the interview.</p>
                    <footer class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-5"><button type="button" :disabled="saving" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 disabled:opacity-50" @click="emit('close')">Cancel</button><button type="submit" :disabled="saving" class="inline-flex items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] disabled:cursor-wait disabled:opacity-60"><i :class="saving ? 'ti ti-loader-2 animate-spin' : 'ti ti-calendar-check'" aria-hidden="true"/>{{ saving ? 'Scheduling…' : 'Schedule interview' }}</button></footer>
                </form>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
@reference "../../css/app.css";
.form-label { @apply mb-1.5 block text-xs font-bold uppercase tracking-wide text-slate-700; }
.form-field { @apply w-full rounded-lg border border-slate-300 bg-white px-3 py-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-4 focus:ring-blue-100; }
</style>
