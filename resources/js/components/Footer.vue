<template>
    <footer class="border-t border-slate-200 bg-white">
        <div class="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-8 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
            <div><p class="font-semibold text-slate-800">{{ runtimeState.application.name }}</p><p class="mt-1 text-xs">{{ localizedRuntime(runtimeState.application.footer, locale) }}</p></div>
            <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs font-semibold"><RouterLink v-if="runtimeState.features.products" to="/products" class="hover:text-emerald-700">{{ $t('navigation.products') }}</RouterLink><RouterLink v-if="runtimeState.features.timeline" to="/timeline" class="hover:text-emerald-700">{{ $t('navigation.timeline') }}</RouterLink><RouterLink v-if="runtimeState.features.security" to="/security" class="hover:text-emerald-700">{{ $t('navigation.security') }}</RouterLink><a v-if="runtimeState.application.support_url" :href="runtimeState.application.support_url" class="hover:text-emerald-700" target="_blank" rel="noreferrer">{{ $t('footer.support') }}</a></div>
        </div>
    </footer>
</template>

<script setup>
import { RouterLink } from 'vue-router';
import { useI18n } from 'vue-i18n';
import { localizedRuntime, runtimeState } from '../runtime';

const { locale } = useI18n();
</script>
