<template>
    <section class="mx-auto max-w-2xl rounded-[2rem] border border-slate-200 bg-white p-8 text-center shadow-sm sm:p-14"><p class="font-mono text-sm font-semibold text-emerald-700">404</p><h1 class="mt-4 text-4xl font-semibold tracking-[-0.05em] text-slate-950">{{ $t('notFound.title') }}</h1><p class="mt-4 text-base leading-7 text-slate-600">{{ $t('notFound.text') }}</p><RouterLink to="/" class="mt-8 inline-flex rounded-full bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-800">{{ $t('notFound.home') }}</RouterLink></section>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
