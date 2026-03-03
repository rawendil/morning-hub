<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface ClickUpConnection {
    id: number;
    name: string;
    workspace_id: string | null;
    default_space_id: string | null;
    default_folder_id: string | null;
    default_list_id: string | null;
    created_at: string;
    updated_at: string;
}

defineProps<{
    connections: ClickUpConnection[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'ClickUp Settings',
        href: '/morning-hub/clickup',
    },
];
</script>

<template>
    <Head title="ClickUp Settings" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 class="text-2xl font-bold">ClickUp Connections</h1>
            <p v-if="connections.length === 0" class="text-muted-foreground">
                No connections configured yet.
            </p>
            <div v-for="connection in connections" :key="connection.id">
                {{ connection.name }}
            </div>
        </div>
    </AppLayout>
</template>
