import { reactive } from 'vue';
import { fetchJson } from './api';

export const authState = reactive({
    user: null,
    loading: true,
    unreadCount: 0,
});

let currentUserRequest;
let unreadCountRequest;

export async function loadCurrentUser(force = false) {
    if (currentUserRequest && ! force) {
        return currentUserRequest;
    }

    currentUserRequest = fetchJson('/api/auth/me')
        .then((payload) => {
            authState.user = payload.data;
            authState.loading = false;
            return authState.user;
        })
        .catch((error) => {
            if (error.status === 401 || error.status === 419) {
                authState.user = null;
                authState.unreadCount = 0;
            }

            authState.loading = false;
            return null;
        })
        .finally(() => {
            currentUserRequest = null;
        });

    return currentUserRequest;
}

export async function login(credentials) {
    const payload = await fetchJson('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
    });

    authState.user = payload.data;
    authState.loading = false;
    await loadUnreadCount();

    return payload;
}

export async function register(credentials) {
    const payload = await fetchJson('/api/auth/register', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(credentials),
    });

    authState.user = payload.data;
    authState.loading = false;

    return payload;
}

export async function logout() {
    await fetchJson('/api/auth/logout', { method: 'POST' });
    authState.user = null;
    authState.unreadCount = 0;
}

export async function resendVerification() {
    return fetchJson('/api/auth/email/verification-notification', { method: 'POST' });
}

export async function loadUnreadCount() {
    if (! authState.user?.email_verified) {
        authState.unreadCount = 0;

        return 0;
    }

    if (unreadCountRequest) {
        return unreadCountRequest;
    }

    unreadCountRequest = fetchJson('/api/notifications/unread-count')
        .then((payload) => {
            authState.unreadCount = payload.count;

            return payload.count;
        })
        .catch(() => {
            authState.unreadCount = 0;

            return 0;
        })
        .finally(() => {
            unreadCountRequest = null;
        });

    return unreadCountRequest;
}
