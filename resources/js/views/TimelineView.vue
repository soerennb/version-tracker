<template>
    <section class="mx-auto max-w-6xl">
        <header class="border-b border-slate-200 pb-6">
            <h1 class="text-3xl font-semibold text-slate-950">{{ $t('timeline.title') }}</h1>
        </header>

        <form class="grid gap-4 border-b border-slate-200 py-5 md:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
            <label class="md:col-span-2 lg:col-span-2"><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.search') }}</span><input v-model="filters.q" type="search" class="mt-1 h-10 w-full border border-slate-300 bg-white px-3 text-sm outline-none focus:border-emerald-600" :placeholder="$t('timeline.filter.searchPlaceholder')" @input="scheduleFilters"></label>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.product') }}</span><select v-model="filters.software" class="mt-1 h-10 w-full border border-slate-300 bg-white px-2 text-sm" @change="applyFilters"><option value="">{{ $t('timeline.filter.all') }}</option><option v-for="software in softwareOptions" :key="software.id" :value="String(software.id)">{{ software.name }}</option></select></label>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.support') }}</span><select v-model="filters.support" class="mt-1 h-10 w-full border border-slate-300 bg-white px-2 text-sm" @change="applyFilters"><option value="">{{ $t('timeline.filter.any') }}</option><option v-for="status in supportStatuses" :key="status" :value="status">{{ $t(`product.supportStatus.${status}`) }}</option></select></label>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.security') }}</span><select v-model="filters.security" class="mt-1 h-10 w-full border border-slate-300 bg-white px-2 text-sm" @change="applyFilters"><option value="">{{ $t('timeline.filter.any') }}</option><option value="clear">{{ $t('product.clear') }}</option><option value="attention">{{ $t('product.attention') }}</option></select></label>
            <button v-if="hasFilters" type="button" class="self-end text-left text-sm font-semibold text-slate-600 hover:text-slate-950 lg:text-center" @click="clearFilters">{{ $t('timeline.filter.clear') }}</button>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.from') }}</span><input v-model="filters.date_from" type="date" class="mt-1 h-10 w-full border border-slate-300 bg-white px-2 text-sm" @change="applyFilters"></label>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('timeline.filter.to') }}</span><input v-model="filters.date_to" type="date" class="mt-1 h-10 w-full border border-slate-300 bg-white px-2 text-sm" @change="applyFilters"></label>
        </form>

        <p v-if="loading" class="py-8 text-sm text-slate-500">{{ $t('timeline.loading') }}</p>
        <p v-else-if="timeline.length === 0" class="py-8 text-sm text-slate-500">{{ $t('timeline.empty') }}</p>

        <ol v-else class="divide-y divide-slate-200">
            <li v-for="item in timeline" :key="item.id" class="grid gap-3 py-6 md:grid-cols-[9rem_1fr_10rem]">
                <div><div class="text-xs text-slate-500">{{ formatDate(item.release_date) }}</div><div class="mt-1 text-sm font-semibold text-slate-800">{{ item.software }}</div></div>
                <div><RouterLink :to="`/releases/${item.id}`" class="font-mono text-lg font-semibold text-slate-950 hover:text-emerald-700">{{ item.version }}</RouterLink><h2 v-if="item.headline" class="mt-1 text-sm font-semibold text-slate-800">{{ item.headline }}</h2><p v-if="item.summary" class="mt-1 text-sm leading-6 text-slate-600">{{ item.summary }}</p></div>
                <div class="text-xs md:text-right"><div class="font-medium text-slate-700">{{ supportLabel(item.support_status) }}</div><div class="mt-1" :class="item.open_vulnerabilities ? 'text-red-700' : 'text-emerald-700'">{{ item.open_vulnerabilities }} {{ $t('products.openFindings').toLowerCase() }}</div></div>
            </li>
        </ol>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const timeline = ref([]);
const loading = ref(true);
const softwareOptions = ref([]);
const supportStatuses = ['supported', 'maintenance', 'deprecated', 'eol'];
const filterKeys = ['q', 'software', 'date_from', 'date_to', 'support', 'security'];
const filters = reactive(Object.fromEntries(filterKeys.map((key) => [key, String(route.query[key] ?? '')])));
const hasFilters = computed(() => filterKeys.some((key) => filters[key]));
let debounceTimer;

const queryFromFilters = () => Object.fromEntries(filterKeys.filter((key) => filters[key]).map((key) => [key, filters[key]]));
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '–';
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);

const loadTimeline = async () => {
    loading.value = true;
    try {
        const params = new URLSearchParams(queryFromFilters());
        const response = await fetch(`/api/public/timeline?${params}`);
        const payload = await response.json();
        timeline.value = response.ok ? payload.data ?? [] : [];
        softwareOptions.value = payload.filters?.software ?? softwareOptions.value;
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    clearTimeout(debounceTimer);
    await router.replace({ query: queryFromFilters() });
    await loadTimeline();
};
const scheduleFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
};
const clearFilters = () => {
    filterKeys.forEach((key) => { filters[key] = ''; });
    applyFilters();
};

onMounted(loadTimeline);
onBeforeUnmount(() => clearTimeout(debounceTimer));
</script>
