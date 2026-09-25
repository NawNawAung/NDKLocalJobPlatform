<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const authenticated = Boolean(window.__AUTH_BOOTSTRAP__?.authenticated);
const conversations = ref([]);
const messages = ref([]);
const currentUserId = ref(null);
const selectedConversationId = ref(null);
const search = ref('');
const conversationFilter = ref('all');
const draft = ref('');
const attachments = ref([]);
const loading = ref(false);
const sending = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const conversationLoading = ref(false);
const listElement = ref(null);
let pollTimer;
let searchTimer;
let activeConversationRequest = 0;

const selectedConversation = computed(() => conversations.value.find((conversation) => conversation.id === selectedConversationId.value) ?? null);
const recipientName = computed(() => selectedConversation.value?.other_participant?.name ?? 'your conversation partner');
const filteredConversations = computed(() => conversationFilter.value === 'unread'
    ? conversations.value.filter((conversation) => conversation.unread_count > 0)
    : conversations.value);

function formatTime(date, includeDate = false) {
    if (!date) return '';
    const value = new Date(date);
    if (Number.isNaN(value.getTime())) return '';
    return new Intl.DateTimeFormat(undefined, includeDate
        ? { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit' }
        : { hour: 'numeric', minute: '2-digit' }).format(value);
}

function messagePreview(message) {
    if (!message) return 'Start a conversation';
    return message.body || (message.attachments?.length ? 'Shared an attachment' : 'Message');
}

async function loadConversations({ preserveSelection = true } = {}) {
    if (!authenticated) return;
    try {
        const { data } = await window.axios.get('/api/conversations', { params: { search: search.value.trim() || undefined } });
        conversations.value = data.conversations ?? [];
        currentUserId.value = data.current_user_id;
        if (!preserveSelection || !conversations.value.some((item) => item.id === selectedConversationId.value)) {
            const next = conversations.value[0];
            if (next) await selectConversation(next.id);
            else {
                selectedConversationId.value = null;
                messages.value = [];
            }
        }
    } catch (error) {
        if (error.response?.status === 401) errorMessage.value = 'Please sign in to view your messages.';
        else errorMessage.value = 'Unable to load conversations. Please try again.';
    }
}

async function selectConversation(id) {
    if (!id) return;
    selectedConversationId.value = id;
    conversationLoading.value = true;
    errorMessage.value = '';
    const requestId = ++activeConversationRequest;
    try {
        const { data } = await window.axios.get(`/api/conversations/${id}/messages`);
        if (requestId !== activeConversationRequest || selectedConversationId.value !== id) return;
        currentUserId.value = data.current_user_id;
        messages.value = data.messages ?? [];
        await window.axios.patch(`/api/conversations/${id}/read`);
        const conversation = conversations.value.find((item) => item.id === id);
        if (conversation) conversation.unread_count = 0;
        await scrollToLatest();
    } catch (error) {
        errorMessage.value = error.response?.status === 403
            ? 'You do not have access to this conversation.'
            : 'Unable to load this conversation.';
    } finally {
        if (requestId === activeConversationRequest) conversationLoading.value = false;
    }
}

async function refreshSelectedMessages() {
    if (!authenticated || !selectedConversationId.value || conversationLoading.value || sending.value) return;
    try {
        const { data } = await window.axios.get(`/api/conversations/${selectedConversationId.value}/messages`);
        if (selectedConversationId.value !== data.messages?.[0]?.conversation_id && data.messages?.length) return;
        const previousLatestId = messages.value.at(-1)?.id;
        messages.value = data.messages ?? [];
        if (messages.value.at(-1)?.id !== previousLatestId) {
            await window.axios.patch(`/api/conversations/${selectedConversationId.value}/read`);
            await scrollToLatest();
        }
        await loadConversations();
    } catch {
        // Keep the conversation usable if a background refresh fails.
    }
}

function handleFiles(event) {
    attachments.value = [...event.target.files].slice(0, 5);
    successMessage.value = '';
}

function removeAttachment(index) {
    attachments.value.splice(index, 1);
}

async function sendMessage() {
    if (!selectedConversationId.value || sending.value) return;
    const body = draft.value.trim();
    if (!body && attachments.value.length === 0) return;

    sending.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    const form = new FormData();
    if (body) form.append('body', body);
    attachments.value.forEach((file) => form.append('attachments[]', file));
    try {
        const { data } = await window.axios.post(`/api/conversations/${selectedConversationId.value}/messages`, form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        messages.value.push(data.message);
        draft.value = '';
        attachments.value = [];
        successMessage.value = 'Message sent.';
        await loadConversations();
        await scrollToLatest();
    } catch (error) {
        errorMessage.value = error.response?.data?.errors?.body?.[0]
            ?? error.response?.data?.message
            ?? 'Message could not be sent. Check your connection and try again.';
    } finally {
        sending.value = false;
    }
}

async function scrollToLatest() {
    await new Promise((resolve) => requestAnimationFrame(resolve));
    if (listElement.value) listElement.value.scrollTop = listElement.value.scrollHeight;
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => loadConversations({ preserveSelection: false }), 250);
});

