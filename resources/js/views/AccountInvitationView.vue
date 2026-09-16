<template>
    <AccountShell>
        <template #title>{{ $t('account.invitationTitle') }}</template>
        <template #description>{{ $t('account.invitationDescription') }}</template>

        <div v-if="loading" class="h-32 animate-pulse rounded-xl bg-slate-100"></div>
        <div v-else-if="error || !invitation" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert">{{ $t('account.invitationInvalid') }}</div>
        <form v-else class="space-y-5" @submit.prevent="submit">
            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-700"><span class="font-semibold">{{ invitation.email }}</span><span v-if="invitation.name"> · {{ invitation.name }}</span></div>
            <div v-if="submitError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">{{ firstError || submitError.message }}</div>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.name') }}</span><input v-model="form.name" name="name" type="text" autocomplete="name" class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.password') }}</span><input v-model="form.password" name="password" type="password" autocomplete="new-password" required class="account-input" /></label>
            <label class="block"><span class="text-sm font-semibold text-slate-800">{{ $t('account.confirmPassword') }}</span><input v-model="form.password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="account-input" /></label>
            <button type="submit" class="account-button" :disabled="pending">{{ pending ? $t('account.loading') : $t('account.acceptInvitation') }}</button>
        </form>
    </AccountShell>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import AccountShell from '../components/AccountShell.vue';
import { fetchJson } from '../api';
import { authState } from '../auth';

const route = useRoute();
const router = useRouter();
const token = typeof route.query.token === 'string' ? route.query.token : '';
const invitation = ref(null);
const loading = ref(true);
const error = ref(false);
const pending = ref(false);
const submitError = ref(null);
const form = reactive({ name: '', password: '', password_confirmation: '' });
const firstError = computed(() => Object.values(submitError.value?.errors ?? {})[0]?.[0]);

const submit = async () => {
    pending.value = true;
    submitError.value = null;

    try {
        const payload = await fetchJson(`/api/invitations/${encodeURIComponent(token)}/accept`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(form),
        });
        authState.user = payload.data;
        await router.push('/account');
    } catch (requestError) {
        submitError.value = requestError;
    } finally {
        pending.value = false;
    }
};

onMounted(async () => {
    if (! token) {
        error.value = true;
        loading.value = false;

        return;
    }

    try {
        invitation.value = (await fetchJson(`/api/invitations/${encodeURIComponent(token)}`)).data;
        form.name = invitation.value.name ?? '';
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
});
</script>
