<template>
    <section class="mx-auto max-w-xl">
        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-6 shadow-sm sm:p-10">
            <div class="border-b border-slate-200 pb-6">
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-700">{{ runtimeState.application.name }} / {{ $t('account.title') }}</p>
                <h1 class="mt-4 text-3xl font-semibold tracking-[-0.05em] text-slate-950 sm:text-4xl"><slot name="title" /></h1>
                <p class="mt-3 text-sm leading-6 text-slate-600"><slot name="description" /></p>
            </div>
            <div class="pt-6">
                <slot />
            </div>
        </div>
    </section>
</template>

<script setup>
import { runtimeState } from '../runtime';
</script>
