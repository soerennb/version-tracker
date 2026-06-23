<template>
    <section class="mx-auto max-w-5xl">
        <RouterLink v-if="release" :to="`/products/${release.product.id}`" class="text-sm font-medium text-slate-500 hover:text-slate-900">← {{ $t('release.back') }}</RouterLink>
        <p v-if="loading" class="py-10 text-sm text-slate-500">{{ $t('release.loading') }}</p>
        <p v-else-if="!release" class="py-10 text-sm text-red-700">{{ $t('release.notFound') }}</p>

        <template v-else>
            <header class="mt-5 border-b border-slate-200 pb-7">
                <p class="text-sm font-medium text-emerald-700">{{ release.product.name }}</p>
                <h1 class="mt-2 font-mono text-4xl font-semibold text-slate-950">{{ release.version }}</h1>
                <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    <div><dt class="text-xs text-slate-500">{{ $t('release.released') }}</dt><dd class="mt-1 font-medium">{{ formatDate(release.release_date) }}</dd></div>
                    <div><dt class="text-xs text-slate-500">{{ $t('release.support') }}</dt><dd class="mt-1 font-medium">{{ supportLabel(release.support_status) }}</dd></div>
                    <div v-if="release.lts_date"><dt class="text-xs text-slate-500">{{ $t('release.lts') }}</dt><dd class="mt-1 font-medium">{{ formatDate(release.lts_date) }}</dd></div>
                    <div v-if="release.eol_date"><dt class="text-xs text-slate-500">{{ $t('release.eol') }}</dt><dd class="mt-1 font-medium">{{ formatDate(release.eol_date) }}</dd></div>
                </dl>
            </header>

            <section class="py-8">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <h2 class="text-xl font-semibold text-slate-950">{{ $t('release.notes') }}</h2>
                    <div v-if="release.notes.length > 1" class="flex border border-slate-300" role="group" :aria-label="$t('release.language')">
                        <button v-for="note in release.notes" :key="note.language" type="button" class="px-3 py-1.5 text-xs font-semibold" :class="activeLanguage === note.language ? 'bg-slate-900 text-white' : 'bg-white text-slate-600 hover:bg-slate-50'" @click="activeLanguage = note.language">{{ note.language_label }}</button>
                    </div>
                </div>
                <article v-if="activeNote" class="mt-5 max-w-3xl">
                    <h3 class="text-lg font-semibold text-slate-900">{{ activeNote.title }}</h3>
                    <div class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ activeNote.content }}</div>
                </article>
            </section>

            <section v-if="release.downloads.length" class="border-t border-slate-200 py-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ $t('release.downloads') }}</h2>
                <ul class="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                    <li v-for="file in release.downloads" :key="file.id" class="flex flex-wrap items-center justify-between gap-2 py-3 text-sm"><a :href="file.download_url" class="font-medium text-emerald-700 hover:text-emerald-900">{{ file.filename }}</a><span class="text-xs text-slate-500">{{ formatSize(file.size) }}</span></li>
                </ul>
            </section>

            <section class="border-t border-slate-200 py-8">
                <h2 class="text-xl font-semibold text-slate-950">{{ $t('release.advisories') }}</h2>
                <p v-if="release.advisories.length === 0" class="mt-4 text-sm text-slate-500">{{ $t('release.noAdvisories') }}</p>
                <ol v-else class="mt-4 divide-y divide-slate-200 border-y border-slate-200">
                    <li v-for="advisory in release.advisories" :key="advisory.id" class="grid gap-3 py-5 sm:grid-cols-[9rem_1fr]">
                        <div><a v-if="advisory.source_url" :href="advisory.source_url" target="_blank" rel="noopener noreferrer" class="font-mono text-sm font-semibold text-emerald-700 hover:text-emerald-900">{{ advisory.cve_id }}</a><span v-else class="font-mono text-sm font-semibold">{{ advisory.cve_id }}</span><div class="mt-1 text-xs font-semibold uppercase" :class="severityClass(advisory.severity)">{{ advisory.severity }} · {{ $t('release.cvss') }} {{ advisory.cvss_score }}</div></div>
                        <div><p class="text-sm leading-6 text-slate-700">{{ advisory.description }}</p><p class="mt-2 text-xs text-slate-500">{{ $t('release.affected') }} {{ advisory.affected_range || release.version }}<span v-if="advisory.fixed_version"> · {{ $t('release.fixedIn') }} {{ advisory.fixed_version }}</span></p></div>
                    </li>
                </ol>
            </section>
        </template>
    </section>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

const route = useRoute();
const { locale, t } = useI18n();
const release = ref(null);
const loading = ref(true);
const activeLanguage = ref(locale.value);
const activeNote = computed(() => release.value?.notes.find((note) => note.language === activeLanguage.value) ?? release.value?.notes[0] ?? null);
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '–';
const formatSize = (bytes) => new Intl.NumberFormat(locale.value, { style: 'unit', unit: bytes >= 1_000_000 ? 'megabyte' : 'kilobyte', unitDisplay: 'short', maximumFractionDigits: 1 }).format(bytes / (bytes >= 1_000_000 ? 1_000_000 : 1_000));
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const severityClass = (severity) => severity === 'critical' ? 'text-red-700' : severity === 'high' ? 'text-orange-700' : 'text-slate-600';

onMounted(async () => {
    try {
        const response = await fetch(`/api/public/releases/${route.params.id}`);
        if (response.ok) {
            release.value = (await response.json()).data;
            activeLanguage.value = release.value.notes.some((note) => note.language === locale.value) ? locale.value : release.value.notes[0]?.language;
        }
    } finally {
        loading.value = false;
    }
});
</script>
