<script setup lang="ts">
import { Deferred, Head, Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import DashboardBlockRenderer from '@/components/morning-hub/DashboardBlockRenderer.vue';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import type { BreadcrumbItem, BlockTasksData, RoutineBlock } from '@/types';

const props = defineProps<{
    blocks: RoutineBlock[];
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard() },
];

function getTasksData(blockId: number): BlockTasksData | undefined {
    return (page.props as Record<string, unknown>)[`tasks_${blockId}`] as BlockTasksData | undefined;
}

const detailOpen = ref(false);
const detailConnectionId = ref<number | null>(null);
const detailTaskId = ref<string | null>(null);

function openTaskDetail(connectionId: number, taskId: string) {
    detailConnectionId.value = connectionId;
    detailTaskId.value = taskId;
    detailOpen.value = true;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard" />

        <div class="space-y-6 p-6">
            <Heading title="Morning Hub" description="Your daily routine dashboard." />

            <div v-if="blocks.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">
                    No routine blocks configured.
                    <Link :href="routineIndex.url()" class="underline">Go to Morning Routine</Link>
                    to set up your blocks.
                </p>
            </div>

            <div v-else class="grid gap-4">
                <template v-for="block in blocks" :key="block.id">
                    <Deferred
                        v-if="block.type === 'clickup' && block.clickup_connection_id"
                        :data="`tasks_${block.id}`"
                    >
                        <template #fallback>
                            <ClickUpTaskBlockSkeleton />
                        </template>
                        <DashboardBlockRenderer
                            :block="block"
                            :tasks-data="getTasksData(block.id)"
                            @select-task="openTaskDetail"
                        />
                    </Deferred>
                    <DashboardBlockRenderer
                        v-else
                        :block="block"
                        @select-task="openTaskDetail"
                    />
                </template>
            </div>
        </div>

        <ClickUpTaskDetail
            v-model:open="detailOpen"
            :connection-id="detailConnectionId"
            :task-id="detailTaskId"
        />
    </AppLayout>
</template>
