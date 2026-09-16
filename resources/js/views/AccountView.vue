<template>
    <section class="mx-auto max-w-6xl space-y-8">
        <div v-if="loading" class="space-y-4"><div class="h-44 animate-pulse rounded-[1.75rem] bg-white"></div><div class="grid gap-5 md:grid-cols-2"><div class="h-80 animate-pulse rounded-2xl bg-white"></div><div class="h-80 animate-pulse rounded-2xl bg-white"></div></div></div>
        <div v-else-if="!authState.user" class="rounded-2xl border border-amber-200 bg-amber-50 p-8 text-sm text-amber-900">{{ $t('account.sessionExpired') }} <RouterLink to="/account/login" class="font-semibold underline">{{ $t('account.login') }}</RouterLink></div>
        <template v-else>
            <header class="relative overflow-hidden rounded-[1.75rem] bg-slate-950 p-6 text-white shadow-[0_24px_80px_-36px_rgba(15,23,42,0.8)] sm:p-10">
                <div class="absolute -right-10 -top-16 h-56 w-56 rounded-full border-[32px] border-emerald-400/20"></div>
                <div class="relative flex flex-wrap items-end justify-between gap-6">
                    <div><p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-300">{{ runtimeState.application.name }} / {{ $t('account.profile') }}</p><h1 class="mt-4 text-4xl font-semibold tracking-[-0.06em] sm:text-6xl">{{ authState.user.name }}</h1><p class="mt-3 text-sm text-slate-300">{{ authState.user.email }}</p></div>
                    <span class="rounded-full border border-slate-700 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-slate-300">{{ authState.user.role }}</span>
                </div>
            </header>

            <section v-if="!authState.user.email_verified" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950 sm:p-6"><p class="font-semibold">{{ $t('account.verificationRequired') }}</p><p class="mt-1">{{ $t('account.verifyDescription') }}</p><RouterLink to="/account/verify" class="mt-3 inline-flex font-semibold underline">{{ $t('account.verify') }} →</RouterLink></section>

            <div v-else class="grid gap-5 lg:grid-cols-[1.1fr_0.9fr]">
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-5"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-rose-700">{{ $t('account.notifications') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ authState.unreadCount }} {{ $t('account.unread') }}</h2></div><button v-if="notifications.some((notification) => !notification.read_at)" type="button" class="text-sm font-semibold text-emerald-700 hover:text-emerald-900" @click="markAllRead">{{ $t('account.markAllRead') }}</button></div>
                    <p v-if="notificationError" class="mt-5 rounded-xl bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ notificationError.message }}</p>
                    <p v-if="!notifications.length && !notificationError" class="mt-6 text-sm leading-6 text-slate-500">{{ $t('account.noNotifications') }}</p>
                    <ul v-else class="mt-5 divide-y divide-slate-100"><li v-for="notification in notifications" :key="notification.id" class="py-4 first:pt-0 last:pb-0"><a :href="actionPath(notification)" class="block rounded-xl p-3 transition hover:bg-slate-50" :class="notification.read_at ? 'opacity-65' : 'bg-rose-50/60'" @click="handleNotificationClick($event, notification)"><div class="flex items-start justify-between gap-4"><p class="text-sm font-semibold text-slate-950">{{ notification.data.title || notification.type }}</p><time class="shrink-0 text-[0.68rem] text-slate-500">{{ formatDateTime(notification.created_at) }}</time></div><p class="mt-1 text-sm leading-6 text-slate-600">{{ notification.data.message }}</p><span class="mt-2 inline-flex text-xs font-bold text-emerald-700">{{ $t('account.openAction') }} →</span></a></li></ul>
                </section>

                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7">
                    <div class="border-b border-slate-200 pb-5"><p class="text-xs font-bold uppercase tracking-[0.18em] text-emerald-700">{{ $t('account.subscriptions') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ $t('account.subscriptionTitle') }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $t('account.subscriptionDescription') }}</p></div>
                    <form v-if="runtimeState.features.products" class="mt-5 space-y-3 rounded-xl bg-slate-50 p-4" @submit.prevent="addSubscription"><label class="block"><span class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $t('account.product') }}</span><select v-model="subscriptionForm.software_id" name="software_id" class="account-input mt-1" required><option value="" disabled>{{ $t('account.chooseProduct') }}</option><option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option></select></label><label class="block"><span class="text-xs font-bold uppercase tracking-[0.12em] text-slate-500">{{ $t('account.event') }}</span><select v-model="subscriptionForm.event" name="event" class="account-input mt-1"><option value="all">{{ $t('account.eventAll') }}</option><option value="release">{{ $t('account.eventRelease') }}</option><option value="security">{{ $t('account.eventSecurity') }}</option><option value="eol">{{ $t('account.eventEol') }}</option></select></label><p v-if="subscriptionError" class="text-sm text-rose-700" role="alert">{{ subscriptionError.message }}</p><button type="submit" class="account-button" :disabled="subscriptionPending">{{ subscriptionPending ? $t('account.loading') : $t('account.subscribe') }}</button></form>
                    <p v-else class="mt-5 rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-600">{{ $t('account.subscriptionsUnavailable') }}</p>
                    <p v-if="!subscriptions.length" class="mt-6 text-sm leading-6 text-slate-500">{{ $t('account.noSubscriptions') }}</p><ul v-else class="mt-5 divide-y divide-slate-100"><li v-for="subscription in subscriptions" :key="subscription.id" class="flex items-center justify-between gap-4 py-4 first:pt-0"><div><p class="text-sm font-semibold text-slate-950">{{ subscription.software?.name }}</p><p class="mt-1 text-xs uppercase tracking-[0.12em] text-slate-500">{{ eventLabel(subscription.event) }}</p></div><button type="button" class="text-xs font-bold text-slate-500 hover:text-rose-700" :disabled="removingSubscription === subscription.id" @click="removeSubscription(subscription)">{{ removingSubscription === subscription.id ? '…' : $t('account.remove') }}</button></li></ul>
                </section>
            </div>

            <section v-if="authState.user.role === 'admin' && authState.user.email_verified" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-7"><div class="border-b border-slate-200 pb-5"><p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">{{ $t('account.adminArea') }}</p><h2 class="mt-2 text-2xl font-semibold tracking-[-0.04em] text-slate-950">{{ $t('account.inviteUser') }}</h2><p class="mt-2 text-sm leading-6 text-slate-600">{{ $t('account.inviteDescription') }}</p></div><form class="mt-5 grid gap-4 md:grid-cols-[1fr_1fr_auto] md:items-end" @submit.prevent="sendInvitation"><label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.email') }}</span><input v-model="inviteForm.email" name="email" type="email" autocomplete="email" required class="account-input mt-1" /></label><label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.name') }}</span><input v-model="inviteForm.name" name="name" type="text" autocomplete="name" class="account-input mt-1" /></label><button type="submit" class="account-button" :disabled="invitePending">{{ invitePending ? $t('account.loading') : $t('account.sendInvitation') }}</button></form><p v-if="inviteMessage" class="mt-4 text-sm font-semibold text-emerald-700" role="status">{{ inviteMessage }}</p><p v-if="inviteError" class="mt-4 text-sm text-rose-700" role="alert">{{ inviteError.message }}</p></section>
        </template>
    </section>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouterLink, useRouter } from 'vue-router';
