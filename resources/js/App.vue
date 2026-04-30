<template>
    <RouterView />
    <Toaster position="bottom-right" :rich-colors="true" />
    <CookieConsentModal />
</template>

<script setup lang="ts">
import { Toaster } from 'vue-sonner';
import CookieConsentModal from '@/components/CookieConsentModal.vue';
</script>
