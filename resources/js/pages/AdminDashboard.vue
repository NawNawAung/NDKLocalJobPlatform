<script setup>
import { computed, onMounted, reactive, ref } from 'vue';

const tabs = [
    { id: 'overview', label: 'Overview', icon: 'ti ti-layout-dashboard' },
    { id: 'users', label: 'Users', icon: 'ti ti-users' },
    { id: 'employers', label: 'Employers', icon: 'ti ti-building' },
    { id: 'jobs', label: 'Job listings', icon: 'ti ti-briefcase' },
    { id: 'applications', label: 'Applications', icon: 'ti ti-file-text' },
    { id: 'reports', label: 'Reports', icon: 'ti ti-flag' },
    { id: 'categories', label: 'Categories', icon: 'ti ti-category' },
    { id: 'reviews', label: 'Employer reviews', icon: 'ti ti-star' },
    { id: 'audit', label: 'Audit log', icon: 'ti ti-history' },
];
const tab = ref('overview');
const loading = ref(true);
const actionId = ref(null);
const error = ref('');
const notice = ref('');
const stats = ref({});
const activity = ref([]);
const chart = ref([]);
const rows = ref([]);
const categories = ref([]);
const expandedJob = ref(null);
const auditFilters = reactive({ q: '', action: '', administrator_id: '', target_type: '', from: '', to: '' });
const auditOptions = ref({ actions: [], administrators: [] });
const categoryDraft = reactive({ name: '', sort_order: 0 });
const categoryEdits = reactive({});
const filters = reactive({ q: '', role: '', status: '', verification_status: '', category_id: '' });
const items = computed(() => rows.value?.data ?? []);
const metricCards = computed(() => [
    { label: 'Total users', value: stats.value.users, icon: 'ti-users', tone: 'bg-blue-50 text-blue-800' },
    { label: 'Job seekers', value: stats.value.job_seekers, icon: 'ti-user-search', tone: 'bg-indigo-50 text-indigo-800' },
    { label: 'Employers', value: stats.value.employers, icon: 'ti-building', tone: 'bg-violet-50 text-violet-800' },
    { label: 'Active jobs', value: stats.value.active_jobs, icon: 'ti-briefcase', tone: 'bg-emerald-50 text-emerald-800' },
    { label: 'Pending listings', value: stats.value.pending_jobs, icon: 'ti-hourglass', tone: 'bg-amber-50 text-amber-800' },
    { label: 'Applications', value: stats.value.applications, icon: 'ti-file-text', tone: 'bg-sky-50 text-sky-800' },
    { label: 'Verification reviews', value: stats.value.pending_verifications, icon: 'ti-rosette-discount-check', tone: 'bg-purple-50 text-purple-800' },
    { label: 'Open reports', value: stats.value.open_reports, icon: 'ti-flag', tone: 'bg-rose-50 text-rose-800' },
]);

