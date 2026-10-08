<script setup>
import { onMounted, ref } from 'vue';

const payments = ref([]);
const loading = ref(true);
const savingId = ref(null);
const error = ref('');
const notice = ref('');
const reviewNotes = ref({});
const refundAmounts = ref({});
const refundReasons = ref({});
const search = ref('');
const statusFilter = ref('');
const pagination = ref(null);

function money(amount, currency) { return `${new Intl.NumberFormat('en-US').format(amount ?? 0)} ${currency ?? 'MMK'}`; }
async function load(page = 1) {
    loading.value = true; error.value = '';
    try {
        const { data } = await window.axios.get('/api/admin/billing/payments', { params: { q: search.value || undefined, status: statusFilter.value || undefined, page } });
        pagination.value = data.payments;
        payments.value = data.payments?.data ?? [];
    }
    catch (exception) { error.value = exception.response?.data?.message ?? 'Could not load payment submissions.'; }
    finally { loading.value = false; }
}
function filterPayments() { load(1); }
async function review(payment, decision) {
    savingId.value = payment.id; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.patch(`/api/admin/billing/payments/${payment.id}/review`, { decision, notes: reviewNotes.value[payment.id] || undefined });
        notice.value = data.message; await load();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not update this payment.'; }
    finally { savingId.value = null; }
}
async function refund(payment) {
    const amount = Number(refundAmounts.value[payment.id]);
    const reason = refundReasons.value[payment.id]?.trim();
    if (!amount || !reason) { error.value = 'Enter a refund amount and reason.'; return; }
    if (!window.confirm('Confirm the refund has been transferred back outside the platform before recording it?')) return;
    savingId.value = payment.id; error.value = ''; notice.value = '';
    try {
        const { data } = await window.axios.post(`/api/admin/billing/payments/${payment.id}/refunds`, { amount, reason });
        notice.value = data.message;
        refundAmounts.value[payment.id] = null;
        refundReasons.value[payment.id] = '';
        await load();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not record the refund.'; }
    finally { savingId.value = null; }
}
onMounted(() => load(1));
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8"><div class="mx-auto max-w-6xl">
        <div class="mb-7 flex flex-wrap items-end justify-between gap-3"><div><p class="text-sm font-semibold text-[var(--brand-primary)]">Platform administration</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Payment review</h1><p class="mt-2 text-sm text-slate-600">Verify transfer receipts before activating plans or promotions.</p></div><button type="button" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50" @click="load(1)">Refresh</button></div>
        <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p><p v-if="notice" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
        <form class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:grid-cols-[minmax(0,1fr)_200px_auto]" @submit.prevent="filterPayments"><label class="text-xs font-semibold text-slate-600">Search payments<input v-model="search" type="search" placeholder="Reference, order, employer, or customer" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label><label class="text-xs font-semibold text-slate-600">Status<select v-model="statusFilter" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900"><option value="">All statuses</option><option value="pending">Pending review</option><option value="processing">Processing</option><option value="paid">Paid</option><option value="failed">Failed</option><option value="partially_refunded">Partially refunded</option><option value="refunded">Refunded</option></select></label><button class="self-end rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white">Apply filters</button></form>
        <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Loading payment submissions…</div>
        <div v-else-if="payments.length" class="space-y-4"><article v-for="payment in payments" :key="payment.id" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-4"><div><div class="flex flex-wrap items-center gap-2"><h2 class="font-bold text-slate-950">{{ payment.order.number }} · {{ payment.company || payment.customer.name }}</h2><span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="payment.status === 'pending' ? 'bg-amber-50 text-amber-800' : payment.status === 'paid' ? 'bg-emerald-50 text-emerald-800' : payment.status === 'failed' ? 'bg-red-50 text-red-800' : 'bg-slate-100 text-slate-700'">{{ payment.status.replaceAll('_', ' ') }}</span></div><p class="mt-1 text-sm text-slate-600">{{ payment.customer.name }} · {{ payment.customer.email }}</p><p class="mt-1 text-sm font-semibold text-slate-800">{{ payment.order.items.join(', ') }} · {{ money(payment.amount, payment.currency) }}</p><p class="mt-1 text-xs text-slate-500">{{ payment.method }}<span v-if="payment.reference"> · Ref {{ payment.reference }}</span><span v-if="payment.submitted_at"> · Submitted {{ new Date(payment.submitted_at).toLocaleString() }}</span><span v-if="payment.reviewed_at"> · Reviewed {{ new Date(payment.reviewed_at).toLocaleString() }} by {{ payment.reviewer || 'admin' }}</span></p><p v-if="payment.failure_reason" class="mt-1 text-xs text-red-700">Review note: {{ payment.failure_reason }}</p><p v-if="payment.refund_total" class="mt-1 text-xs font-semibold text-amber-800">Refunded {{ money(payment.refund_total, payment.currency) }}</p></div><a v-if="payment.proof_url" :href="payment.proof_url" target="_blank" rel="noopener" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-blue-800 hover:bg-blue-50">Review receipt</a></div>
            <details v-if="payment.refunds?.length" class="mt-3 text-xs"><summary class="cursor-pointer font-semibold text-slate-700">Refund history ({{ payment.refunds.length }})</summary><ul class="mt-2 space-y-1 text-slate-600"><li v-for="(refundItem, index) in payment.refunds" :key="index">{{ money(refundItem.amount, payment.currency) }} · {{ refundItem.reason }}<span v-if="refundItem.reference_number"> · Ref {{ refundItem.reference_number }}</span><span v-if="refundItem.processed_at"> · {{ new Date(refundItem.processed_at).toLocaleString() }}</span></li></ul></details>
            <div v-if="payment.status === 'pending'" class="mt-4 border-t border-slate-100 pt-4"><label class="block text-xs font-semibold text-slate-600">Review note (required when rejecting)<textarea v-model="reviewNotes[payment.id]" rows="2" maxlength="2000" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100" placeholder="Add a reason if the transfer cannot be verified." /></label><div class="mt-3 flex flex-wrap gap-2"><button type="button" :disabled="savingId === payment.id" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50" @click="review(payment, 'approve')">Approve &amp; activate</button><button type="button" :disabled="savingId === payment.id || !reviewNotes[payment.id]?.trim()" class="rounded-lg border border-red-200 px-4 py-2 text-sm font-semibold text-red-800 hover:bg-red-50 disabled:opacity-40" @click="review(payment, 'reject')">Reject receipt</button></div></div>
            <div v-if="['paid', 'partially_refunded'].includes(payment.status) && payment.refund_remaining > 0" class="mt-4 grid gap-3 border-t border-slate-100 pt-4 sm:grid-cols-[180px_minmax(0,1fr)_auto]"><label class="text-xs font-semibold text-slate-600">Refund amount<input v-model.number="refundAmounts[payment.id]" type="number" min="1" :max="payment.refund_remaining" step="1" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"><span class="mt-1 block text-[11px] font-normal text-slate-500">Remaining: {{ money(payment.refund_remaining, payment.currency) }}</span></label><label class="text-xs font-semibold text-slate-600">Reason<input v-model="refundReasons[payment.id]" maxlength="2000" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal"></label><button type="button" :disabled="savingId === payment.id" class="self-end rounded-lg border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-50 disabled:opacity-50" @click="refund(payment)">Record refund</button><p class="text-xs leading-5 text-amber-800 sm:col-span-3">Only record a refund after funds have actually been returned outside the platform.</p></div>
        </article></div>
        <div v-else class="rounded-xl border border-slate-200 bg-white px-6 py-14 text-center"><i class="ti ti-receipt text-4xl text-slate-300" aria-hidden="true"/><h2 class="mt-3 font-semibold text-slate-900">No payment submissions</h2><p class="mt-1 text-sm text-slate-600">Submitted bank transfer receipts will appear here for review.</p></div>
        <nav v-if="pagination && pagination.last_page > 1" class="mt-5 flex items-center justify-between rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm"><span class="text-slate-600">Page {{ pagination.current_page }} of {{ pagination.last_page }} · {{ pagination.total }} payments</span><div class="flex gap-2"><button type="button" :disabled="!pagination.prev_page_url || loading" class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 disabled:opacity-40" @click="load(pagination.current_page - 1)">Previous</button><button type="button" :disabled="!pagination.next_page_url || loading" class="rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-700 disabled:opacity-40" @click="load(pagination.current_page + 1)">Next</button></div></nav>
    </div></section>
</template>
