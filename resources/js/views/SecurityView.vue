<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <header class="grid gap-6 border-b border-slate-200 pb-8 lg:grid-cols-[1fr_auto] lg:items-end">
            <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-700">{{ $t('security.eyebrow') }}</p><h1 class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">{{ $t('security.title') }}</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ $t('security.subtitle') }}</p></div>
            <div class="grid grid-cols-3 gap-2 sm:gap-3"><div v-for="metric in metrics" :key="metric.label" class="min-w-[5.5rem] rounded-2xl border border-slate-200 bg-white p-3 text-center shadow-sm"><p class="font-mono text-2xl font-semibold" :class="metric.class">{{ metric.value }}</p><p class="mt-1 text-[0.62rem] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ metric.label }}</p></div></div>
        </header>

        <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_12rem_12rem_14rem_auto]" @submit.prevent="applyFilters">
            <label><span class="sr-only">{{ $t('security.filter.search') }}</span><input id="security-search" v-model="filters.q" name="q" type="search" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" :placeholder="$t('security.filter.searchPlaceholder')"></label>
            <label><span class="sr-only">{{ $t('security.filter.severity') }}</span><select id="security-severity" v-model="filters.severity" name="severity" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">{{ $t('security.filter.allSeverities') }}</option><option v-for="severity in severities" :key="severity" :value="severity">{{ severityLabel(severity) }}</option></select></label>
            <label><span class="sr-only">{{ $t('security.filter.status') }}</span><select id="security-status" v-model="filters.status" name="status" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">{{ $t('security.filter.allStatuses') }}</option><option v-for="status in statuses" :key="status" :value="status">{{ statusLabel(status) }}</option></select></label>
            <label><span class="sr-only">{{ $t('security.filter.product') }}</span><select id="security-product" v-model="filters.software" name="software" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100"><option value="">{{ $t('security.filter.allProducts') }}</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option></select></label>
            <button type="submit" class="h-11 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300">{{ $t('security.filter.apply') }}</button>
        </form>

        <p v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800" role="alert">{{ $t('security.error') }} <button type="button" class="ml-2 font-semibold underline" @click="loadAdvisories(true)">{{ $t('common.retry') }}</button></p>
        <p v-else-if="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-sm text-slate-500">{{ $t('security.loading') }}</p>
        <div v-else-if="advisories.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center"><p class="text-lg font-semibold text-slate-900">{{ $t('security.emptyTitle') }}</p><p class="mt-2 text-sm text-slate-500">{{ $t('security.empty') }}</p></div>

        <ol v-else class="space-y-4" aria-live="polite">
            <li v-for="advisory in advisories" :key="advisory.id" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-slate-300 hover:shadow-md">
                <article class="grid gap-5 border-l-4 p-5 sm:p-6 lg:grid-cols-[10rem_1fr_auto]" :class="severityBorder(advisory.severity)">
                    <div><RouterLink :to="advisory.links.detail || advisory.links.release" class="font-mono text-sm font-bold text-slate-950 hover:text-emerald-700">{{ advisory.cve_id }}</RouterLink><p class="mt-2 text-xs text-slate-500">{{ formatDate(advisory.published_date) }}</p></div>
                    <div><div class="flex flex-wrap items-center gap-2"><RouterLink :to="advisory.links.product" class="text-sm font-semibold text-emerald-800 hover:text-emerald-950">{{ advisory.software.name }}</RouterLink><span class="font-mono text-xs text-slate-400">{{ advisory.version.number }}</span><StatusBadge :status="advisory.severity" :label="severityLabel(advisory.severity)" /><StatusBadge :status="advisory.status" :label="statusLabel(advisory.status)" /></div><p class="mt-3 max-w-3xl text-sm leading-6 text-slate-700">{{ advisory.description }}</p><p class="mt-3 text-xs text-slate-500">{{ advisory.affected_range || $t('security.allVersions') }}<span v-if="advisory.fixed_version"> · {{ $t('security.fixedIn') }} {{ advisory.fixed_version.number }}</span></p></div>
                    <div class="text-left lg:text-right"><p class="font-mono text-xl font-semibold text-slate-950">{{ advisory.cvss_score ?? '–' }}</p><p class="mt-1 text-[0.62rem] font-semibold uppercase tracking-[0.12em] text-slate-500">CVSS</p><RouterLink :to="advisory.links.release" class="mt-4 inline-block text-xs font-semibold text-slate-600 hover:text-emerald-700">{{ $t('security.viewRelease') }} →</RouterLink></div>
                </article>
            </li>
        </ol>

        <p v-if="loadMoreError" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-center text-sm text-rose-800" role="alert">{{ $t('security.loadMoreError') }} <button type="button" class="ml-2 font-semibold underline" @click="loadMore">{{ $t('common.retry') }}</button></p>
        <div v-if="meta.has_more" class="flex justify-center pb-4"><button type="button" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-800 disabled:cursor-wait disabled:opacity-60" :disabled="loadingMore" @click="loadMore">{{ loadingMore ? $t('security.loadingMore') : $t('security.loadMore') }}</button></div>
    </section>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchJson, formatDate as formatDateValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const advisories = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const loadMoreError = ref(false);