function paramsForTab() {
    const params = {};
    for (const key of ['q', 'role', 'status', 'verification_status', 'category_id']) if (filters[key]) params[key] = filters[key];
    return params;
}
async function loadOverview() {
    const { data } = await window.axios.get('/api/admin/dashboard');
    stats.value = data.stats ?? {};
    activity.value = data.recent_activity ?? [];
    chart.value = data.job_activity ?? [];
}
async function loadTab() {
    if (tab.value === 'overview') return loadOverview();
    if (tab.value === 'users') { const { data } = await window.axios.get('/api/admin/users', { params: paramsForTab() }); rows.value = data.users; }
    if (tab.value === 'employers') { const { data } = await window.axios.get('/api/admin/employers', { params: paramsForTab() }); rows.value = data.employers; }
    if (tab.value === 'jobs') { const { data } = await window.axios.get('/api/admin/jobs', { params: paramsForTab() }); rows.value = data.jobs; categories.value = data.categories ?? []; }
    if (tab.value === 'applications') { const { data } = await window.axios.get('/api/admin/applications'); rows.value = data.applications; }
    if (tab.value === 'reports') { const { data } = await window.axios.get('/api/admin/reports', { params: { status: filters.status || undefined } }); rows.value = data.reports; }
    if (tab.value === 'categories') { const { data } = await window.axios.get('/api/admin/categories', { params: { q: filters.q || undefined, status: filters.status || undefined } }); rows.value = data.categories; }
    if (tab.value === 'reviews') { const { data } = await window.axios.get('/api/admin/employer-reviews', { params: { q: filters.q || undefined, status: filters.status || undefined } }); rows.value = data.reviews; }
    if (tab.value === 'audit') { const { data } = await window.axios.get('/api/admin/audit-logs', { params: Object.fromEntries(Object.entries(auditFilters).filter(([, value]) => value)) }); rows.value = data.logs; auditOptions.value = { actions: data.actions ?? [], administrators: data.administrators ?? [] }; }
    if (tab.value === 'categories') rows.value?.data?.forEach((row) => { categoryEdits[row.id] ??= { name: row.name, sort_order: row.sort_order }; });
}
async function load() {
    loading.value = true; error.value = '';
    try { await loadTab(); }
    catch (exception) { error.value = exception.response?.data?.message ?? 'The administration data could not be loaded.'; }
    finally { loading.value = false; }
}
async function act(id, callback) {
    actionId.value = id; error.value = ''; notice.value = '';
    try { const message = await callback(); notice.value = message ?? 'Changes saved.'; await load(); }
    catch (exception) { error.value = exception.response?.data?.message ?? 'The requested change could not be saved.'; }
    finally { actionId.value = null; }
}
function switchTab(next) { tab.value = next; error.value = ''; notice.value = ''; load(); }
function search() { load(); }
async function changeUserRole(row, nextRole) {
    try {
        const { data } = await window.axios.patch(`/api/admin/users/${row.id}`, { role: nextRole });
        notice.value = data.message;
        await load();
    } catch (exception) {
        error.value = exception.response?.data?.message ?? 'The role could not be changed.';
        await load();
    }
}
function changePage(url) {
    if (!url) return;
    loading.value = true;
    window.axios.get(url).then(({ data }) => {
        const key = ({ users: 'users', employers: 'employers', jobs: 'jobs', applications: 'applications', reports: 'reports', categories: 'categories', reviews: 'reviews', audit: 'logs' })[tab.value];
        rows.value = data[key];
    }).catch((exception) => { error.value = exception.response?.data?.message ?? 'Could not load this page.'; }).finally(() => { loading.value = false; });
}
function dateLabel(date) { return date ? new Date(date).toLocaleString() : '—'; }
function statusClass(status) {
    if (['verified', 'published', 'active', 'actioned', 'hired', 'offered'].includes(status)) return 'bg-emerald-50 text-emerald-800';
    if (['pending', 'open', 'draft', 'unverified', 'reviewing'].includes(status)) return 'bg-amber-50 text-amber-800';
    if (['rejected', 'closed', 'inactive', 'dismissed', 'paused'].includes(status)) return 'bg-slate-100 text-slate-700';
    return 'bg-blue-50 text-blue-800';
}
onMounted(load);
</script>

