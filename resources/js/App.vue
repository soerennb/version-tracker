<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <Navigation />
        <main class="container mx-auto px-4 py-8">
            <RouterView />
        </main>
        <SiteFooter />
    </div>
</template>

<script setup>
import Navigation from './components/Navigation.vue';
import SiteFooter from './components/Footer.vue';
</script>
