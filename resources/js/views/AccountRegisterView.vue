<template>
    <AccountShell>
        <template #title>{{ $t('account.registerTitle') }}</template>
        <template #description>{{ $t('account.registerDescription') }}</template>

        <div v-if="!runtimeState.access.registration_allowed" class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900" role="status">{{ $t('account.registrationUnavailable') }}</div>

        <form v-else class="space-y-5" @submit.prevent="submit">
            <div v-if="error" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ firstError || error.message }}</div>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.name') }}</span><input v-model="form.name" name="name" type="text" autocomplete="name" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.email') }}</span><input v-model="form.email" name="email" type="email" autocomplete="email" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.password') }}</span><input v-model="form.password" name="password" type="password" autocomplete="new-password" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.confirmPassword') }}</span><input v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="account-input" /></label>
            <button type="submit" class="account-button" :disabled="pending">{{ pending ? $t('account.loading') : $t('account.submitRegister') }}</button>
        </form>

        <p class="mt-6 text-sm text-slate-600">{{ $t('account.haveAccount') }} <RouterLink to="/account/login" class="font-semibold text-emerald-700 hover:text-emerald-900">{{ $t('account.signIn') }}</RouterLink></p>
    </AccountShell>
</template>

<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { register } from '../auth';
import { runtimeState } from '../runtime';

const router = useRouter();
const form = reactive({ name: '', email: '', password: '', password_confirmation: '' });
const pending = ref(false);
const error = ref(null);
const firstError = computed(() => Object.values(error.value?.errors ?? {})[0]?.[0]);

const submit = async () => {
    pending.value = true;
    error.value = null;

    try {
        await register(form);
        await router.push('/account/verify');
    } catch (requestError) {
        error.value = requestError;
    } finally {
        pending.value = false;
    }
};
</script>
