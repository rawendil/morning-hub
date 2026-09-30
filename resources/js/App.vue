<template>
    <RouterView />
    <Toaster position="bottom-right" :rich-colors="true" />
</template>

<script setup lang="ts">
import { Toaster } from 'vue-sonner';
</script>
