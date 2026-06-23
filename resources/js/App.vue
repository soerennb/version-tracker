<template>
    <div class="flex min-h-screen flex-col bg-gray-50 text-gray-900">
        <Navigation />
        <main class="container mx-auto flex-1 px-4 py-8">
            <RouterView />
        </main>
        <SiteFooter />
    </div>
</template>

<script setup>
import Navigation from './components/Navigation.vue';
import SiteFooter from './components/Footer.vue';
</script>
