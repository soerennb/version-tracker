<template>
    <AccountShell>
        <template #title>{{ $t('account.loginTitle') }}</template>
        <template #description>{{ $t('account.loginDescription') }}</template>

        <form class="space-y-5" @submit.prevent="submit">
            <div v-if="route.query.reset === '1'" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status">{{ $t('account.resetSuccess') }}</div>
            <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ fieldError('email') || error.message }}</div>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.email') }}</span><input v-model="form.email" name="email" type="email" autocomplete="email" required class="account-input" :aria-invalid="Boolean(fieldError('email'))" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.password') }}</span><input v-model="form.password" name="password" type="password" autocomplete="current-password" required class="account-input" /></label>
            <label class="flex items-center gap-3 text-sm text-slate-600"><input v-model="form.remember" name="remember" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-emerald-700 focus:ring-emerald-600" /> {{ $t('account.remember') }}</label>
            <button type="submit" class="account-button" :disabled="pending">{{ pending ? $t('account.loading') : $t('account.submitLogin') }}</button>
        </form>

        <div class="mt-6 flex flex-wrap justify-between gap-3 text-sm font-semibold">
            <RouterLink to="/account/forgot-password" class="text-emerald-700 hover:text-emerald-900">{{ $t('account.forgotPassword') }}</RouterLink>
            <RouterLink v-if="runtimeState.access.registration_allowed" to="/account/register" class="text-slate-600 hover:text-slate-950">{{ $t('account.createOne') }}</RouterLink>
        </div>
    </AccountShell>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useRoute, useRouter, RouterLink } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { login } from '../auth';
import { runtimeState } from '../runtime';

const route = useRoute();
const router = useRouter();
const form = reactive({ email: '', password: '', remember: false });
const pending = ref(false);
const error = ref(null);

const fieldError = (field) => error.value?.errors?.[field]?.[0];

const submit = async () => {
    pending.value = true;
    error.value = null;

    try {
        const payload = await login(form);
        const target = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/') ? route.query.redirect : payload.data.email_verified ? '/account' : '/account/verify';
        await router.push(target);
    } catch (requestError) {
        error.value = requestError;
    } finally {
        pending.value = false;
    }
};
</script>
