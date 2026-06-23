<template>
    <nav class="border-b border-slate-200 bg-white">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <RouterLink to="/" class="text-lg font-semibold text-slate-800">
                    Software Version Tracker
                </RouterLink>
                <div class="flex items-center gap-1">
                    <RouterLink
                        to="/products"
                        class="border-b-2 border-transparent px-3 py-5 text-sm font-medium text-slate-600 hover:text-slate-950"
                        active-class="!border-emerald-600 !text-slate-950"
                    >
                        {{ $t('navigation.products') }}
                    </RouterLink>
                    <RouterLink
                        to="/timeline"
                        class="border-b-2 border-transparent px-3 py-5 text-sm font-medium text-slate-600 hover:text-slate-950"
                        active-class="!border-emerald-600 !text-slate-950"
                    >
                        {{ $t('navigation.timeline') }}
                    </RouterLink>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
