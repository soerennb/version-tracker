<template>
    <section class="mx-auto max-w-6xl">
        <RouterLink :to="`/products/${route.params.productId}`" class="text-sm font-medium text-slate-500 hover:text-slate-900">← {{ $t('compare.back') }}</RouterLink>
        <header class="mt-5 border-b border-slate-200 pb-6"><p v-if="product" class="text-sm font-medium text-emerald-700">{{ product.name }}</p><h1 class="mt-1 text-3xl font-semibold text-slate-950">{{ $t('compare.title') }}</h1></header>

        <form class="grid gap-4 border-b border-slate-200 py-5 sm:grid-cols-2" @submit.prevent>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('compare.left') }}</span><select v-model="leftId" class="mt-1 h-10 w-full border border-slate-300 bg-white px-3 text-sm" @change="updateComparison"><option v-for="release in releases" :key="release.id" :value="String(release.id)">{{ release.version }} · {{ formatDate(release.release_date) }}</option></select></label>
            <label><span class="block text-xs font-medium text-slate-600">{{ $t('compare.right') }}</span><select v-model="rightId" class="mt-1 h-10 w-full border border-slate-300 bg-white px-3 text-sm" @change="updateComparison"><option v-for="release in releases" :key="release.id" :value="String(release.id)">{{ release.version }} · {{ formatDate(release.release_date) }}</option></select></label>
        </form>

        <p v-if="loading" class="py-8 text-sm text-slate-500">{{ $t('compare.loading') }}</p>
        <p v-else-if="!comparison" class="py-8 text-sm text-red-700">{{ $t('compare.invalid') }}</p>

        <template v-else>
            <div class="grid grid-cols-2 border-b border-slate-200">
                <div v-for="side in ['left', 'right']" :key="side" class="min-w-0 py-5 first:border-r first:pr-4 last:pl-4 sm:first:pr-6 sm:last:pl-6"><div class="font-mono text-xl font-semibold text-slate-950">{{ comparison[side].version }}</div><div class="mt-1 text-xs text-slate-500">{{ formatDate(comparison[side].release_date) }} · {{ supportLabel(comparison[side].support_status) }}</div></div>
            </div>

            <CompareSection :title="$t('compare.releaseNotes')" :left="noteFor(comparison.left)" :right="noteFor(comparison.right)">
                <template #default="{ value }"><template v-if="value"><h3 class="text-sm font-semibold text-slate-900">{{ value.title }}</h3><p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ value.content }}</p></template><p v-else class="text-sm text-slate-500">{{ $t('compare.noNotes') }}</p></template>
            </CompareSection>

            <CompareSection :title="$t('compare.security')" :left="comparison.left.advisories" :right="comparison.right.advisories">
                <template #default="{ value }"><ul v-if="value.length" class="space-y-2"><li v-for="advisory in value" :key="advisory.cve_id" class="text-sm"><span class="font-mono font-semibold">{{ advisory.cve_id }}</span><span class="ml-2 text-xs uppercase text-red-700">{{ advisory.severity }} · {{ advisory.cvss_score }}</span></li></ul><p v-else class="text-sm text-slate-500">{{ $t('compare.noItems') }}</p></template>
            </CompareSection>

            <CompareSection :title="$t('compare.attachments')" :left="comparison.left.attachments" :right="comparison.right.attachments">
                <template #default="{ value }"><ul v-if="value.length" class="space-y-2"><li v-for="file in value" :key="file.filename" class="truncate text-sm font-medium text-slate-800">{{ file.filename }}</li></ul><p v-else class="text-sm text-slate-500">{{ $t('compare.noItems') }}</p></template>
            </CompareSection>

            <section class="border-b border-slate-200 py-7"><h2 class="text-lg font-semibold text-slate-950">{{ $t('compare.dependencies') }}</h2><p v-if="comparison.dependency_changes.length === 0" class="mt-4 text-sm text-slate-500">{{ $t('compare.noItems') }}</p><ol v-else class="mt-4 divide-y divide-slate-200 border-y border-slate-200"><li v-for="(change, index) in comparison.dependency_changes" :key="index" class="grid gap-2 py-4 sm:grid-cols-[8rem_1fr]"><span class="text-xs font-semibold uppercase" :class="change.status === 'unchanged' ? 'text-slate-500' : 'text-emerald-700'">{{ $t(`compare.${change.status}`) }}</span><div class="text-sm text-slate-700"><span class="font-semibold">{{ (change.after || change.before).software }}</span><span class="ml-2">{{ constraint(change.before) }}<template v-if="change.status === 'changed'"> → {{ constraint(change.after) }}</template></span></div></li></ol></section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute, useRouter } from 'vue-router';
import CompareSection from '../components/CompareSection.vue';

const route = useRoute();
const router = useRouter();
const { locale, t } = useI18n();
const product = ref(null);
const releases = ref([]);
const comparison = ref(null);
const loading = ref(true);
const leftId = ref(String(route.query.left ?? ''));
const rightId = ref(String(route.query.right ?? ''));
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '–';
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);
const noteFor = (version) => version.notes.find((note) => note.language === locale.value) ?? version.notes[0] ?? null;
const constraint = (dependency) => dependency ? `${dependency.type}: ${dependency.min_version ?? '–'} – ${dependency.max_version ?? '∞'}` : '';

const loadComparison = async () => {
    if (!leftId.value || !rightId.value || leftId.value === rightId.value) { comparison.value = null; loading.value = false; return; }
    loading.value = true;
    try {
        const response = await fetch(`/api/public/compare?left=${leftId.value}&right=${rightId.value}`);
        comparison.value = response.ok ? (await response.json()).data : null;
    } finally { loading.value = false; }
};
const updateComparison = async () => {
    await router.replace({ query: { left: leftId.value, right: rightId.value } });
    await loadComparison();
};

onMounted(async () => {
    const response = await fetch(`/api/public/products/${route.params.productId}`);
    if (response.ok) {
        product.value = (await response.json()).data;
        releases.value = product.value.releases;
        leftId.value ||= String(releases.value[1]?.id ?? releases.value[0]?.id ?? '');
        rightId.value ||= String(releases.value[0]?.id ?? '');
        await updateComparison();
    } else { loading.value = false; }
});
</script>
