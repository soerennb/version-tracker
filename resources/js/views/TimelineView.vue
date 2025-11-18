<template>
    <section>
        <header class="mb-6">
            <h1 class="text-2xl font-semibold text-slate-800">{{ $t('timeline.title') }}</h1>
        </header>

        <div v-if="softwareOptions.length" class="flex flex-wrap gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $t('timeline.filter.label') }}</span>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-wide transition"
                    :class="isActiveSoftware(null) ? 'bg-slate-900 text-white shadow' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                    @click="handleSoftwareSelect(null)"
                >
                    {{ $t('timeline.filter.all') }}
                </button>
                <button
                    v-for="software in softwareOptions"
                    :key="software.id"
                    type="button"
                    class="inline-flex items-center gap-2 rounded-full border px-4 py-2 text-xs font-semibold uppercase tracking-wide transition"
                    :class="isActiveSoftware(software.id)
                        ? 'border-transparent bg-indigo-600 text-white shadow'
                        : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
                    @click="handleSoftwareSelect(software.id)"
                >
                    <span class="h-2 w-2 rounded-full bg-current"></span>
                    {{ software.name }}
                </button>
            </div>
        </div>

        <div v-if="loading" class="mt-6 text-slate-500 text-sm">
            {{ $t('timeline.loading') }}
        </div>

        <div v-else-if="timeline.length === 0" class="mt-6 text-slate-500 text-sm">
            {{ $t('timeline.empty') }}
        </div>

        <div v-else class="relative mt-8 pl-6">
            <div class="absolute left-2 top-0 bottom-0 w-px bg-slate-200"></div>
            <ol class="space-y-8">
                <li
                    v-for="item in timeline"
                    :key="item.id"
                    class="relative flex gap-4"
                >
                    <div class="relative flex flex-col items-center">
                        <span class="relative flex h-3 w-3">
                            <span :class="['absolute inline-flex h-full w-full animate-ping rounded-full opacity-40', colorClassFor(item)]"></span>
                            <span :class="['relative inline-flex h-3 w-3 rounded-full border-2 border-white', colorClassFor(item)]"></span>
                        </span>
                    </div>
                    <div class="flex-1 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-slate-500">
                            <span class="font-semibold text-slate-700">{{ item.software }}</span>
                            <span>{{ $t('timeline.released') }} {{ item.release_date }}</span>
                        </div>
                        <h2 class="mt-2 text-lg font-semibold text-slate-900">
                            {{ item.version }}
                            <span v-if="item.headline" class="text-slate-500">— {{ item.headline }}</span>
                        </h2>
                        <p v-if="item.summary" class="mt-2 text-sm text-slate-600">
                            {{ item.summary }}
                        </p>
                    </div>
                </li>
            </ol>
        </div>
    </section>
</template>

<script setup>
import { onMounted, ref } from 'vue';

const timeline = ref([]);
const loading = ref(true);
const softwareOptions = ref([]);
const activeSoftware = ref(null);
const softwareColors = ref({});

const colorPalette = ['bg-indigo-500', 'bg-amber-500', 'bg-emerald-500', 'bg-rose-500', 'bg-sky-500', 'bg-purple-500'];

const assignColors = (items) => {
    const map = {};
    items.forEach((item) => {
        const key = item.software_id ?? `unknown-${item.id}`;
        if (!map[key]) {
            const index = Object.keys(map).length % colorPalette.length;
            map[key] = colorPalette[index];
        }
    });

    softwareColors.value = map;
};

const buildQuery = (softwareId) => {
    const params = new URLSearchParams();
    if (softwareId) {
        params.set('software', softwareId);
    }
    const query = params.toString();
    return query ? `?${query}` : '';
};

const loadTimeline = async (softwareId = null) => {
    loading.value = true;

    try {
        const response = await fetch(`/api/public/timeline${buildQuery(softwareId)}`);
        const data = await response.json();
        timeline.value = data.data ?? [];
        softwareOptions.value = data.filters?.software ?? [];
        activeSoftware.value = data.filters?.active ?? null;
        assignColors(timeline.value);
    } catch (error) {
        console.error('Failed to load timeline', error);
        timeline.value = [];
    } finally {
        loading.value = false;
    }
};

const handleSoftwareSelect = (softwareId) => {
    if ((softwareId ?? null) === (activeSoftware.value ?? null)) {
        return;
    }

    loadTimeline(softwareId);
};

const isActiveSoftware = (softwareId) => (activeSoftware.value ?? null) === (softwareId ?? null);

const colorClassFor = (item) => {
    const key = item.software_id ?? `unknown-${item.id}`;
    return softwareColors.value[key] ?? 'bg-slate-400';
};

onMounted(() => loadTimeline());
</script>
