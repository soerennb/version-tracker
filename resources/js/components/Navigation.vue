<template>
    <nav class="bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <RouterLink to="/" class="text-lg font-semibold text-slate-800">
                    Software Version Tracker
                </RouterLink>
                <div class="flex items-center space-x-4">
                    <RouterLink
                        to="/timeline"
                        class="text-sm font-medium text-slate-600 hover:text-amber-600"
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
