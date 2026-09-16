<template>
    <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-[#f3f5f1]/95 backdrop-blur">
        <nav class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8" :aria-label="$t('navigation.label')">
            <div class="flex min-h-20 items-center justify-between gap-6">
                <RouterLink to="/" class="group flex min-w-0 items-center gap-3" @click="menuOpen = false">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-950 font-mono text-sm font-bold text-emerald-300 shadow-lg shadow-slate-950/10">VT</span>
                    <span class="min-w-0"><span class="block truncate text-sm font-bold tracking-[-0.02em] text-slate-950">{{ runtimeState.application.name }}</span><span class="hidden text-[0.63rem] font-semibold uppercase tracking-[0.16em] text-slate-600 sm:block">{{ localizedRuntime(runtimeState.application.tagline, locale) }}</span></span>
                </RouterLink>

                <button id="mobile-menu-button" type="button" class="rounded-xl border border-slate-300 p-2 text-slate-700 transition hover:border-slate-500 lg:hidden" :aria-expanded="menuOpen" aria-controls="public-navigation" :aria-label="menuOpen ? $t('navigation.close') : $t('navigation.toggle')" @click="menuOpen = !menuOpen">
                    <span class="sr-only">{{ menuOpen ? $t('navigation.close') : $t('navigation.toggle') }}</span>
                    <span class="block h-0.5 w-5 bg-current transition" :class="menuOpen ? 'translate-y-1.5 rotate-45' : ''"></span><span class="mt-1.5 block h-0.5 w-5 bg-current transition" :class="menuOpen ? 'opacity-0' : ''"></span><span class="mt-1.5 block h-0.5 w-5 bg-current transition" :class="menuOpen ? '-translate-y-1.5 -rotate-45' : ''"></span>
                </button>

                <div id="public-navigation" class="absolute inset-x-4 top-[calc(100%-0.25rem)] rounded-2xl border border-slate-200 bg-white p-2 shadow-xl shadow-slate-950/10 lg:static lg:flex lg:items-center lg:gap-1 lg:border-0 lg:bg-transparent lg:p-0 lg:shadow-none" :class="menuOpen ? 'block' : 'hidden lg:flex'">
                    <RouterLink v-for="item in navigationItems" :key="item.to" :to="item.to" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-950 lg:py-2.5" active-class="!bg-slate-950 !text-white" @click="menuOpen = false">{{ item.label }}</RouterLink>
                    <span class="mx-2 hidden h-6 w-px bg-slate-200 lg:block"></span>
                    <RouterLink v-if="authState.user" to="/account" class="flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-950 lg:py-2.5" active-class="!bg-slate-950 !text-white" @click="menuOpen = false">
                        {{ authState.user.name }}
                        <span v-if="authState.unreadCount" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[0.65rem] font-bold text-white">{{ authState.unreadCount }}</span>
                    </RouterLink>
                    <RouterLink v-else to="/account/login" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-600 transition hover:bg-white hover:text-slate-950 lg:py-2.5" active-class="!bg-slate-950 !text-white" @click="menuOpen = false">{{ $t('account.login') }}</RouterLink>
                    <button v-if="authState.user" type="button" class="rounded-xl px-4 py-3 text-sm font-semibold text-slate-500 transition hover:bg-white hover:text-slate-950 lg:py-2.5" :disabled="logoutPending" @click="handleLogout">{{ logoutPending ? $t('account.loggingOut') : $t('account.logout') }}</button>
                    <button id="locale-toggle" type="button" class="rounded-xl px-3 py-2.5 text-xs font-bold uppercase tracking-[0.14em] text-slate-600 transition hover:bg-white hover:text-slate-950" :aria-label="`${locale.toUpperCase()} — ${$t('navigation.switchLanguage')}`" @click="toggleLocale">{{ locale.toUpperCase() }}</button>
                </div>
            </div>
        </nav>
    </header>
</template>

<script setup>
import { computed, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRoute, useRouter } from 'vue-router';
import { authState, loadUnreadCount, logout } from '../auth';
import { localizedRuntime, runtimeState } from '../runtime';

const { locale, t } = useI18n();
const route = useRoute();
const router = useRouter();
const menuOpen = ref(false);
const logoutPending = ref(false);
const navigationItems = computed(() => [
    runtimeState.features.search ? { to: '/search', label: t('navigation.search') } : null,
    runtimeState.features.products ? { to: '/products', label: t('navigation.products') } : null,
    runtimeState.features.timeline ? { to: '/timeline', label: t('navigation.timeline') } : null,
    runtimeState.features.security ? { to: '/security', label: t('navigation.security') } : null,
].filter(Boolean));

const toggleLocale = () => {
    locale.value = locale.value === 'de' ? 'en' : 'de';
    window.localStorage.setItem('versiontracker-locale', locale.value);
    document.documentElement.lang = locale.value;
};

const handleLogout = async () => {
    logoutPending.value = true;

    try {
        await logout();
        await router.push('/');
    } finally {
        logoutPending.value = false;
        menuOpen.value = false;
    }
};

watch(() => route.fullPath, () => { menuOpen.value = false; });
watch(() => authState.user?.id, loadUnreadCount);
</script>
