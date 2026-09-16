<template>
    <section class="mx-auto max-w-5xl space-y-8">
        <RouterLink to="/security" class="inline-flex items-center text-sm font-semibold text-slate-500 transition hover:text-emerald-800">← {{ $t('security.back') }}</RouterLink>

        <div v-if="loading" class="space-y-4"><div class="h-12 w-2/3 animate-pulse rounded-xl bg-slate-200"></div><div class="h-48 animate-pulse rounded-2xl bg-white"></div></div>
        <div v-else-if="error || !advisory" class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-sm text-rose-800" role="alert">{{ $t('security.detailNotFound') }} <button type="button" class="ml-2 font-semibold underline" @click="loadAdvisory">{{ $t('common.retry') }}</button></div>

        <template v-else>
            <header class="rounded-[1.75rem] bg-slate-950 p-6 text-white shadow-[0_24px_80px_-36px_rgba(15,23,42,0.7)] sm:p-10"><div class="flex flex-wrap items-start justify-between gap-5"><div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-300">{{ $t('security.detailEyebrow') }}</p><h1 class="mt-4 font-mono text-4xl font-semibold tracking-[-0.06em] sm:text-6xl">{{ advisory.cve_id }}</h1><p class="mt-3 text-slate-300">{{ advisory.software.name }} · {{ advisory.version.number }}</p></div><div class="flex flex-wrap gap-2"><StatusBadge :status="advisory.severity" :label="advisory.severity_label || severityLabel(advisory.severity)" /><StatusBadge :status="advisory.status" :label="advisory.status_label || statusLabel(advisory.status)" /></div></div><dl class="mt-8 grid gap-5 border-t border-slate-800 pt-5 text-sm sm:grid-cols-3"><div><dt class="text-xs text-slate-400">{{ $t('security.published') }}</dt><dd class="mt-1 font-semibold text-slate-100">{{ formatDate(advisory.published_date) }}</dd></div><div><dt class="text-xs text-slate-400">{{ $t('security.cvss') }}</dt><dd class="mt-1 font-mono font-semibold text-slate-100">{{ advisory.cvss_score ?? '–' }}</dd></div><div><dt class="text-xs text-slate-400">{{ $t('security.exploitability') }}</dt><dd class="mt-1 font-semibold text-slate-100">{{ advisory.exploitability_label || advisory.exploitability || '–' }}</dd></div></dl></header>

            <section class="grid gap-5 lg:grid-cols-[1fr_0.8fr]"><article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-rose-700">{{ $t('security.description') }}</p><p class="mt-5 whitespace-pre-line text-base leading-8 text-slate-700">{{ advisory.description }}</p><dl class="mt-8 grid gap-4 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2"><div><dt class="text-xs text-slate-500">{{ $t('security.affectedRange') }}</dt><dd class="mt-1 font-mono text-slate-950">{{ advisory.affected_range || $t('security.allVersions') }}</dd></div><div><dt class="text-xs text-slate-500">{{ $t('security.source') }}</dt><dd class="mt-1"> <a v-if="advisory.source_url" :href="advisory.source_url" target="_blank" rel="noopener noreferrer" class="font-semibold text-emerald-700 hover:text-emerald-900">{{ advisory.source || $t('security.source') }} ↗</a><span v-else class="text-slate-700">{{ advisory.source || '–' }}</span></dd></div></dl></article><aside v-if="advisory.remediation" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-6 sm:p-8"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-800">{{ $t('security.remediation') }}</p><h2 class="mt-3 text-2xl font-semibold text-slate-950">{{ $t('security.fixedIn') }} {{ advisory.remediation.fixed_version }}</h2><p class="mt-3 text-sm leading-6 text-slate-700">{{ $t('security.remediationText') }}</p><RouterLink :to="advisory.remediation.release_url" class="mt-6 inline-flex rounded-full bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">{{ $t('security.openFix') }} →</RouterLink></aside></section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $t('security.affectedReleases') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ advisory.affected_releases.length }}</h2></div><RouterLink :to="advisory.links.product" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">{{ $t('security.openProduct') }} →</RouterLink></div><ul class="mt-5 divide-y divide-slate-100"><li v-for="release in advisory.affected_releases" :key="`${release.product.id}-${release.id}`" class="flex flex-wrap items-center justify-between gap-3 py-4"><div><p class="font-semibold text-slate-950">{{ release.product.name }} <span class="font-mono">{{ release.version }}</span></p><p class="mt-1 text-xs text-slate-500">{{ formatDate(release.release_date) }}</p></div><RouterLink :to="release.release_url" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">{{ $t('security.viewRelease') }} →</RouterLink></li></ul></section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { fetchJson, formatDate as formatDateValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const { locale, t } = useI18n();
const advisory = ref(null);
const loading = ref(true);
const error = ref(false);
const formatDate = (value) => formatDateValue(value, locale.value);
const severityLabel = (severity) => t(`security.severity.${severity}`);
const statusLabel = (status) => t(`security.status.${status}`);

const loadAdvisory = async () => {
    loading.value = true;
    error.value = false;

    try {
        advisory.value = (await fetchJson(`/api/public/security/${route.params.id}?locale=${encodeURIComponent(locale.value)}`)).data;
    } catch {
        advisory.value = null;
        error.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(loadAdvisory);
watch(locale, loadAdvisory);
</script>
