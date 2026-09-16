<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <header class="grid gap-6 border-b border-slate-200 pb-8 lg:grid-cols-[1fr_auto] lg:items-end">
            <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ $t('products.eyebrow') }}</p><h1 class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">{{ $t('products.title') }}</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ $t('products.subtitle') }}</p></div>
            <p class="font-mono text-sm text-slate-500">{{ $t('products.resultCount', { count: meta.total }) }}</p>
        </header>

        <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[1fr_12rem_12rem_auto]" @submit.prevent="applyFilters">
            <label class="relative"><span class="sr-only">{{ $t('products.search') }}</span><span class="pointer-events-none absolute inset-y-0 left-4 flex items-center text-slate-400" aria-hidden="true">⌕</span><input id="product-search" v-model="filters.q" name="q" type="search" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 pl-11 pr-3 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:bg-white focus:ring-2 focus:ring-emerald-100" :placeholder="$t('products.searchPlaceholder')"></label>
            <label><span class="sr-only">{{ $t('products.filter.support') }}</span><select v-model="filters.support" name="support" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">{{ $t('products.filter.allSupport') }}</option><option v-for="status in supportStatuses" :key="status" :value="status">{{ supportLabel(status) }}</option></select></label>
            <label><span class="sr-only">{{ $t('products.filter.security') }}</span><select v-model="filters.security" name="security" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100"><option value="">{{ $t('products.filter.allSecurity') }}</option><option value="clear">{{ $t('products.filter.clear') }}</option><option value="attention">{{ $t('products.filter.attention') }}</option></select></label>
            <button type="submit" class="h-11 rounded-xl bg-slate-950 px-4 text-sm font-semibold text-white transition hover:bg-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-300">{{ $t('products.filter.apply') }}</button>
        </form>

        <div v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800" role="alert">{{ $t('products.error') }} <button type="button" class="ml-2 font-semibold underline" @click="loadProducts(true)">{{ $t('common.retry') }}</button></div>
        <div v-else-if="loading" class="grid gap-4 md:grid-cols-2"><div v-for="index in 4" :key="index" class="h-64 animate-pulse rounded-2xl border border-slate-200 bg-white"></div></div>
        <div v-else-if="products.length === 0" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="text-lg font-semibold text-slate-900">{{ hasFilters ? $t('products.noResults') : $t('products.empty') }}</p><button v-if="hasFilters" type="button" class="mt-4 text-sm font-semibold text-emerald-700 underline" @click="clearFilters">{{ $t('products.filter.clear') }}</button></div>

        <div v-else class="grid gap-4 md:grid-cols-2">
            <article v-for="(product, index) in products" :key="product.id" class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-xl hover:shadow-emerald-950/5 sm:p-7">
                <div class="absolute right-0 top-0 h-24 w-24 rounded-bl-[4rem] bg-emerald-50 transition group-hover:bg-emerald-100"></div>
                <div class="relative flex items-start justify-between gap-4"><span class="font-mono text-xs text-slate-400">{{ String((meta.current_page - 1) * meta.per_page + index + 1).padStart(2, '0') }}</span><StatusBadge :status="product.current_release?.support_status" :label="supportLabel(product.current_release?.support_status)" /></div>
                <h2 class="relative mt-10 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ product.name }}</h2>
                <p class="relative mt-3 min-h-12 text-sm leading-6 text-slate-600">{{ product.description }}</p>
                <div class="relative mt-5 flex items-center justify-between gap-3 rounded-xl bg-slate-50 px-3 py-2 text-xs"><span class="font-semibold text-slate-700">{{ $t('products.recommendation') }}</span><span class="font-semibold text-emerald-800">{{ recommendationLabel(product.recommendation?.code) }}</span></div>
                <dl class="relative mt-5 grid grid-cols-3 gap-3 border-t border-slate-100 pt-5">
                    <div><dt class="text-[0.63rem] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $t('products.current') }}</dt><dd class="mt-2 font-mono text-sm font-semibold text-slate-950">{{ product.current_release?.version ?? '–' }}</dd></div>
                    <div><dt class="text-[0.63rem] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $t('products.released') }}</dt><dd class="mt-2 text-xs font-medium text-slate-700">{{ formatDate(product.current_release?.release_date) }}</dd></div>
                    <div><dt class="text-[0.63rem] font-semibold uppercase tracking-[0.12em] text-slate-500">{{ $t('products.openFindings') }}</dt><dd class="mt-2 font-mono text-sm font-semibold" :class="product.current_release?.open_vulnerabilities ? 'text-rose-700' : 'text-emerald-700'">{{ product.current_release?.open_vulnerabilities ?? 0 }}</dd></div>
                </dl>
                <div class="relative mt-6 flex items-center justify-between"><span class="text-xs text-slate-500">{{ $t('products.releaseCount', { count: product.release_count ?? 0 }) }}</span><RouterLink :to="`/products/${product.id}`" class="text-sm font-bold text-emerald-700 transition group-hover:text-emerald-900">{{ $t('products.view') }} <span aria-hidden="true">↗</span></RouterLink></div>
            </article>
        </div>

        <div v-if="meta.has_more" class="flex justify-center pb-4"><button type="button" class="rounded-full border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-emerald-500 hover:text-emerald-800 disabled:cursor-wait disabled:opacity-60" :disabled="loadingMore" @click="loadProducts(false)">{{ loadingMore ? $t('products.loadingMore') : $t('products.loadMore') }}</button></div>
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
const products = ref([]);
const loading = ref(true);
const loadingMore = ref(false);
const error = ref(false);
const meta = ref({ current_page: 1, per_page: 12, total: 0, has_more: false });
const filters = reactive({
    q: String(route.query.q ?? ''),
    support: String(route.query.support ?? ''),
    security: String(route.query.security ?? ''),
});
const supportStatuses = ['supported', 'maintenance', 'deprecated', 'eol'];
const formatDate = (value) => formatDateValue(value, locale.value);
const hasFilters = computed(() => Object.values(filters).some(Boolean));
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const recommendationLabel = (code) => t(`product.recommendation.${code ?? 'review'}`);

const queryFromFilters = (page = 1) => {
    const params = new URLSearchParams({ page: String(page), per_page: '12', locale: locale.value });

    Object.entries(filters).forEach(([key, value]) => {
        if (value) {
            params.set(key, value);
        }
    });

    return params;
};

const loadProducts = async (reset = true) => {
    if (reset) {
        loading.value = true;
        products.value = [];
        meta.value = { current_page: 1, per_page: 12, total: 0, has_more: false };
    } else {
        loadingMore.value = true;
    }

    error.value = false;

    try {
        const page = reset ? 1 : meta.value.current_page + 1;
        const payload = await fetchJson(`/api/public/products?${queryFromFilters(page)}`);
        products.value = reset ? payload.data ?? [] : [...products.value, ...(payload.data ?? [])];
        meta.value = payload.meta ?? meta.value;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
};

const applyFilters = async () => {
    await router.replace({ query: Object.fromEntries(Object.entries(filters).filter(([, value]) => value)) });
    await loadProducts(true);
};

const clearFilters = async () => {
    filters.q = '';
    filters.support = '';
    filters.security = '';
    await applyFilters();
};

onMounted(() => loadProducts(true));
watch(locale, () => loadProducts(true));
</script>
