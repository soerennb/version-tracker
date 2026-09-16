<template>
    <AccountShell>
        <template #title>{{ $t('account.forgotTitle') }}</template>
        <template #description>{{ $t('account.forgotDescription') }}</template>

        <form v-if="!sent" class="space-y-5" @submit.prevent="submit">
            <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ error.message }}</div>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.email') }}</span><input v-model="email" name="email" type="email" autocomplete="email" required class="account-input" /></label>
            <button type="submit" class="account-button" :disabled="pending">{{ pending ? $t('account.loading') : $t('account.sendReset') }}</button>
        </form>
        <div v-else class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-800" role="status">{{ $t('account.resetSent') }}</div>
        <RouterLink to="/account/login" class="mt-6 block text-sm font-semibold text-slate-500 hover:text-slate-950">← {{ $t('account.login') }}</RouterLink>
    </AccountShell>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { fetchJson } from '../api';

const email = ref('');
const pending = ref(false);
const sent = ref(false);
const error = ref(null);

const submit = async () => {
    pending.value = true;
    error.value = null;

    try {
        await fetchJson('/api/auth/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email.value }),
        });
        sent.value = true;
    } catch (requestError) {
        error.value = requestError;
    } finally {
        pending.value = false;
    }
};
</script>
