<template>
    <section class="mx-auto max-w-6xl">
        <header class="border-b border-slate-200 pb-6">
            <h1 class="text-3xl font-semibold text-slate-950">{{ $t('products.title') }}</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">{{ $t('products.subtitle') }}</p>
        </header>

        <p v-if="loading" class="py-10 text-sm text-slate-500">{{ $t('products.loading') }}</p>
        <p v-else-if="products.length === 0" class="py-10 text-sm text-slate-500">{{ $t('products.empty') }}</p>

        <div v-else class="divide-y divide-slate-200">
            <article v-for="product in products" :key="product.id" class="grid gap-5 py-6 md:grid-cols-[1fr_auto] md:items-center">
                <div>
                    <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1">
                        <h2 class="text-lg font-semibold text-slate-950">{{ product.name }}</h2>
                        <span class="text-xs font-medium uppercase text-slate-500">{{ supportLabel(product.current_release?.support_status) }}</span>
                    </div>
                    <p v-if="product.description" class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{{ product.description }}</p>
                    <dl class="mt-4 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                        <div><dt class="text-xs text-slate-500">{{ $t('products.current') }}</dt><dd class="mt-1 font-mono font-semibold text-slate-900">{{ product.current_release?.version }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ $t('products.released') }}</dt><dd class="mt-1 text-slate-800">{{ formatDate(product.current_release?.release_date) }}</dd></div>
                        <div><dt class="text-xs text-slate-500">{{ $t('products.openFindings') }}</dt><dd class="mt-1 font-semibold" :class="product.current_release?.open_vulnerabilities ? 'text-red-700' : 'text-emerald-700'">{{ product.current_release?.open_vulnerabilities ?? 0 }}</dd></div>
                    </dl>
                </div>
                <RouterLink :to="`/products/${product.id}`" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">
                    {{ $t('products.view') }} →
                </RouterLink>
            </article>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale, t } = useI18n();
const products = ref([]);
const loading = ref(true);
const formatDate = (value) => value ? new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`)) : '–';
const supportLabel = (status) => t(`product.supportStatus.${status ?? 'unknown'}`);

onMounted(async () => {
    try {
        const response = await fetch('/api/public/products');
        const payload = await response.json();
        products.value = payload.data ?? [];
    } finally {
        loading.value = false;
    }
});
</script>
