<template>
    <AccountShell>
        <template #title>{{ $t('account.verifyTitle') }}</template>
        <template #description>{{ $t('account.verifyDescription') }}</template>

        <div v-if="verified || authState.user?.email_verified" class="space-y-5">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800" role="status">{{ $t('account.verifiedSuccess') }}</div>
            <RouterLink to="/account" class="account-button inline-flex">{{ $t('account.continue') }} →</RouterLink>
        </div>
        <div v-else class="space-y-5">
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900">{{ $t('account.verifyPending') }} <strong>{{ authState.user?.email }}</strong></div>
            <p v-if="sent" class="text-sm font-semibold text-emerald-700" role="status">{{ $t('account.resendSent') }}</p>
            <p v-if="error" class="text-sm text-rose-700" role="alert">{{ error.message }}</p>
            <button type="button" class="account-button" :disabled="pending" @click="resend">{{ pending ? $t('account.resending') : $t('account.resend') }}</button>
            <RouterLink to="/" class="block text-center text-sm font-semibold text-slate-500 hover:text-slate-950">{{ $t('account.backToCatalog') }}</RouterLink>
        </div>
    </AccountShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { RouterLink, useRouter, useRoute } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { authState, loadCurrentUser, resendVerification } from '../auth';

const router = useRouter();
const route = useRoute();
const verified = ref(route.query.verified === '1');
const pending = ref(false);
const sent = ref(false);
const error = ref(null);

const resend = async () => {
    pending.value = true;
    error.value = null;
    sent.value = false;

    try {
        await resendVerification();
        sent.value = true;
    } catch (requestError) {
        error.value = requestError;
    } finally {
        pending.value = false;
    }
};

onMounted(async () => {
    await loadCurrentUser();

    if (! authState.user) {
        await router.push('/account/login');
    }
});
</script>
