import './bootstrap';

import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import routes from './routes';
import messages from './i18n';

const i18n = createI18n({
    legacy: false,
    locale: 'de',
    fallbackLocale: 'en',
    messages,
});

const router = createRouter({
    history: createWebHistory(),
    routes,
});

const app = createApp(App);
app.use(i18n);
app.use(router);
app.mount('#app');