const error = ref(false);
const meta = ref({ current_page: 1, has_more: false, total: 0 });
const summary = ref({ total: 0, open: 0, critical: 0, high: 0 });
const products = ref([]);
const severities = ['critical', 'high', 'medium', 'low'];
const statuses = ['open', 'fixed', 'accepted'];
const filters = reactive({
    q: String(route.query.q ?? ''),
    severity: String(route.query.severity ?? ''),
    status: String(route.query.status ?? ''),
    software: String(route.query.software ?? ''),
});
const formatDate = (value) => formatDateValue(value, locale.value);

const metrics = computed(() => [
    { label: t('security.metrics.total'), value: summary.value.total, class: 'text-slate-950' },
    { label: t('security.metrics.open'), value: summary.value.open, class: 'text-rose-700' },
    { label: t('security.metrics.critical'), value: summary.value.critical, class: 'text-red-800' },
]);
const queryFromFilters = (page = 1) => {
    const params = new URLSearchParams({ page: String(page), per_page: '12', locale: locale.value });
    Object.entries(filters).forEach(([key, value]) => { if (value) params.set(key, value); });
    return params;
};
const severityLabel = (severity) => t(`security.severity.${severity}`);
const statusLabel = (status) => t(`security.status.${status}`);
const severityBorder = (severity) => ({ critical: 'border-l-red-700', high: 'border-l-orange-500', medium: 'border-l-amber-400', low: 'border-l-slate-400' }[severity] ?? 'border-l-slate-300');

const loadAdvisories = async (reset = false) => {
    if (reset) {
        loading.value = true;
        advisories.value = [];
        meta.value = { current_page: 1, has_more: false, total: 0 };
        loadMoreError.value = false;
    }
    error.value = false;

    try {
        const payload = await fetchJson(`/api/public/security?${queryFromFilters(reset ? 1 : meta.value.current_page)}`);
        advisories.value = reset ? payload.data ?? [] : [...advisories.value, ...(payload.data ?? [])];
        meta.value = payload.meta ?? meta.value;
        summary.value = payload.summary ?? summary.value;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const applyFilters = async () => {
    await router.replace({ query: Object.fromEntries(Object.entries(filters).filter(([, value]) => value)) });
    await loadAdvisories(true);
};
const loadMore = async () => {
    loadingMore.value = true;
    loadMoreError.value = false;
    try {
        const payload = await fetchJson(`/api/public/security?${queryFromFilters(meta.value.current_page + 1)}`);
        advisories.value = [...advisories.value, ...(payload.data ?? [])];
        meta.value = payload.meta ?? meta.value;
    } catch {
        loadMoreError.value = true;
    } finally {
        loadingMore.value = false;
    }
};

const loadProducts = async () => {
    try {
        products.value = (await fetchJson('/api/public/products?per_page=60')).data ?? [];
    } catch {
        products.value = [];
    }
};

onMounted(() => {
    loadAdvisories(true);
    loadProducts();
});
watch(locale, () => {
    loadAdvisories(true);
    loadProducts();
});
</script>
