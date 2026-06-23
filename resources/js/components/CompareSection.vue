<template>
    <section class="border-b border-slate-200 py-7">
        <h2 class="text-lg font-semibold text-slate-950">{{ title }}</h2>
        <div class="mt-4 grid grid-cols-2">
            <div class="min-w-0 border-r border-slate-200 pr-4 sm:pr-6"><slot :value="left" /></div>
            <div class="min-w-0 pl-4 sm:pl-6"><slot :value="right" /></div>
        </div>
    </section>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    left: { type: [Array, Object], default: null },
    right: { type: [Array, Object], default: null },
});
</script>
