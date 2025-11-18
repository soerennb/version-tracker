<template>
    <footer class="border-t border-slate-200 bg-white">
        <div class="container mx-auto px-4 py-6 text-sm text-slate-500 flex items-center justify-between">
            <p>&copy; {{ year }} Software Version Tracker</p>
            <p class="text-xs">{{ $t('navigation.timeline') }}</p>
        </div>
    </footer>
</template>

<script setup>
const year = new Date().getFullYear();
</script>
