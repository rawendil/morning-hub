<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface ClickUpConnection {
    id: number;
    name: string;
}

interface RoutineBlock {
    id: number;
    type: string;
    name: string;
    sort_order: number;
    timer_minutes: number | null;
    clickup_connection_id: number | null;
    clickup_connection: ClickUpConnection | null;
    config: Record<string, unknown> | null;
}

defineProps<{
    blocks: RoutineBlock[];
    connections: ClickUpConnection[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Morning Routine',
        href: '/morning-hub/routine',
    },
];
</script>

<template>
    <Head title="Morning Routine" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="text-2xl font-bold">Morning Routine</h1>
            <p v-if="blocks.length === 0" class="text-muted-foreground">
                No routine blocks configured yet.
            </p>
            <div v-for="block in blocks" :key="block.id">
                {{ block.name }}
            </div>
        </div>
    </AppLayout>
</template>