onMounted(async () => {
    if (!authenticated) return;
    loading.value = true;
    await loadConversations({ preserveSelection: false });
    loading.value = false;
    pollTimer = setInterval(() => {
        loadConversations().then(refreshSelectedMessages);
    }, 5000);
});

onUnmounted(() => {
    clearInterval(pollTimer);
    clearTimeout(searchTimer);
});
</script>

<template>
    <section class="min-h-[calc(100vh-9rem)] bg-slate-50 px-3 py-5 sm:px-6 sm:py-8">
        <div class="mx-auto flex min-h-[680px] max-w-7xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <aside class="flex w-full shrink-0 flex-col border-r border-slate-200 md:w-80 lg:w-96" :class="selectedConversation ? 'hidden md:flex' : 'flex'">
                <div class="border-b border-slate-100 p-5">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-[0.12em] text-[var(--brand-primary)]">Inbox</p>
                            <h1 class="mt-1 text-xl font-bold text-[var(--brand-ink)]">Messages</h1>
                        </div>
                        <span v-if="conversations.reduce((sum, item) => sum + item.unread_count, 0)" class="rounded-full bg-blue-100 px-2.5 py-1 text-xs font-bold text-blue-800">{{ conversations.reduce((sum, item) => sum + item.unread_count, 0) }} unread</span>
                    </div>
                    <label class="relative mt-4 block">
                        <span class="sr-only">Search conversations</span>
                        <i class="ti ti-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                        <input v-model="search" type="search" placeholder="Search people or jobs" class="w-full rounded-lg border border-slate-200 bg-slate-50 py-2.5 pl-9 pr-3 text-sm outline-none transition focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" />
                    </label>
                    <div class="mt-3 flex gap-2" aria-label="Conversation filters">
                        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="conversationFilter === 'all' ? 'bg-blue-800 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'" @click="conversationFilter = 'all'">All</button>
                        <button type="button" class="rounded-full px-3 py-1.5 text-xs font-semibold transition" :class="conversationFilter === 'unread' ? 'bg-blue-800 text-white' : 'border border-slate-200 text-slate-600 hover:bg-slate-50'" @click="conversationFilter = 'unread'">Unread</button>
                    </div>
                </div>

                <div v-if="!authenticated" class="m-5 rounded-xl border border-blue-100 bg-blue-50 p-5">
                    <h2 class="font-semibold text-blue-950">Sign in to see your inbox</h2>
                    <p class="mt-2 text-sm leading-5 text-blue-900/80">Your conversations with job seekers and employers will appear here.</p>
                    <a href="/login" class="mt-4 inline-flex rounded-lg bg-[var(--brand-primary)] px-4 py-2 text-sm font-semibold text-white hover:bg-[var(--brand-primary-hover)]">Sign in</a>
                </div>
                <div v-else-if="loading" class="p-5 text-sm text-slate-500">Loading conversations…</div>
                <div v-else-if="conversations.length === 0" class="m-5 rounded-xl border border-dashed border-slate-300 p-6 text-center">
                    <i class="ti ti-messages mx-auto text-3xl text-slate-400" aria-hidden="true" />
                    <h2 class="mt-2 font-semibold text-slate-800">No conversations yet</h2>
                    <p class="mt-1 text-sm leading-5 text-slate-500">Messages will appear here when you contact someone about a job application.</p>
                </div>
                <div v-else-if="filteredConversations.length === 0" class="p-6 text-center text-sm text-slate-500">No unread conversations.</div>
                <div v-else class="flex-1 overflow-y-auto">
                    <button v-for="conversation in filteredConversations" :key="conversation.id" type="button" class="flex w-full gap-3 border-b border-slate-100 px-4 py-4 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500" :class="selectedConversationId === conversation.id ? 'bg-blue-50/70' : 'bg-white'" :aria-current="selectedConversationId === conversation.id ? 'true' : undefined" @click="selectConversation(conversation.id)">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-sm font-bold text-blue-900">{{ conversation.other_participant?.name?.slice(0, 1)?.toUpperCase() ?? '?' }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center justify-between gap-2"><span class="truncate text-sm font-semibold text-slate-900">{{ conversation.other_participant?.name ?? 'Conversation' }}</span><time class="shrink-0 text-xs text-slate-400">{{ formatTime(conversation.updated_at) }}</time></span>
                            <span v-if="conversation.job_title" class="mt-1 block truncate text-xs font-medium text-blue-800">{{ conversation.job_title }}</span>
                            <span class="mt-1 flex items-center justify-between gap-2"><span class="truncate text-xs text-slate-500">{{ messagePreview(conversation.last_message) }}</span><span v-if="conversation.unread_count" class="grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-blue-700 px-1.5 text-[11px] font-bold text-white">{{ conversation.unread_count }}</span></span>
                        </span>
                    </button>
                </div>
            </aside>

            <div v-if="selectedConversation" class="flex min-w-0 flex-1 flex-col" :class="selectedConversation ? 'flex' : 'hidden md:flex'">
                <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-4 sm:px-6">
                    <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-slate-600 hover:bg-slate-100 md:hidden" aria-label="Back to conversations" @click="selectedConversationId = null; messages = []"><i class="ti ti-arrow-left" aria-hidden="true" /></button>
                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full bg-blue-100 text-sm font-bold text-blue-900">{{ recipientName.slice(0, 1).toUpperCase() }}</span>
                    <div class="min-w-0 flex-1">
                        <h2 class="truncate font-bold text-slate-900">{{ recipientName }}</h2>
                        <p class="truncate text-xs text-slate-500">{{ selectedConversation.job_title ? `Application: ${selectedConversation.job_title}` : 'Job platform conversation' }}</p>
                    </div>
                    <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-800 sm:inline-flex">Private conversation</span>
                </header>

                <div ref="listElement" class="flex-1 space-y-4 overflow-y-auto bg-slate-50/70 px-4 py-5 sm:px-6" aria-live="polite" aria-relevant="additions text">
                    <div v-if="conversationLoading" class="py-8 text-center text-sm text-slate-500">Loading messages…</div>
                    <div v-else-if="messages.length === 0" class="grid min-h-64 place-items-center">
                        <div class="max-w-sm text-center"><i class="ti ti-message-2 mx-auto text-4xl text-blue-300" aria-hidden="true" /><p class="mt-3 font-semibold text-slate-800">Start the conversation</p><p class="mt-1 text-sm text-slate-500">Keep communication relevant to the application and never share sensitive account information.</p></div>
                    </div>
                    <template v-for="message in messages" :key="message.id">
                        <div class="flex" :class="message.sender_id === currentUserId ? 'justify-end' : 'justify-start'">
                            <article class="max-w-[85%] sm:max-w-[72%]" :class="message.sender_id === currentUserId ? 'items-end' : 'items-start'">
                                <div class="rounded-2xl px-4 py-3 shadow-sm" :class="message.sender_id === currentUserId ? 'rounded-br-md bg-blue-800 text-white' : 'rounded-bl-md border border-slate-200 bg-white text-slate-800'">
                                    <p v-if="message.body" class="whitespace-pre-wrap break-words text-sm leading-6">{{ message.body }}</p>
                                    <ul v-if="message.attachments?.length" class="mt-2 space-y-1.5">
                                        <li v-for="attachment in message.attachments" :key="attachment.id"><a :href="attachment.download_url" class="inline-flex max-w-full items-center gap-2 text-sm underline underline-offset-2" :class="message.sender_id === currentUserId ? 'text-blue-100 hover:text-white' : 'text-blue-800 hover:text-blue-950'"><i class="ti ti-paperclip shrink-0" aria-hidden="true" /><span class="truncate">{{ attachment.name }}</span></a></li>
                                    </ul>
                                </div>
                                <p class="mt-1 px-1 text-[11px] text-slate-400">{{ formatTime(message.created_at, true) }}<span v-if="message.sender_id === currentUserId && message.is_read" class="ml-1 text-blue-600">· Read</span></p>
                            </article>
                        </div>
                    </template>
                </div>

                <div class="border-t border-slate-100 bg-white p-3 sm:p-4">
                    <div v-if="attachments.length" class="mb-3 flex flex-wrap gap-2">
                        <span v-for="(file, index) in attachments" :key="`${file.name}-${index}`" class="inline-flex max-w-full items-center gap-2 rounded-lg bg-blue-50 px-3 py-1.5 text-xs text-blue-900"><i class="ti ti-paperclip" aria-hidden="true" /><span class="max-w-48 truncate">{{ file.name }}</span><button type="button" class="rounded p-0.5 hover:bg-blue-100" :aria-label="`Remove ${file.name}`" @click="removeAttachment(index)"><i class="ti ti-x" aria-hidden="true" /></button></span>
                    </div>
                    <form class="flex items-end gap-2" @submit.prevent="sendMessage">
                        <label class="grid h-11 w-11 shrink-0 cursor-pointer place-items-center rounded-lg text-xl text-slate-500 transition hover:bg-slate-100 hover:text-blue-800" title="Attach files">
                            <i class="ti ti-paperclip" aria-hidden="true" /><span class="sr-only">Attach files</span>
                            <input type="file" class="sr-only" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" @change="handleFiles" />
                        </label>
                        <textarea v-model="draft" rows="1" maxlength="5000" :placeholder="`Message ${recipientName}…`" class="max-h-32 min-h-11 flex-1 resize-y rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-3 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:bg-white focus:ring-4 focus:ring-blue-100" @keydown.enter.exact.prevent="sendMessage" />
                        <button type="submit" :disabled="sending || (!draft.trim() && !attachments.length)" class="inline-flex h-11 shrink-0 items-center gap-2 rounded-lg bg-[var(--brand-primary)] px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-[var(--brand-primary-hover)] disabled:cursor-not-allowed disabled:opacity-50"><i :class="sending ? 'ti ti-loader-2 animate-spin' : 'ti ti-send'" aria-hidden="true" /><span class="hidden sm:inline">{{ sending ? 'Sending' : 'Send' }}</span></button>
                    </form>
                    <p class="mt-2 text-[11px] text-slate-400">Enter to send · Shift+Enter for a new line · Up to 5 files, 10 MB each</p>
                    <p v-if="errorMessage" class="mt-2 text-sm text-red-700" role="alert">{{ errorMessage }}</p>
                    <p v-else-if="successMessage" class="mt-2 text-sm text-emerald-700" role="status">{{ successMessage }}</p>
                </div>
            </div>
            <div v-else class="hidden min-w-0 flex-1 items-center justify-center bg-slate-50/50 p-8 text-center md:flex">
                <div class="max-w-md"><i class="ti ti-messages mx-auto text-5xl text-blue-200" aria-hidden="true" /><h2 class="mt-4 text-lg font-semibold text-slate-800">Choose a conversation</h2><p class="mt-2 text-sm leading-6 text-slate-500">Select a person from your inbox to read and reply to messages.</p><p v-if="errorMessage" class="mt-4 text-sm text-red-700" role="alert">{{ errorMessage }}</p></div>
            </div>
        </div>
    </section>
</template>
