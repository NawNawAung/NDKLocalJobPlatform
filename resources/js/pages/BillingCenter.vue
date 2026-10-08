<script setup>
import { computed, onMounted, ref } from 'vue';

const data = ref(null);
const loading = ref(true);
const submitting = ref(false);
const error = ref('');
const notice = ref('');
const selectedJob = ref('');
const coupon = ref('');
const activeOrder = ref(null);
const referenceNumber = ref('');
const proof = ref(null);
const selectedPlan = computed(() => data.value?.plans?.find((plan) => plan.slug === data.value?.plan?.slug));

function money(amount, currency = 'MMK') {
    return `${new Intl.NumberFormat('en-US').format(amount ?? 0)} ${currency}`;
}
function featureRows(plan) {
    const values = plan?.features ?? {};
    const activeJobs = values.max_active_jobs < 0 ? 'Unlimited active jobs' : `${values.max_active_jobs ?? 1} active job${values.max_active_jobs === 1 ? '' : 's'}`;
    const previews = values.candidate_search ? 'Full candidate search' : `${values.candidate_preview_limit ?? 0} candidate previews per month`;
    const cv = values.candidate_cv_downloads < 0 ? 'Unlimited CV downloads' : `${values.candidate_cv_downloads ?? 0} CV downloads per month`;
    return [activeJobs, previews, values.candidate_contact_view ? 'Candidate contact details' : 'Private contact details', cv, `${values.analytics_level ?? 'basic'} analytics`];
}
async function load() {
    loading.value = true;
    error.value = '';
    try {
        const { data: result } = await window.axios.get('/api/employer/billing');
        data.value = result;
        if (!selectedJob.value) selectedJob.value = String(result.jobs?.[0]?.id ?? '');
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'Could not load billing information.';
    } finally { loading.value = false; }
}
async function startPlan(plan) {
    if (plan.price_amount === 0 || plan.billing_interval === 'custom') return;
    submitting.value = true; error.value = ''; notice.value = '';
    try {
        const { data: result } = await window.axios.post('/api/employer/billing/orders', { plan_slug: plan.slug, promotion_code: coupon.value || undefined });
        activeOrder.value = result.order;
        notice.value = result.message;
        await load();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not create this order.'; }
    finally { submitting.value = false; }
}
async function buyPromotion(product) {
    if (!selectedJob.value) { error.value = 'Publish a job before ordering a promotion.'; return; }
    submitting.value = true; error.value = ''; notice.value = '';
    try {
        const { data: result } = await window.axios.post('/api/employer/billing/orders', { product_slug: product.slug, job_id: Number(selectedJob.value), promotion_code: coupon.value || undefined });
        activeOrder.value = result.order;
        notice.value = result.message;
        await load();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not create this order.'; }
    finally { submitting.value = false; }
}
function chooseOrder(order) {
    if (['pending_payment', 'payment_failed'].includes(order.status)) {
        activeOrder.value = { id: order.id, order_number: order.order_number, total_amount: order.total_amount, currency: order.currency };
        proof.value = null; referenceNumber.value = '';
    }
}
async function submitProof() {
    if (!activeOrder.value || !proof.value) return;
    submitting.value = true; error.value = ''; notice.value = '';
    const form = new FormData();
    form.append('payment_method', 'bank_transfer');
    if (referenceNumber.value.trim()) form.append('reference_number', referenceNumber.value.trim());
    form.append('proof', proof.value);
    try {
        const { data: result } = await window.axios.post(`/api/employer/billing/orders/${activeOrder.value.id}/payments`, form);
        notice.value = result.message;
        activeOrder.value = null; proof.value = null; referenceNumber.value = '';
        await load();
    } catch (exception) { error.value = exception.response?.data?.errors ? Object.values(exception.response.data.errors).flat().join(' ') : exception.response?.data?.message ?? 'Could not submit payment receipt.'; }
    finally { submitting.value = false; }
}
async function cancelOrder(order) {
    if (!window.confirm(`Cancel unpaid order ${order.order_number}?`)) return;
    submitting.value = true; error.value = ''; notice.value = '';
    try {
        const { data: result } = await window.axios.patch(`/api/employer/billing/orders/${order.id}/cancel`);
        notice.value = result.message;
        if (activeOrder.value?.id === order.id) activeOrder.value = null;
        await load();
    } catch (exception) { error.value = exception.response?.data?.message ?? 'Could not cancel this order.'; }
    finally { submitting.value = false; }
}
onMounted(load);
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <div class="mb-7"><p class="text-sm font-semibold text-[var(--brand-primary)]">Employer account</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Plans &amp; billing</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Choose an employer plan or purchase visibility for a published job. Job seekers continue to search and apply for free.</p></div>
            <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>
            <p v-if="notice" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-10 text-center text-sm text-slate-500">Loading plans and orders…</div>
            <template v-else-if="data">
                <section class="mb-6 grid gap-4 lg:grid-cols-[1.2fr_0.8fr]">
                    <article class="rounded-xl border border-blue-100 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wide text-blue-700">Current plan</p><h2 class="mt-1 text-xl font-bold text-slate-950">{{ data.plan?.name || 'Free' }}</h2><p class="mt-1 text-sm text-slate-600">{{ data.plan?.description }}</p></div><span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold capitalize text-blue-800">{{ data.plan?.subscription_ends_at ? `Paid through ${new Date(data.plan.subscription_ends_at).toLocaleDateString()}` : 'Active' }}</span></div><div class="mt-5 grid gap-4 sm:grid-cols-2"><div><div class="flex justify-between text-xs text-slate-600"><span>Active jobs</span><span>{{ data.usage.active_jobs }} / {{ data.usage.active_jobs_limit < 0 ? '∞' : data.usage.active_jobs_limit }}</span></div><div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-700" :style="{ width: `${data.usage.active_jobs_limit < 0 ? 0 : Math.min(100, data.usage.active_jobs / Math.max(1, data.usage.active_jobs_limit) * 100)}%` }" /></div></div><div><div class="flex justify-between text-xs text-slate-600"><span>Candidate CV downloads this month</span><span>{{ data.usage.candidate_cv_downloads }} / {{ data.usage.candidate_cv_downloads_limit < 0 ? '∞' : data.usage.candidate_cv_downloads_limit }}</span></div><div class="mt-1.5 h-2 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-indigo-600" :style="{ width: `${data.usage.candidate_cv_downloads_limit < 0 ? 0 : Math.min(100, data.usage.candidate_cv_downloads / Math.max(1, data.usage.candidate_cv_downloads_limit) * 100)}%` }" /></div></div></div></article>
                    <article class="rounded-xl border border-amber-200 bg-amber-50 p-5"><h2 class="font-bold text-amber-950">Manual bank transfer</h2><p v-if="data.transfer_instructions.configured" class="mt-2 text-sm leading-6 text-amber-950">{{ data.transfer_instructions.bank_name }}<br>{{ data.transfer_instructions.account_name }}<br>{{ data.transfer_instructions.account_number }}<br>{{ data.transfer_instructions.payment_note }}</p><p v-else class="mt-2 text-sm leading-6 text-amber-950">Transfer details are not configured yet. The platform operator must set BILLING_BANK_NAME, BILLING_ACCOUNT_NAME, and BILLING_ACCOUNT_NUMBER in the environment before accepting transfers.</p><p class="mt-3 text-xs leading-5 text-amber-900">Payment is not automated. Upload a receipt and an administrator must verify it before an order is activated.</p></article>
                </section>

                <section class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><div class="flex flex-wrap items-end justify-between gap-4"><div><h2 class="text-lg font-bold text-slate-950">Employer plans</h2><p class="mt-1 text-sm text-slate-600">Features are enforced by server-side entitlements.</p></div><label class="w-full max-w-xs text-xs font-semibold text-slate-600">Promotion code (optional)<input v-model="coupon" type="text" maxlength="40" placeholder="Enter a code" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"></label></div>
                    <div class="mt-5 grid gap-4 md:grid-cols-3"><article v-for="plan in data.plans" :key="plan.id" class="flex flex-col rounded-xl border p-5" :class="plan.slug === data.plan?.slug ? 'border-blue-300 bg-blue-50/50 ring-1 ring-blue-200' : 'border-slate-200'"><div><div class="flex items-center justify-between gap-2"><h3 class="font-bold text-slate-950">{{ plan.name }}</h3><span v-if="plan.slug === data.plan?.slug" class="rounded-full bg-blue-100 px-2.5 py-1 text-[11px] font-bold text-blue-900">Current</span></div><p class="mt-2 text-2xl font-extrabold text-[var(--brand-ink)]">{{ plan.billing_interval === 'custom' ? 'Custom' : money(plan.price_amount, plan.currency) }}<span v-if="plan.billing_interval !== 'custom' && plan.price_amount" class="text-xs font-medium text-slate-500"> / month</span></p><p class="mt-2 min-h-10 text-sm leading-5 text-slate-600">{{ plan.description }}</p><ul class="mt-4 space-y-2 text-sm text-slate-700"><li v-for="feature in featureRows(plan)" :key="feature" class="flex gap-2"><i class="ti ti-check mt-0.5 text-blue-700" aria-hidden="true"/><span>{{ feature }}</span></li></ul></div><div v-if="plan.billing_interval === 'custom'" class="mt-5 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 text-center text-sm text-slate-600">Contact the platform team for a custom quote.</div><button v-else-if="plan.price_amount > 0 && (plan.slug !== data.plan?.slug || data.plan?.subscription_ends_at)" type="button" :disabled="submitting" class="mt-5 rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)] disabled:opacity-50" @click="startPlan(plan)">{{ submitting ? 'Preparing…' : plan.slug === data.plan?.slug ? 'Extend plan' : 'Choose ' + plan.name }}</button><span v-else class="mt-5 rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-500">{{ plan.slug === data.plan?.slug ? 'Current plan' : 'Included' }}</span></article></div>
                </section>

                <section v-if="data.products.length" class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6"><h2 class="text-lg font-bold text-slate-950">Job promotions</h2><p class="mt-1 text-sm text-slate-600">One-time promotion purchases are separate from subscriptions.</p><div class="mt-4 grid gap-4 md:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]"><label class="text-xs font-semibold text-slate-600">Published job<select v-model="selectedJob" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal text-slate-900"><option value="">Choose a job</option><option v-for="job in data.jobs" :key="job.id" :value="String(job.id)">{{ job.title }}</option></select></label><article v-for="product in data.products" :key="product.slug" class="rounded-lg border border-slate-200 p-3"><p class="font-semibold text-slate-900">{{ product.name }}</p><p class="mt-1 text-xs leading-5 text-slate-600">{{ product.description }}</p><p class="mt-2 text-sm font-bold text-blue-900">{{ money(product.price_amount, product.currency) }}</p><button type="button" :disabled="submitting || !selectedJob" class="mt-2 w-full rounded-md bg-blue-800 px-3 py-2 text-xs font-semibold text-white disabled:opacity-40" @click="buyPromotion(product)">Order promotion</button></article></div></section>

                <section class="rounded-xl border border-slate-200 bg-white shadow-sm"><div class="border-b border-slate-100 px-5 py-4"><h2 class="font-bold text-slate-950">Orders, payments &amp; invoices</h2><p class="mt-1 text-sm text-slate-600">Receipts are reviewed manually before a plan or promotion is activated.</p></div><div v-if="data.orders.length" class="divide-y divide-slate-100"><article v-for="order in data.orders" :key="order.id" class="px-5 py-4"><div class="flex flex-wrap items-center justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ order.order_number }} · {{ order.items.map(item => item.name).join(', ') }}</p><p class="mt-1 text-xs text-slate-500">{{ money(order.total_amount, order.currency) }} · <span class="capitalize">{{ order.status.replaceAll('_', ' ') }}</span><span v-if="order.latest_payment_status"> · latest payment {{ order.latest_payment_status }}</span></p></div><div class="flex flex-wrap gap-2"><button v-if="['pending_payment', 'payment_failed'].includes(order.status)" type="button" class="rounded-lg border border-blue-200 px-3 py-2 text-xs font-semibold text-blue-900 hover:bg-blue-50" @click="chooseOrder(order)">Submit transfer receipt</button><button v-if="['pending_payment', 'payment_failed'].includes(order.status)" type="button" :disabled="submitting" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="cancelOrder(order)">Cancel unpaid order</button><a v-if="order.invoice_id" :href="`/api/billing/invoices/${order.invoice_id}`" target="_blank" rel="noopener" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">View invoice</a></div></div><details v-if="order.payments?.length" class="mt-3 text-xs"><summary class="cursor-pointer font-semibold text-blue-800">Payment history ({{ order.payments.length }})</summary><div class="mt-2 space-y-2"> <div v-for="payment in order.payments" :key="payment.id" class="rounded-lg bg-slate-50 px-3 py-2 text-slate-600"><p class="font-semibold capitalize text-slate-800">{{ payment.status }}<span v-if="payment.reference"> · Ref {{ payment.reference }}</span></p><p v-if="payment.submitted_at">Submitted {{ new Date(payment.submitted_at).toLocaleString() }}</p><p v-if="payment.failure_reason" class="text-red-700">{{ payment.failure_reason }}</p><p v-for="(refund, index) in payment.refunds" :key="index" class="text-amber-800">Refund {{ money(refund.amount, refund.currency) }} · {{ refund.reason }}<span v-if="refund.reference_number"> · Ref {{ refund.reference_number }}</span></p></div></div></details></article></div><p v-else class="px-5 py-8 text-center text-sm text-slate-500">No orders yet.</p></section>

                <section v-if="activeOrder" class="mt-6 rounded-xl border border-blue-200 bg-white p-5 shadow-sm"><div class="flex flex-wrap items-start justify-between gap-4"><div><h2 class="font-bold text-slate-950">Submit transfer receipt</h2><p class="mt-1 text-sm text-slate-600">Order {{ activeOrder.order_number }} · {{ money(activeOrder.total_amount, activeOrder.currency) }}</p></div><button type="button" class="text-sm font-semibold text-slate-500 hover:text-slate-900" @click="activeOrder = null">Close</button></div><form class="mt-4 grid gap-4 sm:grid-cols-2" @submit.prevent="submitProof"><label class="text-xs font-semibold text-slate-600">Bank transfer reference (optional)<input v-model="referenceNumber" type="text" maxlength="120" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900"></label><label class="text-xs font-semibold text-slate-600">Receipt image or PDF<input required type="file" accept="image/jpeg,image/png,application/pdf" class="mt-1 block w-full rounded-lg border border-slate-300 p-2 text-sm text-slate-700" @change="proof = $event.target.files?.[0] ?? null"></label><div class="sm:col-span-2"><button :disabled="submitting || !proof" class="rounded-lg bg-[var(--brand-primary)] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{{ submitting ? 'Submitting…' : 'Submit for review' }}</button></div></form></section>
            </template>
        </div>
    </section>
</template>
