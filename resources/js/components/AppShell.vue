<script setup lang="ts">
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppShellVariant } from '@/types';

defineProps<{ variant?: AppShellVariant }>();
</script>

<template>
    <div v-if="variant === 'header'" class="flex min-h-screen w-full flex-col">
        <slot />
    </div>
    <SidebarProvider v-else :default-open="true">
        <slot />
    </SidebarProvider>
</template>
