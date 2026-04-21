<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />

            <!-- Forest footer -->
            <footer class="relative mt-auto h-48 sm:h-64 lg:h-80">
                <img
                    src="/images/forest.jpg"
                    alt=""
                    loading="lazy"
                    class="absolute inset-0 h-full w-full mask-[radial-gradient(ellipse_at_bottom_right,black_20%,transparent_70%)] object-cover"
                />
            </footer>
        </AppContent>
    </AppShell>
</template>
