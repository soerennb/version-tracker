<template>
    <div class="space-y-12">
        <section class="relative isolate overflow-hidden rounded-[2rem] bg-slate-950 px-6 py-8 text-white shadow-[0_24px_80px_-36px_rgba(15,23,42,0.7)] sm:px-10 sm:py-12 lg:px-14">
            <div class="absolute inset-y-0 right-0 -z-10 w-1/2 opacity-70 [background-image:linear-gradient(rgba(148,163,184,0.12)_1px,transparent_1px),linear-gradient(90deg,rgba(148,163,184,0.12)_1px,transparent_1px)] [background-size:32px_32px] [mask-image:linear-gradient(to_left,black,transparent)]"></div>
            <div class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr] lg:items-end">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-emerald-300">{{ $t('home.eyebrow') }}</p>
                    <h1 class="mt-5 max-w-3xl text-4xl font-semibold leading-[0.98] tracking-[-0.045em] text-balance sm:text-6xl">{{ $t('home.title') }}</h1>
                    <p class="mt-6 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">{{ intro }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <RouterLink v-if="runtimeState.features.products" to="/products" class="inline-flex items-center rounded-full bg-emerald-400 px-5 py-3 text-sm font-bold text-slate-950 transition hover:bg-emerald-300 focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-950">{{ $t('home.exploreProducts') }} <span class="ml-2" aria-hidden="true">↗</span></RouterLink>
                        <RouterLink v-if="runtimeState.features.security" to="/security" class="inline-flex items-center rounded-full border border-slate-700 px-5 py-3 text-sm font-semibold text-slate-200 transition hover:border-slate-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-300 focus:ring-offset-2 focus:ring-offset-slate-950">{{ $t('home.securityCenter') }}</RouterLink>
                    </div>
                </div>
                <div class="rounded-2xl border border-slate-800 bg-slate-900/80 p-5 backdrop-blur">
                    <div class="flex items-center justify-between border-b border-slate-800 pb-4 text-[0.68rem] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <span>{{ $t('home.signalBoard') }}</span>
                        <span class="flex items-center gap-2 text-emerald-300"><span class="h-2 w-2 animate-pulse rounded-full bg-emerald-300"></span>{{ $t('home.live') }}</span>
                    </div>
                    <div v-if="loading" class="space-y-4 pt-5" aria-hidden="true">
                        <div v-for="index in 3" :key="index" class="h-3 animate-pulse rounded bg-slate-800"></div>
                    </div>
                    <div v-else class="space-y-4 pt-5">
                        <div v-for="signal in signals" :key="signal.label" class="flex items-center gap-4">
                            <span class="w-24 shrink-0 text-xs text-slate-400">{{ signal.label }}</span>
                            <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-800"><div class="h-full rounded-full" :class="signal.class" :style="{ width: `${Math.min(100, (signal.value / signal.max) * 100)}%` }"></div></div>
                            <span class="w-8 text-right font-mono text-xs text-slate-200">{{ signal.value }}</span>
                        </div>
                    </div>
                    <p class="mt-6 border-t border-slate-800 pt-4 text-xs leading-5 text-slate-400">{{ $t('home.signalCaption') }}</p>
                </div>
            </div>
        </section>

        <p v-if="error" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 text-sm text-rose-800" role="alert">{{ $t('home.loadError') }} <button type="button" class="ml-2 font-semibold underline" @click="loadData">{{ $t('common.retry') }}</button></p>

        <section v-if="runtimeState.features.catalog" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4" :aria-label="$t('home.overview')">
            <article v-for="metric in metrics" :key="metric.label" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">{{ metric.label }}</p>
                <p class="mt-4 font-mono text-3xl font-semibold tracking-[-0.06em] text-slate-950">{{ metric.value }}</p>
                <p class="mt-2 text-sm text-slate-500">{{ metric.detail }}</p>
            </article>
        </section>

        <section v-if="runtimeState.features.catalog" class="grid gap-10 lg:grid-cols-[1.15fr_0.85fr]">
            <div>
                <div class="flex items-end justify-between gap-4 border-b border-slate-200 pb-4">
                    <div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $t('home.catalogEyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950">{{ $t('home.featuredProducts') }}</h2></div>
                    <RouterLink to="/products" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">{{ $t('home.viewAll') }} <span aria-hidden="true">→</span></RouterLink>
                </div>
                <div v-if="loading" class="mt-5 grid gap-4 sm:grid-cols-2" aria-hidden="true"><div v-for="index in 4" :key="index" class="h-48 animate-pulse rounded-2xl bg-white"></div></div>
                <div v-else class="mt-5 grid gap-4 sm:grid-cols-2">
                    <RouterLink v-for="product in products" :key="product.id" :to="`/products/${product.id}`" class="group rounded-2xl border border-slate-200 bg-white p-5 transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg hover:shadow-emerald-950/5">
                        <div class="flex items-start justify-between gap-3"><span class="font-mono text-xs text-slate-600">{{ String(product.id).padStart(2, '0') }}</span><StatusBadge :status="product.current_release?.support_status" :label="supportLabel(product.current_release?.support_status)" /></div>
                        <h3 class="mt-8 text-xl font-semibold tracking-[-0.025em] text-slate-950 group-hover:text-emerald-800">{{ product.name }}</h3>
                        <p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{{ product.description }}</p>
                        <div class="mt-6 flex items-end justify-between border-t border-slate-100 pt-4"><span class="font-mono text-sm font-semibold text-slate-900">{{ product.recommended_release?.version ?? product.current_release?.version ?? '–' }}</span><span class="text-xs text-slate-500">{{ recommendationLabel(product.recommendation?.code) }}</span></div>
                    </RouterLink>
                    <div v-if="products.length === 0" class="rounded-2xl border border-dashed border-slate-300 p-8 text-sm text-slate-500">{{ $t('products.empty') }}</div>
                </div>
            </div>

            <div>
                <div class="border-b border-slate-200 pb-4"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">{{ $t('home.activityEyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950">{{ $t('home.latestReleases') }}</h2></div>
                <ol class="divide-y divide-slate-200">
                    <li v-for="release in releases" :key="release.id" class="py-5"><RouterLink :to="`/releases/${release.id}`" class="group block"><div class="flex items-center justify-between gap-3 text-xs text-slate-600"><span>{{ formatDate(release.release_date) }}</span><span class="font-mono">{{ release.software }}</span></div><div class="mt-2 flex items-baseline gap-3"><span class="font-mono text-lg font-semibold text-slate-950 group-hover:text-emerald-700">{{ release.version }}</span><span class="truncate text-sm text-slate-600">{{ release.headline || $t('product.noNotes') }}</span></div></RouterLink></li>
                    <li v-if="releases.length === 0" class="py-6 text-sm text-slate-500">{{ $t('timeline.empty') }}</li>
                </ol>
            </div>
        </section>

        <section v-if="runtimeState.features.security" class="rounded-2xl border border-rose-200 bg-rose-50/70 p-6 sm:p-8">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-rose-200 pb-5"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">{{ $t('home.securityEyebrow') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.03em] text-slate-950">{{ $t('home.securityTitle') }}</h2></div><RouterLink to="/security" class="text-sm font-semibold text-rose-800 hover:text-rose-950">{{ $t('home.openSecurity') }} <span aria-hidden="true">→</span></RouterLink></div>
            <div class="mt-5 grid gap-4 md:grid-cols-3">
                <RouterLink v-for="advisory in advisories" :key="advisory.id" :to="advisory.links.detail || advisory.links.release" class="rounded-xl border border-rose-200 bg-white/80 p-4 transition hover:border-rose-400"><div class="flex items-center justify-between gap-3"><span class="font-mono text-sm font-semibold text-slate-950">{{ advisory.cve_id }}</span><StatusBadge :status="advisory.severity" :label="severityLabel(advisory.severity)" /></div><p class="mt-3 line-clamp-2 text-sm leading-6 text-slate-600">{{ advisory.description }}</p><p class="mt-4 text-xs text-slate-500">{{ advisory.software.name }} · {{ advisory.version.number }}</p></RouterLink>
                <p v-if="advisories.length === 0" class="text-sm text-slate-600">{{ $t('home.noAdvisories') }}</p>
            </div>
        </section>
    </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink } from 'vue-router';
import { fetchJson, formatDate as formatDateValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';
import { localizedRuntime, runtimeState } from '../runtime';

const { locale, t } = useI18n();
const overview = ref(null);
const loading = ref(true);
const error = ref(false);
const formatDate = (value) => formatDateValue(value, locale.value);
const intro = computed(() => localizedRuntime(runtimeState.application.intro, locale.value) || t('home.subtitle'));
const products = computed(() => overview.value?.products ?? []);
const releases = computed(() => overview.value?.latest_releases ?? []);
const advisories = computed(() => overview.value?.security ?? []);
const metrics = computed(() => {
    const values = overview.value?.metrics ?? {};

    return [
        { label: t('home.metrics.products'), value: values.products ?? 0, detail: t('home.metrics.productsDetail') },
        { label: t('home.metrics.releases'), value: values.releases ?? 0, detail: t('home.metrics.releasesDetail') },
        { label: t('home.metrics.open'), value: values.open_security ?? 0, detail: t('home.metrics.openDetail') },
        { label: t('home.metrics.critical'), value: values.critical_security ?? 0, detail: t('home.metrics.criticalDetail') },
    ];
});
const signals = computed(() => {
    const values = overview.value?.metrics ?? {};

    return [
        { label: t('home.signals.releases'), value: values.releases ?? 0, class: 'bg-emerald-400', max: Math.max(values.releases ?? 0, 12) },
        { label: t('home.signals.lifecycle'), value: products.value.filter((product) => product.current_release?.eol_date || product.current_release?.support_status).length, class: 'bg-amber-300', max: Math.max(products.value.length, 6) },
        { label: t('home.signals.openAlerts'), value: values.open_security ?? 0, class: 'bg-rose-400', max: Math.max(values.open_security ?? 0, 5) },
    ];
});
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const severityLabel = (severity) => t(`security.severity.${severity}`);
const recommendationLabel = (code) => t(`product.recommendation.${code ?? 'review'}`);

const loadData = async () => {
    loading.value = true;
    error.value = false;

    if (! runtimeState.features.catalog) {
        overview.value = null;
        loading.value = false;

        return;
    }

    try {
        overview.value = (await fetchJson(`/api/public/overview?locale=${encodeURIComponent(locale.value)}`)).data;
    } catch {
        overview.value = null;
        error.value = true;
    } finally {
        loading.value = false;
    }
};

watch(locale, loadData);
onMounted(loadData);
</script>
