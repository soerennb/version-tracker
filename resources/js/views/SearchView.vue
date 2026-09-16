<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <header class="border-b border-slate-200 pb-8"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ $t('search.eyebrow') }}</p><h1 class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">{{ $t('search.title') }}</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ $t('search.subtitle') }}</p></header>

        <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch"><label class="sr-only" for="global-search">{{ $t('search.inputLabel') }}</label><input id="global-search" v-model="query" type="search" class="h-12 min-w-0 flex-1 rounded-2xl border border-slate-300 bg-white px-4 text-sm outline-none transition placeholder:text-slate-400 focus:border-emerald-600 focus:ring-4 focus:ring-emerald-100" :placeholder="$t('search.placeholder')"><button type="submit" class="h-12 rounded-2xl bg-slate-950 px-6 text-sm font-semibold text-white transition hover:bg-emerald-800">{{ $t('search.submit') }}</button></form>

        <div v-if="!hasSearch" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="text-lg font-semibold text-slate-900">{{ $t('search.startTitle') }}</p><p class="mt-2 text-sm text-slate-500">{{ $t('search.startText') }}</p></div>
        <p v-else-if="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-sm text-slate-500">{{ $t('search.loading') }}</p>
        <p v-else-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800" role="alert">{{ $t('search.error') }} <button type="button" class="ml-2 font-semibold underline" @click="loadResults">{{ $t('common.retry') }}</button></p>
        <div v-else class="space-y-8">
            <section v-if="results.products?.length" class="space-y-4"><div class="flex items-end justify-between border-b border-slate-200 pb-3"><h2 class="text-2xl font-semibold text-slate-950">{{ $t('search.products') }}</h2><span class="font-mono text-xs text-slate-500">{{ results.products.length }}</span></div><div class="grid gap-4 md:grid-cols-2"><RouterLink v-for="product in results.products" :key="product.id" :to="`/products/${product.id}`" class="rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg"><div class="flex items-center justify-between gap-3"><h3 class="text-lg font-semibold text-slate-950">{{ product.name }}</h3><StatusBadge :status="product.current_release?.support_status" :label="supportLabel(product.current_release?.support_status)" /></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ product.description }}</p><p class="mt-5 font-mono text-sm text-emerald-800">{{ product.current_release?.version || '–' }}</p></RouterLink></div></section>
            <section v-if="results.releases?.length" class="space-y-4"><div class="flex items-end justify-between border-b border-slate-200 pb-3"><h2 class="text-2xl font-semibold text-slate-950">{{ $t('search.releases') }}</h2><span class="font-mono text-xs text-slate-500">{{ results.releases.length }}</span></div><ol class="divide-y divide-slate-200"> <li v-for="release in results.releases" :key="release.id" class="py-4"><RouterLink :to="`/releases/${release.id}`" class="group flex flex-wrap items-center justify-between gap-3"><div><p class="font-semibold text-slate-950 group-hover:text-emerald-800">{{ release.software }} <span class="font-mono">{{ release.version }}</span></p><p class="mt-1 text-sm text-slate-600">{{ release.headline || release.summary }}</p></div><span class="text-xs text-slate-500">{{ formatDate(release.release_date) }} →</span></RouterLink></li></ol></section>
            <section v-if="results.security?.length" class="space-y-4"><div class="flex items-end justify-between border-b border-slate-200 pb-3"><h2 class="text-2xl font-semibold text-slate-950">{{ $t('search.security') }}</h2><span class="font-mono text-xs text-slate-500">{{ results.security.length }}</span></div><ol class="space-y-3"><li v-for="advisory in results.security" :key="advisory.id"><RouterLink :to="advisory.links.detail || advisory.links.release" class="block rounded-2xl border border-slate-200 bg-white p-5 transition hover:border-rose-300 hover:shadow-lg"><div class="flex flex-wrap items-center justify-between gap-3"><span class="font-mono font-semibold text-slate-950">{{ advisory.cve_id }}</span><StatusBadge :status="advisory.severity" :label="severityLabel(advisory.severity)" /></div><p class="mt-2 text-sm leading-6 text-slate-600">{{ advisory.description }}</p><p class="mt-3 text-xs text-slate-500">{{ advisory.software.name }} · {{ advisory.version.number }}</p></RouterLink></li></ol></section>
            <div v-if="!results.products?.length && !results.releases?.length && !results.security?.length" class="rounded-2xl border border-dashed border-slate-300 bg-white p-12 text-center"><p class="text-lg font-semibold text-slate-900">{{ $t('search.noResults') }}</p><p class="mt-2 text-sm text-slate-500">{{ $t('search.noResultsText') }}</p></div>
        </div>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchJson, formatDate as formatDateValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const query = ref(String(route.query.q ?? ''));
const results = ref({});
const loading = ref(false);
const error = ref(false);
const hasSearch = computed(() => query.value.trim().length >= 2);
const formatDate = (value) => formatDateValue(value, locale.value);
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const severityLabel = (severity) => t(`security.severity.${severity}`);

const loadResults = async () => {
    if (!hasSearch.value) {
        results.value = {};
        return;
    }

    loading.value = true;
    error.value = false;

    try {
        results.value = (await fetchJson(`/api/public/search?q=${encodeURIComponent(query.value.trim())}&locale=${encodeURIComponent(locale.value)}`)).data;
    } catch {
        results.value = {};
        error.value = true;
    } finally {
        loading.value = false;
    }
};

const submitSearch = async () => {
    await router.replace({ query: query.value.trim() ? { q: query.value.trim() } : {} });
    await loadResults();
};

watch(() => route.query.q, (value) => {
    query.value = String(value ?? '');
    loadResults();
});
watch(locale, loadResults);
onMounted(loadResults);
</script>
