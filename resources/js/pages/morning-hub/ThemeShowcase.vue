<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { themeShowcase } from '@/routes/morning-hub';
import type { BreadcrumbItem } from '@/types';

const { t } = useTranslations();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: 'Theme Showcase', href: themeShowcase() },
]);

const coreColors = [
    { name: 'Primary', variable: '--primary', bg: 'bg-primary', fg: 'text-primary-foreground' },
    { name: 'Secondary', variable: '--secondary', bg: 'bg-secondary', fg: 'text-secondary-foreground' },
    { name: 'Accent', variable: '--accent', bg: 'bg-accent', fg: 'text-accent-foreground' },
    { name: 'Muted', variable: '--muted', bg: 'bg-muted', fg: 'text-muted-foreground' },
    { name: 'Destructive', variable: '--destructive', bg: 'bg-destructive', fg: 'text-destructive-foreground' },
];

const surfaceColors = [
    { name: 'Background', variable: '--background', bg: 'bg-background', fg: 'text-foreground' },
    { name: 'Card', variable: '--card', bg: 'bg-card', fg: 'text-card-foreground' },
    { name: 'Popover', variable: '--popover', bg: 'bg-popover', fg: 'text-popover-foreground' },
];

const utilityColors = [
    { name: 'Border', variable: '--border', bg: 'bg-border', fg: 'text-foreground' },
    { name: 'Input', variable: '--input', bg: 'bg-input', fg: 'text-foreground' },
    { name: 'Ring', variable: '--ring', bg: 'bg-ring', fg: 'text-primary-foreground' },
];

const sidebarColors = [
    { name: 'Sidebar', variable: '--sidebar-background', bg: 'bg-sidebar', fg: 'text-sidebar-foreground' },
    { name: 'Sidebar Primary', variable: '--sidebar-primary', bg: 'bg-sidebar-primary', fg: 'text-sidebar-primary-foreground' },
    { name: 'Sidebar Accent', variable: '--sidebar-accent', bg: 'bg-sidebar-accent', fg: 'text-sidebar-accent-foreground' },
];

const chartColors = [
    { name: 'Chart 1', variable: '--chart-1', bg: 'bg-chart-1' },
    { name: 'Chart 2', variable: '--chart-2', bg: 'bg-chart-2' },
    { name: 'Chart 3', variable: '--chart-3', bg: 'bg-chart-3' },
    { name: 'Chart 4', variable: '--chart-4', bg: 'bg-chart-4' },
    { name: 'Chart 5', variable: '--chart-5', bg: 'bg-chart-5' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Theme Showcase" />

        <div class="mx-auto max-w-3xl space-y-10 p-6">
            <Heading title="Theme Showcase" :description="t('Przegląd kolorów i tokenów wizualnych używanych w projekcie.')" />

            <!-- Core Colors -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Kolory podstawowe') }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="color in coreColors"
                        :key="color.name"
                        :class="[color.bg, color.fg]"
                        class="flex items-center justify-between rounded-md border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ color.name }}</p>
                            <p class="text-xs opacity-75">{{ color.variable }}</p>
                        </div>
                        <code class="text-xs opacity-75">{{ color.bg.replace('bg-', '') }}</code>
                    </div>
                </div>
            </section>

            <!-- Surface Colors -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Powierzchnie') }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="color in surfaceColors"
                        :key="color.name"
                        :class="[color.bg, color.fg]"
                        class="flex items-center justify-between rounded-md border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ color.name }}</p>
                            <p class="text-xs opacity-75">{{ color.variable }}</p>
                        </div>
                        <code class="text-xs opacity-75">{{ color.bg.replace('bg-', '') }}</code>
                    </div>
                </div>
            </section>

            <!-- Utility Colors -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Kolory narzędziowe') }}</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="color in utilityColors"
                        :key="color.name"
                        :class="[color.bg, color.fg]"
                        class="flex items-center justify-between rounded-md border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ color.name }}</p>
                            <p class="text-xs opacity-75">{{ color.variable }}</p>
                        </div>
                        <code class="text-xs opacity-75">{{ color.bg.replace('bg-', '') }}</code>
                    </div>
                </div>
            </section>

            <!-- Sidebar Colors -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">Sidebar</h3>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div
                        v-for="color in sidebarColors"
                        :key="color.name"
                        :class="[color.bg, color.fg]"
                        class="flex items-center justify-between rounded-md border p-4"
                    >
                        <div>
                            <p class="font-medium">{{ color.name }}</p>
                            <p class="text-xs opacity-75">{{ color.variable }}</p>
                        </div>
                        <code class="text-xs opacity-75">{{ color.bg.replace('bg-', '') }}</code>
                    </div>
                </div>
            </section>

            <!-- Chart Colors -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Kolory wykresów') }}</h3>
                <div class="flex gap-2">
                    <div
                        v-for="color in chartColors"
                        :key="color.name"
                        :class="color.bg"
                        class="flex h-16 flex-1 items-end rounded-md border p-2"
                    >
                        <span class="text-xs font-medium text-white drop-shadow-md">{{ color.name }}</span>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