import { authState, loadCurrentUser, loadUnreadCount } from '../auth';
import { fetchJson } from '../api';
import { runtimeState } from '../runtime';

const router = useRouter();
const { t } = useI18n();
const loading = ref(true);
const notifications = ref([]);
const subscriptions = ref([]);
const products = ref([]);
const notificationError = ref(null);
const subscriptionError = ref(null);
const subscriptionPending = ref(false);
const removingSubscription = ref(null);
const invitePending = ref(false);
const inviteMessage = ref('');
const inviteError = ref(null);
const subscriptionForm = reactive({ software_id: '', event: 'all' });
const inviteForm = reactive({ email: '', name: '' });

const formatDateTime = (value) => value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(value)) : '–';
const eventLabel = (event) => t(`account.event${event.charAt(0).toUpperCase()}${event.slice(1)}`);

const actionPath = (notification) => {
    const actionUrl = notification.data?.action_url;

    if (! actionUrl) {
        return '/account';
    }

    try {
        const url = new URL(actionUrl, window.location.origin);

        return url.origin === window.location.origin ? `${url.pathname}${url.search}` : actionUrl;
    } catch {
        return '/account';
    }
};

const handleNotificationClick = async (event, notification) => {
    if (! notification.read_at) {
        event.preventDefault();
        await markRead(notification);
        await router.push(actionPath(notification));
    }
};

