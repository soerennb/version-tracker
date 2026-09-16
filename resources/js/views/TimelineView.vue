<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <header class="border-b border-slate-200 pb-8"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">{{ $t('timeline.eyebrow') }}</p><div class="mt-3 flex flex-wrap items-end justify-between gap-4"><div><h1 class="text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">{{ $t('timeline.title') }}</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ $t('timeline.subtitle') }}</p></div><p class="font-mono text-sm text-slate-500">{{ $t('timeline.resultCount', { count: meta.total }) }}</p></div></header>

        <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-2 lg:grid-cols-6" @submit.prevent="applyFilters">
            <label class="md:col-span-2 lg:col-span-2"><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.search') }}</span><input id="timeline-search" v-model="filters.q" name="q" type="search" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" :placeholder="$t('timeline.filter.searchPlaceholder')" @input="scheduleFilters"></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.product') }}</span><select id="timeline-product" v-model="filters.software" name="software" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="applyFilters"><option value="">{{ $t('timeline.filter.all') }}</option><option v-for="software in softwareOptions" :key="software.id" :value="String(software.id)">{{ software.name }}</option></select></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.support') }}</span><select id="timeline-support" v-model="filters.support" name="support" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="applyFilters"><option value="">{{ $t('timeline.filter.any') }}</option><option v-for="status in supportStatuses" :key="status" :value="status">{{ supportLabel(status) }}</option></select></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.security') }}</span><select id="timeline-security" v-model="filters.security" name="security" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="applyFilters"><option value="">{{ $t('timeline.filter.any') }}</option><option value="clear">{{ $t('product.clear') }}</option><option value="attention">{{ $t('product.attention') }}</option></select></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.from') }}</span><input id="timeline-date-from" v-model="filters.date_from" name="date_from" type="date" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="applyFilters"></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('timeline.filter.to') }}</span><input id="timeline-date-to" v-model="filters.date_to" name="date_to" type="date" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-2 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="applyFilters"></label>
            <button v-if="hasFilters" type="button" class="rounded-xl px-3 py-2 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 lg:col-span-2 lg:text-center" @click="clearFilters">{{ $t('timeline.filter.clear') }}</button>
        </form>

        <p v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800" role="alert">{{ $t('timeline.error') }} <button type="button" class="ml-2 font-semibold underline" @click="loadTimeline(true)">{{ $t('common.retry') }}</button></p>
        <p v-else-if="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-sm text-slate-500">{{ $t('timeline.loading') }}</p>
        <div v-else-if="timeline.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="text-lg font-semibold text-slate-900">{{ $t('timeline.empty') }}</p><p class="mt-2 text-sm text-slate-500">{{ $t('timeline.subtitle') }}</p></div>

        <ol v-else class="relative ml-3 border-l border-slate-300 pl-7 sm:ml-6 sm:pl-10" aria-live="polite">
            <li v-for="item in timeline" :key="item.id" class="relative pb-8 last:pb-2"><span class="absolute -left-[2.05rem] top-1.5 h-3 w-3 rounded-full border-4 border-[#f3f5f1] bg-emerald-500 ring-1 ring-emerald-300 sm:-left-[2.55rem]" aria-hidden="true"></span><article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:border-emerald-200 hover:shadow-md sm:p-6"><div class="flex flex-wrap items-center justify-between gap-3 text-xs text-slate-500"><span>{{ formatDate(item.release_date) }}</span><span class="font-semibold text-slate-700">{{ item.software }}</span></div><div class="mt-4 flex flex-wrap items-center gap-3"><RouterLink :to="`/releases/${item.id}`" class="font-mono text-xl font-semibold text-slate-950 hover:text-emerald-700">{{ item.version }}</RouterLink><StatusBadge :status="item.support_status" :label="supportLabel(item.support_status)" /><span class="text-xs font-medium" :class="item.open_vulnerabilities ? 'text-rose-700' : 'text-emerald-700'">{{ item.open_vulnerabilities }} {{ $t('products.openFindings') }}</span></div><h2 v-if="item.headline" class="mt-3 text-sm font-semibold text-slate-800">{{ item.headline }}</h2><p v-if="item.summary" class="mt-1 max-w-3xl text-sm leading-6 text-slate-600">{{ item.summary }}</p><RouterLink :to="`/releases/${item.id}`" class="mt-5 inline-block text-xs font-bold uppercase tracking-[0.12em] text-emerald-700 hover:text-emerald-900">{{ $t('timeline.cta') }} <span aria-hidden="true">↗</span></RouterLink></article></li>
        </ol>

        <p v-if="loadMoreError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-center text-sm text-rose-800" role="alert">{{ $t('timeline.loadMoreError') }} <button type="button" class="ml-2 font-semibold underline" @click="loadMore">{{ $t('common.retry') }}</button></p>
        <div v-if="meta.has_more" class="flex justify-center"><button type="button" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-800 disabled:cursor-wait disabled:opacity-60" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? $t('timeline.loadingMore') : $t('timeline.loadMore') }}</button></div>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchJson, formatDate as formatDateValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const timeline = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const loadMoreError = ref(false);
const error = ref(false);
const softwareOptions = ref([]);
const meta = ref({ current_page: 1, last_page: 1, total: 0, has_more: false });
const supportStatuses = ['supported', 'maintenance', 'deprecated', 'eol'];
const filterKeys = ['q', 'software', 'date_from', 'date_to', 'support', 'security'];
const filters = reactive(Object.fromEntries(filterKeys.map((key) => [key, String(route.query[key] ?? '')])));
const hasFilters = computed(() => filterKeys.some((key) => filters[key]));
let debounceTimer;

const formatDate = (value) => formatDateValue(value, locale.value);
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const queryFromFilters = (page = 1) => {
    const params = new URLSearchParams({ page: String(page), per_page: '12', locale: locale.value });
    filterKeys.forEach((key) => { if (filters[key]) params.set(key, filters[key]); });
    return params;
};

const loadTimeline = async (reset = false) => {
    if (reset) {
        loading.value = true;
        timeline.value = [];
        meta.value = { current_page: 1, last_page: 1, total: 0, has_more: false };
        loadMoreError.value = false;
    }
    error.value = false;

    try {
        const payload = await fetchJson(`/api/public/timeline?${queryFromFilters(reset ? 1 : meta.value.current_page)}`);
        timeline.value = reset ? payload.data ?? [] : [...timeline.value, ...(payload.data ?? [])];
        softwareOptions.value = payload.filters?.software ?? softwareOptions.value;
        meta.value = payload.meta ?? meta.value;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    clearTimeout(debounceTimer);
    await router.replace({ query: Object.fromEntries(filterKeys.filter((key) => filters[key]).map((key) => [key, filters[key]])) });
    await loadTimeline(true);
};
const scheduleFilters = () => {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilters, 300);
};
const clearFilters = () => {
    filterKeys.forEach((key) => { filters[key] = ''; });
    applyFilters();
};
const loadMore = async () => {
    loadingMore.value = true;
    loadMoreError.value = false;
    try {
        const payload = await fetchJson(`/api/public/timeline?${queryFromFilters(meta.value.current_page + 1)}`);
        timeline.value = [...timeline.value, ...(payload.data ?? [])];
        meta.value = payload.meta ?? meta.value;
    } catch {
        loadMoreError.value = true;
    } finally {
        loadingMore.value = false;
    }
};

onMounted(() => loadTimeline(true));
watch(locale, () => loadTimeline(true));
onBeforeUnmount(() => clearTimeout(debounceTimer));
</script>
