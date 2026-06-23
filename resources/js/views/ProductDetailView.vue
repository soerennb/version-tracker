<template>
    <section class="mx-auto max-w-6xl">
        <RouterLink to="/products" class="text-sm font-medium text-slate-500 hover:text-slate-900">← {{ $t('product.back') }}</RouterLink>
        <p v-if="loading" class="py-10 text-sm text-slate-500">{{ $t('product.loading') }}</p>
        <p v-else-if="!product" class="py-10 text-sm text-red-700">{{ $t('product.notFound') }}</p>

        <template v-else>
            <header class="mt-5 border-b border-slate-200 pb-7">
                <h1 class="text-4xl font-semibold text-slate-950">{{ product.name }}</h1>
                <p v-if="product.description" class="mt-3 max-w-3xl text-base leading-7 text-slate-600">{{ product.description }}</p>
            </header>

            <dl class="grid border-b border-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                <div class="border-b border-slate-200 py-5 sm:border-r lg:border-b-0"><dt class="text-xs font-medium uppercase text-slate-500">{{ $t('product.current') }}</dt><dd class="mt-2 font-mono text-xl font-semibold">{{ product.current_release.version }}</dd></div>
                <div class="border-b border-slate-200 py-5 sm:pl-6 lg:border-b-0 lg:border-r"><dt class="text-xs font-medium uppercase text-slate-500">{{ $t('product.support') }}</dt><dd class="mt-2 text-sm font-semibold">{{ supportLabel(product.current_release.support_status) }}</dd></div>
                <div class="border-b border-slate-200 py-5 sm:border-b-0 lg:border-r lg:pl-6"><dt class="text-xs font-medium uppercase text-slate-500">{{ $t('product.eol') }}</dt><dd class="mt-2 text-sm font-semibold">{{ formatDate(product.current_release.eol_date) }}</dd></div>
                <div class="py-5 sm:pl-6"><dt class="text-xs font-medium uppercase text-slate-500">{{ $t('product.security') }}</dt><dd class="mt-2 text-sm font-semibold" :class="product.security.status === 'clear' ? 'text-emerald-700' : 'text-red-700'">{{ $t(`product.${product.security.status}`) }}</dd><dd class="mt-1 text-xs text-slate-500">{{ product.security.open }} {{ $t('product.open') }} · {{ product.security.critical }} {{ $t('product.critical') }} · {{ product.security.high }} {{ $t('product.high') }}</dd></div>
            </dl>

            <section class="pt-9">
                <div class="flex items-center justify-between gap-4"><h2 class="text-xl font-semibold text-slate-950">{{ $t('product.releases') }}</h2><RouterLink v-if="product.releases.length > 1" :to="`/products/${product.id}/compare?left=${product.releases[1].id}&right=${product.releases[0].id}`" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">{{ $t('product.compare') }} →</RouterLink></div>
                <ol class="mt-4 divide-y divide-slate-200 border-t border-slate-200">
                    <li v-for="release in product.releases" :key="release.id" class="grid gap-5 py-6 lg:grid-cols-[10rem_1fr_16rem]">
                        <div><RouterLink :to="`/releases/${release.id}`" class="font-mono text-lg font-semibold text-slate-950 hover:text-emerald-700">{{ release.version }}</RouterLink><div class="mt-1 text-xs text-slate-500">{{ formatDate(release.release_date) }}</div><div class="mt-2 text-xs font-medium text-slate-700">{{ supportLabel(release.support_status) }}</div></div>
                        <div><h3 v-if="release.headline" class="font-semibold text-slate-900">{{ release.headline }}</h3><p class="mt-1 text-sm leading-6 text-slate-600">{{ release.summary || $t('product.noNotes') }}</p><dl class="mt-3 flex gap-5 text-xs text-slate-500"><div v-if="release.lts_date">{{ $t('product.lts') }} {{ formatDate(release.lts_date) }}</div><div v-if="release.eol_date">{{ $t('product.eol') }} {{ formatDate(release.eol_date) }}</div></dl></div>
                        <div v-if="release.downloads.length"><h3 class="text-xs font-medium uppercase text-slate-500">{{ $t('product.downloads') }}</h3><ul class="mt-2 space-y-2"><li v-for="file in release.downloads" :key="file.id" class="text-sm"><a :href="file.download_url" class="block truncate font-medium text-emerald-700 hover:text-emerald-900">{{ file.filename }}</a><span class="text-xs text-slate-500">{{ formatSize(file.size) }}</span></li></ul></div>
                    </li>
                </ol>
            </section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

const route = useRoute();
const { locale, t } = useI18n();
const product = ref(null);
const loading = ref(true);
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '–';
const formatSize = (bytes) => new Intl.NumberFormat(locale.value, { style: 'unit', unit: bytes >= 1_000_000 ? 'megabyte' : 'kilobyte', unitDisplay: 'short', maximumFractionDigits: 1 }).format(bytes / (bytes >= 1_000_000 ? 1_000_000 : 1_000));
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);

onMounted(async () => {
    try {
        const response = await fetch(`/api/public/products/${route.params.id}`);
        if (response.ok) {
            product.value = (await response.json()).data;
        }
    } finally {
        loading.value = false;
    }
});
</script>
