<template>
    <AccountShell>
        <template #title>{{ $t('account.resetTitle') }}</template>
        <template #description>{{ $t('account.resetDescription', { app: runtimeState.application.name }) }}</template>

        <form class="space-y-5" @submit.prevent="submit">
            <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ firstError || error.message }}</div>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.email') }}</span><input v-model="form.email" name="email" type="email" autocomplete="email" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.newPassword') }}</span><input v-model="form.password" name="password" type="password" autocomplete="new-password" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.confirmPassword') }}</span><input v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="account-input" /></label>
            <button type="submit" class="account-button" :disabled="pending">{{ pending ? $t('account.loading') : $t('account.resetPassword') }}</button>
        </form>
    </AccountShell>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { fetchJson } from '../api';
import { runtimeState } from '../runtime';

const route = useRoute();
const router = useRouter();
const form = reactive({
    token: typeof route.query.token === 'string' ? route.query.token : '',
    email: typeof route.query.email === 'string' ? route.query.email : '',
    password: '',
    password_confirmation: '',
});
const pending = ref(false);
const error = ref(null);
const firstError = computed(() => Object.values(error.value?.errors ?? {})[0]?.[0]);

const submit = async () => {
    pending.value = true;
    error.value = null;

    try {
        await fetchJson('/api/auth/reset-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form),
        });
        await router.push('/account/login?reset=1');
    } catch (requestError) {
        error.value = requestError;
    } finally {
        pending.value = false;
    }
};
</script>