<template>
    <section class="min-h-[70vh] bg-slate-50 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <header class="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div><p class="text-sm font-semibold text-[var(--brand-primary)]">Platform administration</p><h1 class="mt-1 text-3xl font-bold tracking-tight text-[var(--brand-ink)]">Admin dashboard</h1><p class="mt-2 text-sm text-slate-600">Manage platform accounts, listings, employer verification, and reports.</p></div>
                <a href="/#admin-billing" class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="ti ti-receipt" aria-hidden="true"/>Payment review</a>
            </header>
            <div class="mb-6 flex gap-2 overflow-x-auto rounded-xl border border-slate-200 bg-white p-2" role="tablist" aria-label="Administration sections">
                <button v-for="item in tabs" :key="item.id" type="button" role="tab" :aria-selected="tab === item.id" class="inline-flex shrink-0 items-center gap-2 rounded-lg px-3.5 py-2.5 text-sm font-semibold transition" :class="tab === item.id ? 'bg-blue-50 text-blue-900 ring-1 ring-blue-100' : 'text-slate-600 hover:bg-slate-50'" @click="switchTab(item.id)"><i :class="item.icon" aria-hidden="true"/>{{ item.label }}</button>
            </div>
            <p v-if="error" class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">{{ error }}</p>
            <p v-if="notice" class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ notice }}</p>
            <div v-if="loading" class="rounded-xl border border-slate-200 bg-white p-12 text-center text-sm text-slate-500"><i class="ti ti-loader-2 mr-2 animate-spin" aria-hidden="true"/>Loading administration data…</div>

            <template v-else-if="tab === 'overview'">
                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <article v-for="card in metricCards" :key="card.label" class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><p class="text-sm font-medium text-slate-600">{{ card.label }}</p><span class="grid h-10 w-10 place-items-center rounded-lg" :class="card.tone"><i :class="`ti ${card.icon} text-xl`" aria-hidden="true"/></span></div><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ card.value ?? 0 }}</p></article>
                </div>
                <div class="mt-6 grid gap-6 xl:grid-cols-[1fr_1fr]">
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-950">Listing activity</h2><p class="mt-1 text-xs text-slate-500">New listings over the last 14 days</p></div><i class="ti ti-chart-bar text-xl text-blue-700" aria-hidden="true"/></div><div class="mt-6 flex h-40 items-end gap-2 border-b border-slate-100 px-1"> <div v-for="day in chart" :key="day.date" class="group flex h-full min-w-0 flex-1 flex-col justify-end"><span class="mb-1 text-center text-[10px] text-slate-500">{{ day.total }}</span><div class="mx-auto w-full max-w-8 rounded-t bg-blue-600" :style="{ height: `${Math.max(5, Number(day.total) / Math.max(1, ...chart.map(item => Number(item.total))) * 100)}%` }" :title="`${day.date}: ${day.total} listings`"/><span class="mt-2 truncate text-center text-[10px] text-slate-400">{{ new Date(day.date).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }) }}</span></div><p v-if="!chart.length" class="m-auto text-sm text-slate-500">No listing activity recorded.</p></div></section>
                    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><div class="flex items-center justify-between"><div><h2 class="font-bold text-slate-950">Recent platform activity</h2><p class="mt-1 text-xs text-slate-500">Latest account, listing, application, and report events</p></div><button type="button" class="text-sm font-semibold text-blue-800 hover:underline" @click="load">Refresh</button></div><div v-if="activity.length" class="mt-4 divide-y divide-slate-100"><div v-for="(event, index) in activity" :key="`${event.type}-${index}`" class="flex items-start gap-3 py-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-blue-50 text-blue-800"><i :class="event.type === 'user' ? 'ti ti-user-plus' : event.type === 'job' ? 'ti ti-briefcase' : event.type === 'report' ? 'ti ti-flag' : 'ti ti-file-text'" aria-hidden="true"/></span><div class="min-w-0 flex-1"><p class="text-sm font-semibold text-slate-800">{{ event.label }}</p><p class="truncate text-xs text-slate-500">{{ event.detail }}</p></div><time class="shrink-0 text-[11px] text-slate-400">{{ dateLabel(event.at) }}</time></div></div><p v-else class="py-10 text-center text-sm text-slate-500">No recent activity.</p></section>
                </div>
            </template>

            <template v-else>
                <form v-if="['users','employers','jobs','reports','categories','reviews'].includes(tab)" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4" @submit.prevent="search">
                    <label class="min-w-48 flex-1 text-xs font-semibold text-slate-600">Search<input v-model="filters.q" type="search" :placeholder="tab === 'jobs' ? 'Job title or company' : tab === 'employers' ? 'Company or account name' : 'Name or email'" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal text-slate-900 outline-none focus:border-blue-500 focus:ring-4 focus:ring-blue-100"/></label>
                    <label v-if="tab === 'users'" class="min-w-36 text-xs font-semibold text-slate-600">Role<select v-model="filters.role" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All roles</option><option value="job_seeker">Job seeker</option><option value="employer">Employer</option><option value="admin">Admin</option></select></label>
                    <label v-if="tab === 'users'" class="min-w-36 text-xs font-semibold text-slate-600">Account status<select v-model="filters.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option></select></label>
                    <label v-if="tab === 'employers'" class="min-w-40 text-xs font-semibold text-slate-600">Verification<select v-model="filters.verification_status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All</option><option value="pending">Pending review</option><option value="verified">Verified</option><option value="unverified">Unverified</option><option value="rejected">Rejected</option></select></label>
                    <label v-if="tab === 'jobs'" class="min-w-40 text-xs font-semibold text-slate-600">Status<select v-model="filters.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All statuses</option><option value="draft">Pending / draft</option><option value="published">Published</option><option value="paused">Paused</option><option value="closed">Closed</option><option value="expired">Expired</option></select></label>
                    <label v-if="tab === 'jobs'" class="min-w-40 text-xs font-semibold text-slate-600">Category<select v-model="filters.category_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All categories</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select></label>
                    <label v-if="tab === 'reports'" class="min-w-36 text-xs font-semibold text-slate-600">Report status<select v-model="filters.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All</option><option value="open">Open</option><option value="actioned">Actioned</option><option value="dismissed">Dismissed</option></select></label>
                    <label v-if="['categories','reviews'].includes(tab)" class="min-w-36 text-xs font-semibold text-slate-600">Status<select v-model="filters.status" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm font-normal"><option value="">All</option><option value="active">Active</option><option value="inactive">Inactive</option><option v-if="tab === 'reviews'" value="pending">Pending</option><option v-if="tab === 'reviews'" value="approved">Approved</option><option v-if="tab === 'reviews'" value="rejected">Rejected</option></select></label>
                    <button class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]">Apply filters</button>
                </form>

                <form v-if="tab === 'categories'" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4" @submit.prevent="act('new-category', async () => { const { data } = await window.axios.post('/api/admin/categories', categoryDraft); categoryDraft.name = ''; categoryDraft.sort_order = 0; return data.message; })"><label class="min-w-56 flex-1 text-xs font-semibold text-slate-600">New category<input v-model="categoryDraft.name" required maxlength="100" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal"></label><label class="w-32 text-xs font-semibold text-slate-600">Sort order<input v-model.number="categoryDraft.sort_order" type="number" min="0" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal"></label><button class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white">Add category</button></form>

                <form v-if="tab === 'audit'" class="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4" @submit.prevent="search"><label class="min-w-40 flex-1 text-xs font-semibold text-slate-600">Search<input v-model="auditFilters.q" type="search" placeholder="Action or record ID" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm font-normal"></label><label class="min-w-40 text-xs font-semibold text-slate-600">Action<select v-model="auditFilters.action" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">All actions</option><option v-for="action in auditOptions.actions" :key="action" :value="action">{{ action }}</option></select></label><label class="min-w-40 text-xs font-semibold text-slate-600">Administrator<select v-model="auditFilters.administrator_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="">All administrators</option><option v-for="admin in auditOptions.administrators" :key="admin.id" :value="admin.id">{{ admin.name }}</option></select></label><label class="w-36 text-xs font-semibold text-slate-600">From<input v-model="auditFilters.from" type="date" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-2.5 text-sm"></label><label class="w-36 text-xs font-semibold text-slate-600">To<input v-model="auditFilters.to" type="date" class="mt-1 block w-full rounded-lg border border-slate-300 px-2 py-2.5 text-sm"></label><button class="rounded-lg bg-[var(--brand-primary)] px-4 py-2.5 text-sm font-semibold text-white">Filter log</button></form>

                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div v-if="items.length" class="overflow-x-auto">
                        <table class="w-full min-w-[760px] text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr>
                                <th class="px-4 py-3">{{ tab === 'categories' ? 'Category' : tab === 'reviews' ? 'Employer / reviewer' : tab === 'audit' ? 'Admin action' : tab === 'users' ? 'Account' : tab === 'employers' ? 'Employer' : tab === 'jobs' ? 'Listing' : tab === 'applications' ? 'Application' : 'Reported content' }}</th>
                                <th class="px-4 py-3">{{ tab === 'categories' ? 'Sort order' : tab === 'reviews' ? 'Review' : tab === 'audit' ? 'Target / changes' : tab === 'users' ? 'Role' : tab === 'jobs' ? 'Category / location' : tab === 'applications' ? 'Employer' : tab === 'reports' ? 'Reason' : 'Contact' }}</th>
                                <th class="px-4 py-3">{{ tab === 'audit' ? 'Source' : 'Status' }}</th><th class="px-4 py-3">{{ ['applications','audit'].includes(tab) ? 'Recorded' : tab === 'reports' ? 'Reported by' : 'Created' }}</th><th class="px-4 py-3 text-right">Actions</th>
                            </tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-for="row in items" :key="row.id" class="align-top hover:bg-slate-50/70">
                                    <template v-if="tab === 'categories'"><td class="px-4 py-4"><input v-model="categoryEdits[row.id].name" maxlength="100" class="w-full rounded border border-slate-300 px-2 py-1 font-semibold"><p class="mt-1 text-xs text-slate-500">{{ row.jobs_count }} linked listings</p></td><td class="px-4 py-4"><input v-model.number="categoryEdits[row.id].sort_order" type="number" min="0" class="w-24 rounded border border-slate-300 px-2 py-1 text-sm"></td><td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="row.is_active ? statusClass('active') : statusClass('inactive')">{{ row.is_active ? 'Active' : 'Inactive' }}</span></td><td class="px-4 py-4 text-xs text-slate-500">{{ dateLabel(row.created_at) }}</td><td class="px-4 py-4 text-right"><div class="flex justify-end gap-2"><button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/categories/${row.id}`, categoryEdits[row.id]); return data.message; })">Save</button><button type="button" class="rounded-lg border px-3 py-1.5 text-xs font-semibold" :class="row.is_active ? 'border-amber-200 text-amber-800' : 'border-emerald-200 text-emerald-800'" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/categories/${row.id}`, { is_active: !row.is_active }); return data.message; })">{{ row.is_active ? 'Deactivate' : 'Activate' }}</button></div></td></template>
                                    <template v-else-if="tab === 'reviews'"><td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ row.employer }}</p><p class="mt-1 text-xs text-slate-500">{{ row.reviewer }} · {{ row.job_title }}</p></td><td class="max-w-xl px-4 py-4"><p class="font-semibold text-slate-700">{{ row.rating }}/5<span v-if="row.title"> · {{ row.title }}</span></p><p class="mt-1 line-clamp-3 text-sm text-slate-600">{{ row.review }}</p><p v-if="row.moderation_notes" class="mt-1 text-xs text-slate-500">Moderator note: {{ row.moderation_notes }}</p></td><td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClass(row.status)">{{ row.status }}</span></td><td class="px-4 py-4 text-xs text-slate-500">{{ dateLabel(row.created_at) }}</td><td class="px-4 py-4 text-right"><div v-if="row.status !== 'approved'" class="flex justify-end gap-2"><button type="button" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/employer-reviews/${row.id}`, { decision: 'approve' }); return data.message; })">Approve</button><button type="button" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-800" @click="act(row.id, async () => { const notes = window.prompt('Reason for rejecting this review?'); if (!notes?.trim()) throw new Error('A rejection reason is required.'); const { data } = await window.axios.patch(`/api/admin/employer-reviews/${row.id}`, { decision: 'reject', notes }); return data.message; })">Reject</button></div></td></template>
                                    <template v-else-if="tab === 'audit'"><td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ row.action }}</p><p class="mt-1 text-xs text-slate-500">{{ row.administrator }}</p></td><td class="px-4 py-4"><p>{{ row.target_type }} #{{ row.target_id ?? '—' }}</p><details class="mt-1 text-xs text-slate-500"><summary class="cursor-pointer">Changed data</summary><pre class="mt-2 max-w-lg overflow-x-auto whitespace-pre-wrap">{{ JSON.stringify({ before: row.before, after: row.after }, null, 2) }}</pre></details></td><td class="px-4 py-4 text-xs text-slate-500">{{ row.ip_address || '—' }}</td><td class="px-4 py-4 text-xs text-slate-500">{{ dateLabel(row.created_at) }}</td><td class="px-4 py-4 text-right text-xs text-slate-400">Recorded</td></template>
                                    <template v-else>
                                    <td class="px-4 py-4"><p class="font-semibold text-slate-900">{{ tab === 'users' ? row.name : tab === 'employers' ? row.company_name : tab === 'jobs' ? row.title : tab === 'applications' ? row.job_title : row.target_label }}</p><p class="mt-1 text-xs text-slate-500">{{ tab === 'users' ? row.email : tab === 'employers' ? row.user?.name : tab === 'jobs' ? row.company : tab === 'applications' ? `Application #${row.id}` : `${row.target_type} #${row.target_id} · ${row.details || 'No additional details'}` }}</p></td>
                                    <td class="px-4 py-4 text-slate-600"><template v-if="tab === 'users'"><span class="capitalize">{{ row.role.replaceAll('_',' ') }}</span><p class="mt-1 text-xs text-slate-500">{{ row.company || row.professional_title || '' }}</p></template><template v-else-if="tab === 'employers'"><p>{{ row.user?.email || '—' }}</p><p class="mt-1 text-xs">{{ row.location || 'Location not provided' }}</p><p v-if="row.rating !== null" class="mt-1 text-xs font-semibold text-amber-700">★ {{ row.rating }}/5 · {{ row.review_count }} reviews</p><p v-else class="mt-1 text-xs text-slate-400">No approved reviews</p></template><template v-else-if="tab === 'jobs'"><p>{{ row.category }}</p><p class="mt-1 text-xs">{{ row.location }} · {{ row.applications_count }} applications</p></template><template v-else-if="tab === 'applications'">{{ row.company || '—' }}</template><template v-else><span class="capitalize">{{ row.reason }}</span></template></td>
                                    <td class="px-4 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold capitalize" :class="statusClass(tab === 'users' ? (row.status ? 'active' : 'inactive') : tab === 'employers' ? row.verification_status : row.status)">{{ tab === 'users' ? (row.status ? 'Active' : 'Inactive') : tab === 'employers' ? row.verification_status : row.status }}</span></td>
                                    <td class="px-4 py-4 text-xs text-slate-500">{{ dateLabel(tab === 'applications' ? row.submitted_at : tab === 'reports' ? row.created_at : tab === 'employers' ? row.requested_at || row.created_at : row.created_at) }}</td>
                                    <td class="px-4 py-4 text-right"><div class="flex flex-wrap justify-end gap-2">
                                        <select v-if="tab === 'users' && row.role !== 'admin'" :value="row.role" class="rounded-lg border border-slate-300 px-2 py-1.5 text-xs" aria-label="User role" @change="changeUserRole(row, $event.target.value)"><option value="job_seeker">Job seeker</option><option value="employer">Employer</option></select><button v-if="tab === 'users' && row.role !== 'admin'" type="button" :disabled="actionId === row.id" class="rounded-lg border px-3 py-1.5 text-xs font-semibold disabled:opacity-50" :class="row.status ? 'border-red-200 text-red-800 hover:bg-red-50' : 'border-emerald-200 text-emerald-800 hover:bg-emerald-50'" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/users/${row.id}`, { status: !row.status }); return data.message; })">{{ row.status ? 'Deactivate' : 'Activate' }}</button>
                                        <template v-if="tab === 'employers' && row.verification_status !== 'verified'"><button type="button" :disabled="actionId === row.id" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/employers/${row.id}/verification`, { decision: 'approve' }); return data.message; })">Verify</button><button type="button" :disabled="actionId === row.id" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-800 hover:bg-red-50 disabled:opacity-50" @click="act(row.id, async () => { const notes = window.prompt('Reason for rejecting verification?'); if (!notes?.trim()) throw new Error('A rejection reason is required.'); const { data } = await window.axios.patch(`/api/admin/employers/${row.id}/verification`, { decision: 'reject', notes }); return data.message; })">Reject</button></template>
                                        <template v-if="tab === 'jobs'"><button type="button" class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50" @click="expandedJob = expandedJob === row.id ? null : row.id">{{ expandedJob === row.id ? 'Hide details' : 'Details' }}</button><button v-if="row.status === 'draft' || row.status === 'paused'" type="button" :disabled="actionId === row.id" class="rounded-lg bg-emerald-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-800 disabled:opacity-50" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/jobs/${row.id}/moderation`, { status: 'published' }); return data.message; })">Approve / publish</button><button v-if="['published','draft','paused'].includes(row.status)" type="button" :disabled="actionId === row.id" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-800 hover:bg-red-50 disabled:opacity-50" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/jobs/${row.id}/moderation`, { status: 'closed' }); return data.message; })">Close listing</button></template>
                                        <template v-if="tab === 'reports' && row.status === 'open'"><button type="button" :disabled="actionId === row.id" class="rounded-lg bg-red-700 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-800 disabled:opacity-50" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/reports/${row.id}`, { decision: 'actioned' }); return data.message; })">Take action</button><button type="button" :disabled="actionId === row.id" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-50" @click="act(row.id, async () => { const { data } = await window.axios.patch(`/api/admin/reports/${row.id}`, { decision: 'dismissed' }); return data.message; })">Dismiss</button></template>
                                    </div></td>
                                    </template>
                                </tr>
                                <tr v-if="tab === 'jobs' && expandedJob === row.id" :key="`details-${row.id}`"><td colspan="5" class="bg-slate-50 px-5 py-4"><p class="whitespace-pre-line text-sm leading-6 text-slate-700">{{ row.description }}</p><p v-if="row.requirements" class="mt-3 whitespace-pre-line text-xs leading-5 text-slate-600"><span class="font-semibold">Requirements: </span>{{ row.requirements }}</p><p class="mt-2 text-xs text-slate-500">Salary: {{ row.salary_min == null && row.salary_max == null ? 'Not specified' : `${new Intl.NumberFormat().format(row.salary_min ?? 0)}–${new Intl.NumberFormat().format(row.salary_max ?? 0)} MMK` }} · Deadline: {{ row.deadline || 'Open ended' }}</p></td></tr>
                            </tbody>
                        </table>
                    </div>
                    <div v-else class="px-6 py-14 text-center"><i :class="tabs.find(item => item.id === tab)?.icon" class="text-3xl text-slate-300" aria-hidden="true"/><h2 class="mt-3 font-semibold text-slate-900">Nothing to review</h2><p class="mt-1 text-sm text-slate-500">No records match this view.</p></div>
                    <footer v-if="rows?.links?.length" class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-4 py-3"><span class="text-xs text-slate-500">Showing {{ rows.from || 0 }}–{{ rows.to || 0 }} of {{ rows.total || 0 }}</span><div class="flex gap-1"><button v-for="(link,index) in rows.links" :key="index" type="button" :disabled="!link.url || loading" class="rounded-md border px-3 py-1.5 text-xs disabled:opacity-40" :class="link.active ? 'border-blue-700 bg-blue-700 text-white' : 'border-slate-200 text-slate-700 hover:bg-slate-50'" v-html="link.label" @click="changePage(link.url)"/></div></footer>
                </div>
                <p v-if="tab === 'applications'" class="mt-3 text-xs text-slate-500">Administrative overview intentionally excludes candidate contact details, CVs, cover letters, and interview content.</p>
            </template>
        </div>
    </section>
</template>
