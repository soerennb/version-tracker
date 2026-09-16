<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <RouterLink to="/products" class="inline-flex items-center text-sm font-semibold text-slate-500 transition hover:text-emerald-800">← {{ $t('product.back') }}</RouterLink>
        <div v-if="loading" class="space-y-4"><div class="h-12 w-2/3 animate-pulse rounded-xl bg-slate-200"></div><div class="h-5 w-1/2 animate-pulse rounded bg-slate-200"></div><div class="h-32 animate-pulse rounded-2xl bg-white"></div></div>
        <div v-else-if="error || !product" class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-sm text-rose-800" role="alert">{{ $t('product.notFound') }} <button type="button" class="ml-2 font-semibold underline" @click="loadProduct">{{ $t('common.retry') }}</button></div>

        <template v-else>
            <header class="relative overflow-hidden rounded-[1.75rem] bg-white p-6 shadow-sm ring-1 ring-slate-200 sm:p-10">
                <div class="absolute right-0 top-0 h-40 w-40 rounded-bl-[7rem] bg-emerald-50"></div>
                <div class="relative"><div class="flex flex-wrap items-center gap-3"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ $t('product.title') }}</p><StatusBadge :status="product.current_release?.support_status" :label="supportLabel(product.current_release?.support_status)" /></div><h1 class="mt-4 text-4xl font-semibold tracking-[-0.05em] text-slate-950 sm:text-6xl">{{ product.name }}</h1><p v-if="product.description" class="mt-5 max-w-3xl text-base leading-7 text-slate-600">{{ product.description }}</p></div>
            </header>

            <section v-if="product.recommended_release" class="grid gap-5 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-5 sm:grid-cols-[1fr_auto] sm:items-center sm:p-6">
                <div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-800">{{ $t('product.recommendationTitle') }}</p><div class="mt-2 flex flex-wrap items-baseline gap-3"><RouterLink :to="`/releases/${product.recommended_release.id}`" class="font-mono text-2xl font-semibold text-slate-950 hover:text-emerald-800">{{ product.recommended_release.version }}</RouterLink><StatusBadge :status="product.recommended_release.support_status" :label="supportLabel(product.recommended_release.support_status)" /></div><p class="mt-2 text-sm leading-6 text-slate-700">{{ recommendationLabel(product.recommendation?.code) }}</p></div>
                <RouterLink :to="`/releases/${product.recommended_release.id}`" class="inline-flex items-center justify-center rounded-full bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800">{{ $t('product.openRecommendation') }} →</RouterLink>
            </section>

            <dl class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm sm:grid-cols-2 lg:grid-cols-4">
                <div class="border-b border-slate-200 p-5 sm:border-r lg:border-b-0"><dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $t('product.current') }}</dt><dd class="mt-3 font-mono text-2xl font-semibold text-slate-950">{{ product.current_release?.version ?? '–' }}</dd><dd class="mt-1 text-xs text-slate-500">{{ formatDate(product.current_release?.release_date) }}</dd></div>
                <div class="border-b border-slate-200 p-5 lg:border-b-0 lg:border-r"><dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $t('product.support') }}</dt><dd class="mt-3 text-sm font-semibold text-slate-950">{{ supportLabel(product.current_release?.support_status) }}</dd><dd class="mt-1 text-xs text-slate-500">{{ $t('product.eol') }}: {{ formatDate(product.current_release?.eol_date) }}</dd></div>
                <div class="border-b border-slate-200 p-5 sm:border-r sm:border-b-0 lg:border-r"><dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $t('product.releaseCount') }}</dt><dd class="mt-3 font-mono text-2xl font-semibold text-slate-950">{{ product.releases.length }}</dd><dd class="mt-1 text-xs text-slate-500">{{ product.license_type ?? $t('common.notAvailable') }}</dd></div>
                <div class="p-5"><dt class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $t('product.security') }}</dt><dd class="mt-3"><StatusBadge :status="product.security.status" :label="$t(`product.${product.security.status}`)" /></dd><dd class="mt-2 text-xs text-slate-500">{{ product.security.open }} {{ $t('product.open') }} · {{ product.security.critical }} {{ $t('product.critical') }} · {{ product.security.high }} {{ $t('product.high') }}</dd><dd class="mt-1 text-[0.68rem] text-slate-400">{{ $t('product.securityScope') }}</dd></div>
            </dl>

            <section v-if="product.security.status !== 'clear'" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 sm:p-6"><div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-700">{{ $t('product.security') }}</p><p class="mt-2 text-sm font-semibold text-slate-950">{{ product.security.open }} {{ $t('product.open') }} · {{ product.security.critical }} {{ $t('product.critical') }}</p></div><RouterLink :to="{ path: '/security', query: { software: product.id } }" class="text-sm font-semibold text-rose-800 hover:text-rose-950">{{ $t('home.openSecurity') }} →</RouterLink></div></section>

            <section v-if="product.dependencies.length" class="rounded-2xl border border-slate-200 bg-slate-950 p-6 text-white sm:p-8"><div class="flex items-end justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-emerald-300">{{ $t('product.dependencies') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em]">{{ product.dependencies.length }} {{ product.dependencies.length === 1 ? $t('product.dependency') : $t('product.dependencies') }}</h2></div><span class="font-mono text-xs text-slate-400">runtime graph</span></div><ul class="mt-6 grid gap-3 sm:grid-cols-2"><li v-for="dependency in product.dependencies" :key="dependency.id" class="rounded-xl border border-slate-800 bg-slate-900 px-4 py-3"><div class="flex items-center justify-between gap-3"><span class="text-sm font-semibold">{{ dependency.name }}</span><span class="text-xs uppercase tracking-[0.12em] text-slate-400">{{ dependency.type }}</span></div><p v-if="dependency.minimum_version || dependency.maximum_version" class="mt-2 text-xs text-slate-400">{{ dependency.minimum_version ?? '*' }} – {{ dependency.maximum_version ?? '*' }}</p></li></ul></section>

            <section>
                <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-4"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">{{ $t('home.activityEyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ $t('product.releases') }}</h2></div><RouterLink v-if="product.releases.length > 1" :to="`/products/${product.id}/compare?left=${product.releases[1].id}&right=${product.releases[0].id}`" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">{{ $t('product.compare') }} →</RouterLink></div>
                <ol class="divide-y divide-slate-200">
                    <li v-for="release in product.releases" :key="release.id" class="grid gap-5 py-7 lg:grid-cols-[10rem_1fr_auto] lg:items-start"><div><RouterLink :to="`/releases/${release.id}`" class="font-mono text-xl font-semibold text-slate-950 hover:text-emerald-700">{{ release.version }}</RouterLink><p class="mt-1 text-xs text-slate-500">{{ formatDate(release.release_date) }}</p><StatusBadge class="mt-3" :status="release.support_status" :label="supportLabel(release.support_status)" /></div><div><h3 v-if="release.headline" class="font-semibold text-slate-900">{{ release.headline }}</h3><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ release.summary || $t('product.noNotes') }}</p><p v-if="release.fallback_used" class="mt-2 text-xs text-amber-700">{{ $t('product.languageFallback', { language: release.content_locale }) }}</p><dl class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-slate-500"><div v-if="release.lts_date">{{ $t('product.lts') }} {{ formatDate(release.lts_date) }}</div><div v-if="release.eol_date">{{ $t('product.eol') }} {{ formatDate(release.eol_date) }}</div><div v-if="release.open_vulnerabilities">{{ release.open_vulnerabilities }} {{ $t('product.open') }}</div></dl></div><div class="lg:text-right"><p class="text-[0.65rem] font-semibold uppercase tracking-[0.14em] text-slate-500">{{ $t('product.downloads') }}</p><ul v-if="release.downloads.length" class="mt-2 space-y-2"><li v-for="file in release.downloads" :key="file.id"><a :href="file.download_url" class="inline-flex max-w-full items-center gap-2 text-sm font-semibold text-emerald-700 hover:text-emerald-900"><span class="truncate">{{ file.filename }}</span><span class="shrink-0 text-xs text-slate-400">{{ formatSize(file.size) }}</span></a></li></ul><p v-else class="mt-2 text-xs text-slate-500">{{ $t('product.downloadPending') }}</p></div></li>
                </ol>
            </section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { fetchJson, formatDate as formatDateValue, formatSize as formatSizeValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const { locale, t } = useI18n();
const product = ref(null);
const loading = ref(true);
const error = ref(false);
const formatDate = (value) => formatDateValue(value, locale.value);
const formatSize = (value) => formatSizeValue(value, locale.value);
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const recommendationLabel = (code) => t(`product.recommendation.${code ?? 'review'}`);

const loadProduct = async () => {
    loading.value = true;
    error.value = false;

    try {
        product.value = (await fetchJson(`/api/public/products/${route.params.id}?locale=${encodeURIComponent(locale.value)}`)).data;
    } catch {
        product.value = null;
        error.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(loadProduct);
watch(locale, loadProduct);
</script>
