<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <RouterLink :to="`/products/${route.params.productId}`" class="inline-flex items-center text-sm font-semibold text-slate-500 transition hover:text-emerald-800">← {{ $t('compare.back') }}</RouterLink>
        <header class="border-b border-slate-200 pb-8"><p v-if="product" class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-700">{{ product.name }}</p><h1 class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-slate-950 sm:text-5xl">{{ $t('compare.title') }}</h1><p class="mt-4 max-w-2xl text-base leading-7 text-slate-600">{{ $t('compare.releaseNotes') }} · {{ $t('compare.security') }} · {{ $t('compare.dependencies') }}</p></header>

        <form class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:grid-cols-2" @submit.prevent>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('compare.left') }}</span><select id="compare-left" v-model="leftId" name="left" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 font-mono text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="updateComparison"><option v-for="release in releases" :key="release.id" :value="String(release.id)">{{ release.version }} · {{ formatDate(release.release_date) }}</option></select></label>
            <label><span class="mb-1.5 block text-xs font-semibold uppercase tracking-[0.1em] text-slate-600">{{ $t('compare.right') }}</span><select id="compare-right" v-model="rightId" name="right" class="h-11 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 font-mono text-sm outline-none focus:border-emerald-600 focus:ring-2 focus:ring-emerald-100" @change="updateComparison"><option v-for="release in releases" :key="release.id" :value="String(release.id)">{{ release.version }} · {{ formatDate(release.release_date) }}</option></select></label>
        </form>

        <p v-if="loading" class="rounded-2xl border border-slate-200 bg-white p-8 text-sm text-slate-500">{{ $t('compare.loading') }}</p>
        <p v-else-if="error || !comparison" class="rounded-2xl border border-rose-200 bg-rose-50 p-8 text-sm text-rose-800">{{ $t('compare.invalid') }} <button type="button" class="ml-2 font-semibold underline" @click="loadComparison">{{ $t('common.retry') }}</button></p>

        <template v-else>
            <div class="grid grid-cols-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"><div v-for="side in ['left', 'right']" :key="side" class="min-w-0 p-5 sm:p-7" :class="side === 'left' ? 'border-r border-slate-200' : ''"><p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">{{ side === 'left' ? $t('compare.left') : $t('compare.right') }}</p><div class="mt-3 font-mono text-2xl font-semibold text-slate-950 sm:text-3xl">{{ comparison[side].version }}</div><p class="mt-2 text-xs text-slate-500">{{ formatDate(comparison[side].release_date) }} · {{ supportLabel(comparison[side].support_status) }}</p></div></div>
            <CompareSection :title="$t('compare.releaseNotes')" :left="noteFor(comparison.left)" :right="noteFor(comparison.right)"><template #default="{ value }"><template v-if="value"><h3 class="text-sm font-semibold text-slate-900">{{ value.title }}</h3><p class="mt-2 whitespace-pre-line text-sm leading-7 text-slate-600">{{ value.content }}</p></template><p v-else class="text-sm text-slate-500">{{ $t('compare.noNotes') }}</p></template></CompareSection>
            <CompareSection :title="$t('compare.security')" :left="comparison.left.advisories" :right="comparison.right.advisories"><template #default="{ value }"><ul v-if="value.length" class="space-y-3"><li v-for="advisory in value" :key="advisory.cve_id" class="flex flex-wrap items-center justify-between gap-2 text-sm"><span class="font-mono font-semibold text-slate-900">{{ advisory.cve_id }}</span><StatusBadge :status="advisory.severity" :label="`${severityLabel(advisory.severity)} · ${advisory.cvss_score ?? '–'}`" /></li></ul><p v-else class="text-sm text-slate-500">{{ $t('compare.noItems') }}</p></template></CompareSection>
            <CompareSection :title="$t('compare.attachments')" :left="comparison.left.attachments" :right="comparison.right.attachments"><template #default="{ value }"><ul v-if="value.length" class="space-y-2"><li v-for="file in value" :key="file.filename" class="flex items-center justify-between gap-2 text-sm"><span class="truncate font-medium text-slate-800">{{ file.filename }}</span><span class="shrink-0 text-xs text-slate-400">{{ formatSize(file.size) }}</span></li></ul><p v-else class="text-sm text-slate-500">{{ $t('compare.noItems') }}</p></template></CompareSection>
            <section class="border-b border-slate-200 py-7"><h2 class="text-lg font-semibold text-slate-950">{{ $t('compare.dependencies') }}</h2><p v-if="comparison.dependency_changes.length === 0" class="mt-4 text-sm text-slate-500">{{ $t('compare.noItems') }}</p><ol v-else class="mt-4 divide-y divide-slate-200 border-y border-slate-200"><li v-for="(change, index) in comparison.dependency_changes" :key="index" class="grid gap-2 py-4 sm:grid-cols-[8rem_1fr]"><span class="text-xs font-semibold uppercase" :class="change.status === 'unchanged' ? 'text-slate-500' : 'text-emerald-700'">{{ $t(`compare.${change.status}`) }}</span><div class="text-sm text-slate-700"><span class="font-semibold">{{ (change.after || change.before).software }}</span><span class="ml-2">{{ constraint(change.before) }}<template v-if="change.status === 'changed'"> → {{ constraint(change.after) }}</template></span></div></li></ol></section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { fetchJson, formatDate as formatDateValue, formatSize as formatSizeValue } from '../api';
import CompareSection from '../components/CompareSection.vue';
import StatusBadge from '../components/StatusBadge.vue';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const product = ref(null);
const releases = ref([]);
const comparison = ref(null);
const loading = ref(true);
const error = ref(false);
const leftId = ref(String(route.query.left ?? ''));
const rightId = ref(String(route.query.right ?? ''));
const formatDate = (value) => formatDateValue(value, locale.value);
const formatSize = (value) => formatSizeValue(value, locale.value);
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const severityLabel = (severity) => t(`security.severity.${severity}`);
const noteFor = (version) => version.notes.find((note) => note.language === locale.value) ?? version.notes[0] ?? null;
const constraint = (dependency) => dependency ? `${dependency.type}: ${dependency.min_version ?? '–'} – ${dependency.max_version ?? '∞'}` : '';

const loadComparison = async () => {
    if (!leftId.value || !rightId.value || leftId.value === rightId.value) {
        comparison.value = null;
        loading.value = false;
        return;
    }
    loading.value = true;
    error.value = false;

    try {
        comparison.value = (await fetchJson(`/api/public/compare?left=${leftId.value}&right=${rightId.value}`)).data;
    } catch {
        comparison.value = null;
        error.value = true;
    } finally {
        loading.value = false;
    }
};
const updateComparison = async () => {
    await router.replace({ query: { left: leftId.value, right: rightId.value } });
    await loadComparison();
};

onMounted(async () => {
    try {
        product.value = (await fetchJson(`/api/public/products/${route.params.productId}`)).data;
        releases.value = product.value.releases;
        leftId.value ||= String(releases.value[1]?.id ?? releases.value[0]?.id ?? '');
        rightId.value ||= String(releases.value[0]?.id ?? '');
        await updateComparison();
    } catch {
        error.value = true;
        loading.value = false;
    }
});
</script>
