import { reactive } from 'vue';
import { fetchJson } from './api';

const fallbackRuntime = {
    application: {
        name: 'VersionTracker',
        tagline: { de: 'Release intelligence', en: 'Release intelligence' },
        intro: {
            de: 'Versionen, Supportfenster und Sicherheitslage an einem Ort.',
            en: 'Versions, support windows, and security posture in one place.',
        },
        footer: {
            de: 'Freigegebene Versionen. Klare Signale.',
            en: 'Published versions. Clear signals.',
        },
        support_url: null,
    },
    locale: {
        default: 'de',
        fallback: 'en',
        supported: ['de', 'en'],
    },
    features: {
        catalog: true,
        search: true,
        products: true,
        timeline: true,
        security: true,
        compare: true,
    },
    access: {
        registration_mode: 'open',
        registration_allowed: true,
        invitation_registration_allowed: true,
        email_verification_required: true,
    },
};

export const runtimeState = reactive({
    ...fallbackRuntime,
    loaded: false,
});

export async function loadRuntime() {
    try {
        const payload = await fetchJson('/api/public/runtime');
        const runtime = payload.data ?? {};

        Object.assign(runtimeState.application, fallbackRuntime.application, runtime.application ?? {});
        Object.assign(runtimeState.locale, fallbackRuntime.locale, runtime.locale ?? {});
        Object.assign(runtimeState.features, fallbackRuntime.features, runtime.features ?? {});
        Object.assign(runtimeState.access, fallbackRuntime.access, runtime.access ?? {});
    } catch {
        // Safe defaults keep the public shell usable when the API is unavailable.
    } finally {
        runtimeState.loaded = true;
    }

    return runtimeState;
}

export function localizedRuntime(values, locale = 'de') {
    if (! values) {
        return '';
    }

    return values[locale] ?? values[runtimeState.locale.fallback] ?? Object.values(values)[0] ?? '';
}
