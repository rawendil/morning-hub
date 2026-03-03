<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ClickUpConnectionCard from '@/components/morning-hub/ClickUpConnectionCard.vue';
import ClickUpConnectionForm from '@/components/morning-hub/ClickUpConnectionForm.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { index } from '@/routes/morning-hub/clickup';
import type { BreadcrumbItem, ClickUpConnection } from '@/types';

defineProps<{
    connections: ClickUpConnection[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Połączenia ClickUp', href: index() },
];

const addOpen = ref(false);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Połączenia ClickUp" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading title="Połączenia ClickUp" description="Zarządzaj połączeniami API ClickUp i domyślnymi ustawieniami." />
                <Button class="gap-2" @click="addOpen = true">
                    <Plus class="h-4 w-4" />
                    Dodaj połączenie
                </Button>
            </div>

            <div v-if="connections.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">Brak połączeń. Dodaj pierwsze połączenie ClickUp, aby rozpocząć.</p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2">
                <ClickUpConnectionCard
                    v-for="connection in connections"
                    :key="connection.id"
                    :connection="connection"
                />
            </div>
        </div>

        <ClickUpConnectionForm v-model:open="addOpen" />
    </AppLayout>
</template>
