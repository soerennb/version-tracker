<template>
    <span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[0.68rem] font-semibold uppercase tracking-[0.12em]" :class="toneClass">
        <span class="h-1.5 w-1.5 rounded-full" :class="dotClass" aria-hidden="true"></span>
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: { type: String, default: 'unknown' },
    label: { type: String, required: true },
});

const tone = computed(() => ({
    supported: 'positive',
    clear: 'positive',
    fixed: 'positive',
    maintenance: 'warning',
    deprecated: 'warning',
    attention: 'danger',
    critical: 'danger',
    high: 'danger',
    open: 'danger',
    eol: 'muted',
    accepted: 'muted',
    unknown: 'muted',
}[props.status] ?? 'muted'));

const toneClass = computed(() => ({
    positive: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    danger: 'border-rose-200 bg-rose-50 text-rose-800',
    muted: 'border-slate-200 bg-slate-100 text-slate-600',
}[tone.value]));

const dotClass = computed(() => ({
    positive: 'bg-emerald-500',
    warning: 'bg-amber-500',
    danger: 'bg-rose-500',
    muted: 'bg-slate-400',
}[tone.value]));
</script>
