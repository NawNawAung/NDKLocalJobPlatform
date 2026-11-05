<script setup>
import { onMounted, onUnmounted, ref } from 'vue';
import { locale, t } from '../i18n.js';

const open = ref(false);
const loading = ref(false);
const error = ref('');
const unreadCount = ref(0);
const notifications = ref([]);
const root = ref(null);
let refreshTimer;

function relativeTime(value) {
    if (!value) return '';
    const seconds = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
    if (seconds < 60) return t('Just now');
    if (seconds < 3600) return locale.value === 'my' ? `${Math.floor(seconds / 60)} မိနစ်က` : `${Math.floor(seconds / 60)}m ago`;
    if (seconds < 86400) return locale.value === 'my' ? `${Math.floor(seconds / 3600)} နာရီက` : `${Math.floor(seconds / 3600)}h ago`;
    return new Intl.DateTimeFormat(locale.value === 'my' ? 'my-MM' : 'en', { month: 'short', day: 'numeric' }).format(new Date(value));
}

function localizedNotificationMessage(notification) {
    const message = notification.message ?? '';
    if (locale.value !== 'my') return message;

    if (notification.title === 'New job application') {
        const match = message.match(/^(.+) applied for (.+)\.$/);
        if (match) return `${match[1]} က ${match[2]} အလုပ်ကို လျှောက်ထားပါသည်။`;
    }
    if (notification.title === 'New message') {
        const match = message.match(/^(.+) sent you a message\.$/);
        if (match) return `${match[1]} က သင့်ထံ စာတိုပေးပို့ထားပါသည်။`;
    }
    if (notification.title === 'Application update') {
        const match = message.match(/^Your application for (.+) is now (.+)\.$/);
        if (match) return `${match[1]} အလုပ်အတွက် သင့်လျှောက်လွှာအခြေအနေမှာ ${t(match[2].replaceAll('_', ' '))} ဖြစ်ပါသည်။`;
    }
    if (notification.title === 'Payment approved') {
        const match = message.match(/^Order (.+) has been paid and its items are active\.$/);
        if (match) return `အော်ဒါ ${match[1]} ကို ပေးချေပြီး ဝန်ဆောင်မှုများ အသက်သွင်းပြီးပါပြီ။`;
    }
    if (notification.title === 'Interview scheduled') {
        const match = message.match(/^An interview for (.+) has been scheduled for (.+) Myanmar time\.$/);
        if (match) return `${match[1]} အတွက် အင်တာဗျူးကို ${match[2]} မြန်မာစံတော်ချိန်တွင် စီစဉ်ထားပါသည်။`;
    }
    return message;
}

async function loadNotifications(showLoading = false) {
    if (showLoading) loading.value = true;
    try {
        const { data } = await window.axios.get('/api/notifications');
        notifications.value = data.notifications ?? [];
        unreadCount.value = data.unread_count ?? 0;
        error.value = '';
    } catch {
        error.value = t('Notifications could not be loaded.');
    } finally {
        loading.value = false;
    }
}

async function markAllRead() {
    if (!unreadCount.value) return;
    try {
        await window.axios.patch('/api/notifications/read-all');
        notifications.value.forEach((notification) => { notification.is_read = true; });
        unreadCount.value = 0;
    } catch { error.value = t('Could not mark notifications as read.'); }
}

async function openNotification(notification) {
    if (!notification.is_read) {
        try {
            await window.axios.patch(`/api/notifications/${notification.id}/read`);
            notification.is_read = true;
            unreadCount.value = Math.max(0, unreadCount.value - 1);
        } catch { /* Keep the destination available if the read update fails. */ }
    }
    open.value = false;
    if (notification.action_url) window.location.href = notification.action_url;
}

async function removeNotification(event, notification) {
    event.stopPropagation();
    try {
        await window.axios.delete(`/api/notifications/${notification.id}`);
        notifications.value = notifications.value.filter((item) => item.id !== notification.id);
        if (!notification.is_read) unreadCount.value = Math.max(0, unreadCount.value - 1);
    } catch { error.value = t('Could not delete this notification.'); }
}

function closeOnOutsideClick(event) {
    if (root.value && !root.value.contains(event.target)) open.value = false;
}

