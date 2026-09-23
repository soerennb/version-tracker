<template>
    <section v-if="composition" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-6 py-5 sm:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">{{ $t('release.compositionEyebrow') }}</p>
                <h2 class="mt-2 text-2xl font-semibold tracking-[-0.035em] text-slate-950">{{ $t('release.composition') }}</h2>
            </div>
            <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="composition.ted.status === 'not_accepted' ? 'bg-rose-100 text-rose-800' : composition.ted.status === 'accepted' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600'">
                {{ $t(`release.ted.${composition.ted.status}`) }}
            </span>
        </div>
        <dl class="grid sm:grid-cols-3">
            <div v-for="item in primary" :key="item.label" class="border-b border-slate-100 px-6 py-5 sm:border-r sm:px-8 last:sm:border-r-0">
                <dt class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">{{ item.label }}</dt>
                <dd class="mt-2 font-mono text-lg font-semibold text-slate-950">{{ item.value }}</dd>
            </div>
        </dl>
        <div class="px-6 py-5 sm:px-8">
            <h3 class="text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">{{ $t('release.supportedInterfaces') }}</h3>
            <ul class="mt-3 flex flex-wrap gap-2">
                <li v-for="item in composition.supported_interfaces" :key="item.id" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                    {{ item.name }} <span class="ml-1 font-mono font-semibold text-slate-950">{{ item.version }}</span>
                </li>
            </ul>
            <p v-if="composition.ted.checked_at" class="mt-4 text-xs text-slate-500">{{ $t('release.tedChecked') }}: {{ new Date(composition.ted.checked_at).toLocaleString() }}</p>
        </div>
    </section>
</template>

<script setup>
import { computed } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({ composition: { type: Object, default: null } });
const { t } = useI18n();
const primary = computed(() => [
    { label: t('release.baseline'), value: props.composition.baseline.version },
    { label: t('release.eformsComponent'), value: props.composition.eforms_component.version },
    { label: t('release.activeEformsSdk'), value: props.composition.active_eforms_sdk.version },
]);
</script>
