import './bootstrap';

import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import routes from './routes';
import messages from './i18n';
import { loadRuntime, runtimeState } from './runtime';

const i18n = createI18n({
    legacy: false,
    locale: window.localStorage.getItem('versiontracker-locale') ?? runtimeState.locale.default,
    fallbackLocale: runtimeState.locale.fallback,
    messages,
});

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    if (to.meta.feature && ! runtimeState.features[to.meta.feature]) {
        return { name: 'not-found' };
    }

    return true;
});

const app = createApp(App);
app.use(i18n);
app.use(router);

loadRuntime().then(() => {
    i18n.global.fallbackLocale.value = runtimeState.locale.fallback;

    const storedLocale = window.localStorage.getItem('versiontracker-locale');

    if (! storedLocale && runtimeState.locale.supported.includes(runtimeState.locale.default)) {
        i18n.global.locale.value = runtimeState.locale.default;
    }
}).finally(() => app.mount('#app'));