onMounted(() => {
    loadNotifications();
    refreshTimer = setInterval(() => loadNotifications(), 30000);
    document.addEventListener('click', closeOnOutsideClick);
});
onUnmounted(() => {
    clearInterval(refreshTimer);
    document.removeEventListener('click', closeOnOutsideClick);
});
</script>

<template>
    <div ref="root" class="relative">
        <button type="button" class="relative grid h-10 w-10 place-items-center rounded-lg text-xl text-slate-600 transition hover:bg-blue-50 hover:text-blue-800" :aria-expanded="open" aria-haspopup="dialog" :aria-label="$t('Notifications')" @click="open = !open; open && loadNotifications(true)">
            <i class="ti ti-bell" aria-hidden="true" />
            <span v-if="unreadCount" class="absolute right-1 top-1 grid min-h-4 min-w-4 place-items-center rounded-full bg-red-600 px-1 text-[9px] font-bold leading-none text-white">{{ unreadCount > 99 ? '99+' : unreadCount }}</span>
        </button>

        <section v-if="open" class="absolute right-0 top-full z-[80] mt-2 w-[min(24rem,calc(100vw-2rem))] overflow-hidden rounded-xl border border-slate-200 bg-white text-left shadow-xl" role="dialog" :aria-label="$t('Notifications')">
            <header class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3.5"><div><h2 class="font-bold text-slate-900">{{ $t('Notifications') }}</h2><p class="mt-0.5 text-xs text-slate-500">{{ unreadCount }} {{ $t('unread') }}</p></div><button type="button" :disabled="!unreadCount" class="text-xs font-semibold text-blue-800 hover:underline disabled:text-slate-400 disabled:no-underline" @click="markAllRead">{{ $t('Mark all read') }}</button></header>
            <p v-if="error" class="px-4 py-3 text-sm text-red-700" role="alert">{{ $t(error) }}</p>
            <div v-if="loading && !notifications.length" class="px-4 py-8 text-center text-sm text-slate-500">{{ $t('Loading notifications…') }}</div>
            <div v-else-if="notifications.length" class="max-h-[min(65vh,28rem)] overflow-y-auto">
                <article v-for="notification in notifications" :key="notification.id" class="group relative border-b border-slate-100 transition hover:bg-slate-50" :class="notification.is_read ? 'bg-white' : 'bg-blue-50/60'">
                    <button type="button" class="w-full px-4 py-3.5 pr-12 text-left" @click="openNotification(notification)"><span class="flex items-start gap-3"><span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg" :class="notification.is_read ? 'bg-slate-100 text-slate-500' : 'bg-blue-100 text-blue-800'"><i :class="notification.category === 'message' ? 'ti ti-message' : notification.category === 'application' ? 'ti ti-briefcase' : notification.category === 'job' ? 'ti ti-briefcase-2' : notification.category === 'interview' ? 'ti ti-calendar-event' : 'ti ti-bell'" aria-hidden="true"/></span><span class="min-w-0 flex-1"><span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-semibold text-slate-900">{{ $t(notification.title) }}</span><span v-if="!notification.is_read" class="h-2 w-2 shrink-0 rounded-full bg-blue-700" :aria-label="$t('Unread')"/></span><span class="mt-1 block text-xs leading-5 text-slate-600">{{ localizedNotificationMessage(notification) }}</span><time class="mt-1.5 block text-[11px] text-slate-400">{{ relativeTime(notification.created_at) }}</time></span></span></button>
                    <button type="button" class="absolute right-2 top-3 grid h-8 w-8 place-items-center rounded-md text-slate-400 opacity-0 transition hover:bg-slate-200 hover:text-slate-700 group-hover:opacity-100 focus:opacity-100" :aria-label="`Delete ${notification.title}`" @click="removeNotification($event, notification)"><i class="ti ti-x" aria-hidden="true"/></button>
                </article>
            </div>
            <p v-else class="px-4 py-10 text-center text-sm text-slate-500">{{ $t('You’re all caught up.') }}</p>
            <footer class="border-t border-slate-100 bg-slate-50 px-4 py-2.5"><p class="text-center text-[11px] text-slate-500">{{ locale === 'my' ? 'နောက်ဆုံး အသိပေးချက် ၂၅ ခုကို ပြသထားသည်' : 'Showing your 25 most recent notifications' }}</p></footer>
        </section>
    </div>
</template>