const loadAccountData = async () => {
    loading.value = true;
    notificationError.value = null;

    await loadCurrentUser();

    if (! authState.user || ! authState.user.email_verified) {
        loading.value = false;

        return;
    }

    try {
        const [notificationPayload, subscriptionPayload, productPayload] = await Promise.all([
            fetchJson('/api/notifications?per_page=30'),
            fetchJson('/api/subscriptions'),
            runtimeState.features.products ? fetchJson('/api/public/products?per_page=60') : Promise.resolve({ data: [] }),
        ]);
        notifications.value = notificationPayload.data ?? [];
        subscriptions.value = subscriptionPayload.data ?? [];
        products.value = productPayload.data ?? [];
        await loadUnreadCount();
    } catch (requestError) {
        notificationError.value = requestError;
    } finally {
        loading.value = false;
    }
};

const markRead = async (notification) => {
    if (notification.read_at) {
        return;
    }

    try {
        const payload = await fetchJson(`/api/notifications/${notification.id}/read`, { method: 'POST' });
        Object.assign(notification, payload.data);
        await loadUnreadCount();
    } catch (requestError) {
        notificationError.value = requestError;
    }
};

const markAllRead = async () => {
    try {
        await fetchJson('/api/notifications/read-all', { method: 'POST' });
        notifications.value.forEach((notification) => { notification.read_at = notification.read_at ?? new Date().toISOString(); });
        authState.unreadCount = 0;
    } catch (requestError) {
        notificationError.value = requestError;
    }
};

const addSubscription = async () => {
    subscriptionPending.value = true;
    subscriptionError.value = null;

    try {
        const payload = await fetchJson('/api/subscriptions', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(subscriptionForm),
        });
        const existingIndex = subscriptions.value.findIndex((subscription) => subscription.id === payload.data.id);

        if (existingIndex === -1) {
            subscriptions.value.unshift(payload.data);
        } else {
            subscriptions.value[existingIndex] = payload.data;
        }
    } catch (requestError) {
        subscriptionError.value = requestError;
    } finally {
        subscriptionPending.value = false;
    }
};

const removeSubscription = async (subscription) => {
    removingSubscription.value = subscription.id;

    try {
        await fetchJson(`/api/subscriptions/${subscription.id}`, { method: 'DELETE' });
        subscriptions.value = subscriptions.value.filter((item) => item.id !== subscription.id);
    } catch (requestError) {
        subscriptionError.value = requestError;
    } finally {
        removingSubscription.value = null;
    }
};

const sendInvitation = async () => {
    invitePending.value = true;
    inviteMessage.value = '';
    inviteError.value = null;

    try {
        await fetchJson('/api/invitations', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(inviteForm),
        });
        inviteMessage.value = t('account.invitationSent');
        inviteForm.email = '';
        inviteForm.name = '';
    } catch (requestError) {
        inviteError.value = requestError;
    } finally {
        invitePending.value = false;
    }
};

onMounted(loadAccountData);
</script>
