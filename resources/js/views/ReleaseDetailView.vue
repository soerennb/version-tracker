<template>
    <section class="mx-auto max-w-5xl space-y-8">
        <RouterLink v-if="release" :to="`/products/${release.product.id}`" class="inline-flex items-center text-sm font-semibold text-slate-500 transition hover:text-emerald-800">← {{ $t('release.back') }}</RouterLink>
        <div v-if="loading" class="space-y-4"><div class="h-12 w-1/2 animate-pulse rounded-xl bg-slate-200"></div><div class="h-5 w-1/3 animate-pulse rounded bg-slate-200"></div><div class="h-48 animate-pulse rounded-2xl bg-white"></div></div>
        <div v-else-if="error || !release" class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-sm text-rose-800" role="alert">{{ $t('release.notFound') }} <button type="button" class="ml-2 font-semibold underline" @click="loadRelease">{{ $t('common.retry') }}</button></div>

        <template v-else>
            <header class="rounded-[1.75rem] bg-slate-950 p-6 text-white shadow-[0_24px_80px_-36px_rgba(15,23,42,0.7)] sm:p-10"><p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-300">{{ release.product.name }}</p><div class="mt-5 flex flex-wrap items-end justify-between gap-5"><h1 class="font-mono text-5xl font-semibold tracking-[-0.07em] sm:text-7xl">{{ release.version }}</h1><StatusBadge :status="release.support_status" :label="supportLabel(release.support_status)" /></div><dl class="mt-8 grid gap-5 border-t border-slate-800 pt-5 text-sm sm:grid-cols-3"><div><dt class="text-xs text-slate-400">{{ $t('release.released') }}</dt><dd class="mt-1 font-semibold text-slate-100">{{ formatDate(release.release_date) }}</dd></div><div><dt class="text-xs text-slate-400">{{ $t('release.eol') }}</dt><dd class="mt-1 font-semibold text-slate-100">{{ formatDate(release.eol_date) }}</dd></div><div><dt class="text-xs text-slate-400">{{ $t('release.lts') }}</dt><dd class="mt-1 font-semibold text-slate-100">{{ formatDate(release.lts_date) }}</dd></div></dl></header>

            <section v-if="release.security.open" class="rounded-2xl border border-rose-200 bg-rose-50 p-5 sm:p-6"><div class="flex flex-wrap items-center justify-between gap-4"><div><p class="text-xs font-semibold uppercase tracking-[0.16em] text-rose-700">{{ $t('release.securityAttention') }}</p><p class="mt-2 text-sm font-semibold text-slate-950">{{ release.security.open }} {{ $t('release.openAdvisories') }}</p></div><span v-if="release.security.has_fix" class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800">{{ $t('release.fixAvailable') }}</span></div></section>

            <section class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $t('release.notes') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ activeNote?.title ?? $t('release.notes') }}</h2></div><div v-if="release.notes.length > 1" class="flex rounded-xl border border-slate-300 p-1" role="group" :aria-label="$t('release.language')"><button v-for="note in release.notes" :key="note.language" :id="`language-${note.language}`" type="button" class="rounded-lg px-3 py-1.5 text-xs font-bold transition" :class="activeLanguage === note.language ? 'bg-slate-950 text-white' : 'text-slate-600 hover:bg-slate-100'" :aria-pressed="activeLanguage === note.language" @click="activeLanguage = note.language">{{ note.language_label }}</button></div></div><p v-if="release.fallback_used" class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-xs text-amber-800">{{ $t('release.languageFallback', { language: release.content_locale }) }}</p><p v-if="activeNote" class="mt-6 max-w-3xl whitespace-pre-line text-base leading-8 text-slate-700">{{ activeNote.content }}</p><p v-else class="mt-6 text-sm text-slate-500">{{ $t('release.noNotes') }}</p></section>

            <section v-if="release.sources?.length" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8"><div class="border-b border-slate-200 pb-5"><p class="text-xs font-semibold uppercase tracking-[0.18em] text-amber-700">{{ $t('release.provenance') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ $t('release.source') }}</h2></div><ul class="mt-5 space-y-3"><li v-for="source in release.sources" :key="`${source.provider}-${source.kind}-${source.tag_name}`" class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-slate-50 px-4 py-3"><div><p class="text-sm font-semibold text-slate-950">{{ source.provider }} · {{ source.kind === 'tag' ? $t('release.sourceTag') : $t('release.sourceRelease') }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ source.tag_name }}</p></div><a v-if="source.source_url" :href="source.source_url" target="_blank" rel="noopener noreferrer" class="text-sm font-bold text-emerald-700 hover:text-emerald-900">{{ $t('release.source') }} ↗</a></li></ul></section>

            <section class="grid gap-5 md:grid-cols-2">
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-center justify-between gap-3"><h2 class="text-xl font-semibold text-slate-950">{{ $t('release.downloads') }}</h2><span class="font-mono text-xs text-slate-400">{{ release.downloads.length }}</span></div><ul v-if="release.downloads.length" class="mt-5 divide-y divide-slate-100 border-y border-slate-100"><li v-for="file in release.downloads" :key="file.id" class="py-4"><div class="flex items-center justify-between gap-3 text-sm"><a :href="file.download_url" class="min-w-0 truncate font-semibold text-emerald-700 hover:text-emerald-900">{{ file.filename }}</a><span class="shrink-0 text-xs text-slate-500">{{ formatSize(file.size) }}</span></div><p v-if="file.platform || file.architecture || file.checksum" class="mt-2 text-xs text-slate-500">{{ [file.platform, file.architecture].filter(Boolean).join(' · ') || $t('release.genericArtifact') }}<span v-if="file.checksum" class="block break-all font-mono text-[0.68rem]">{{ file.checksum_algorithm || 'SHA' }}: {{ file.checksum }}</span></p></li></ul><p v-else class="mt-5 text-sm leading-6 text-slate-500">{{ $t('release.noDownloads') }}</p></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"><div class="flex items-center justify-between gap-3"><h2 class="text-xl font-semibold text-slate-950">{{ $t('release.advisories') }}</h2><span class="font-mono text-xs text-slate-400">{{ release.advisories.length }}</span></div><p v-if="release.advisories.length === 0" class="mt-5 text-sm leading-6 text-slate-500">{{ $t('release.noAdvisories') }}</p><ul v-else class="mt-5 space-y-3"><li v-for="advisory in release.advisories" :key="advisory.id" class="rounded-xl border border-slate-200 p-4"><div class="flex flex-wrap items-center justify-between gap-2"><RouterLink :to="`/security/${advisory.id}`" class="font-mono text-sm font-bold text-slate-950 hover:text-emerald-700">{{ advisory.cve_id }}</RouterLink><StatusBadge :status="advisory.severity" :label="`${severityLabel(advisory.severity)} · ${advisory.cvss_score ?? '–'}`" /></div><p class="mt-3 text-sm leading-6 text-slate-600">{{ advisory.description }}</p><p class="mt-3 text-xs text-slate-500">{{ $t('release.affected') }} {{ advisory.affected_range || release.version }}<span v-if="advisory.fixed_version"> · {{ $t('release.fixedIn') }} {{ advisory.fixed_version }}</span></p></li></ul></div>
            </section>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute } from 'vue-router';
import { fetchJson, formatDate as formatDateValue, formatSize as formatSizeValue } from '../api';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const { locale, t } = useI18n();
const release = ref(null);
const loading = ref(true);
const error = ref(false);
const activeLanguage = ref(locale.value);
const activeNote = computed(() => release.value?.notes.find((note) => note.language === activeLanguage.value) ?? release.value?.notes[0] ?? null);
const formatDate = (value) => formatDateValue(value, locale.value);
const formatSize = (value) => formatSizeValue(value, locale.value);
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const severityLabel = (severity) => t(`security.severity.${severity}`);

const loadRelease = async () => {
    loading.value = true;
    error.value = false;

    try {
        release.value = (await fetchJson(`/api/public/releases/${route.params.id}?locale=${encodeURIComponent(locale.value)}`)).data;
        activeLanguage.value = release.value.notes.some((note) => note.language === locale.value) ? locale.value : release.value.notes[0]?.language;
    } catch {
        release.value = null;
        error.value = true;
    } finally {
        loading.value = false;
    }
};

onMounted(loadRelease);
watch(locale, loadRelease);
</script>
