<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    placement: { type: String, required: true },
});
const ads = ref([]);

onMounted(async () => {
    try {
        const response = await window.axios.get('/api/advertisements', { params: { placement: props.placement, limit: 1 } });
        ads.value = response.data.data ?? [];
    } catch {
        ads.value = [];
    }
});
</script>

<template>
    <aside v-if="ads.length" class="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8" aria-label="Advertisement">
        <a v-for="ad in ads" :key="ad.id" :href="ad.destination_url" target="_blank" rel="sponsored noopener noreferrer" class="group relative block overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 shadow-sm transition hover:shadow-md">
            <img :src="ad.image_url" :alt="ad.headline" class="max-h-64 w-full object-cover sm:max-h-72">
            <span class="absolute left-3 top-3 rounded-full bg-slate-950/80 px-3 py-1 text-xs font-bold text-white">{{ $t('Advertisement') }}</span>
            <span class="absolute bottom-3 right-3 max-w-[80%] rounded-lg bg-white/95 px-3 py-2 text-sm font-semibold text-slate-900 shadow">{{ ad.headline }} <span class="font-normal text-slate-600">· {{ ad.advertiser }}</span></span>
        </a>
    </aside>
</template>
