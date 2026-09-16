<template>
    <div class="min-h-screen bg-[#f3f5f1] text-slate-950">
        <a href="#main-content" class="skip-link">{{ $t('common.skipToContent') }}</a>
        <Navigation />
        <main id="main-content" class="mx-auto min-h-[calc(100vh-9rem)] max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
            <RouterView v-slot="{ Component }">
                <Transition name="page" mode="out-in">
                    <component :is="Component" />
                </Transition>
            </RouterView>
        </main>
        <SiteFooter />
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterView, useRoute } from 'vue-router';
import { loadCurrentUser, loadUnreadCount } from './auth';
import Navigation from './components/Navigation.vue';
import SiteFooter from './components/Footer.vue';
import { runtimeState } from './runtime';

const route = useRoute();
const { locale, t } = useI18n();

const updateDocument = () => {
    document.title = route.meta.title ? `${t(route.meta.title)} · ${runtimeState.application.name}` : runtimeState.application.name;
    document.documentElement.lang = locale.value;
};

watch(() => [route.fullPath, locale.value, runtimeState.application.name], updateDocument, { immediate: true });
onMounted(async () => {
    await loadCurrentUser();
    await loadUnreadCount();
});
</script>
