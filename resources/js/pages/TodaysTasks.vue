<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { RefreshCw, Settings } from 'lucide-vue-next';
import { ref } from 'vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { todaysTasks } from '@/routes';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import { updateTask as updateTaskRoute } from '@/routes/morning-hub/clickup';
import type { BreadcrumbItem, BlockTodaysTasksData, UpdateTaskPayload } from '@/types';

const props = defineProps<{
    hasConfig: boolean;
    todaysTasksData?: BlockTodaysTasksData;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Zadania na dziś', href: todaysTasks() },
];

const { putJson } = useClickUpApi();

const refreshing = ref(false);

const detailOpen = ref(false);
const detailConnectionId = ref<number | null>(null);
const detailTaskId = ref<string | null>(null);

function openTaskDetail(connectionId: number, taskId: string) {
    detailConnectionId.value = connectionId;
    detailTaskId.value = taskId;
    detailOpen.value = true;
}

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['todaysTasksData'],
        onFinish: () => { refreshing.value = false; },
    });
}

async function handleUpdateTask(connectionId: number, taskId: string, payload: UpdateTaskPayload) {
    if (!props.todaysTasksData) return;

    const group = props.todaysTasksData.groups.find((g) => g.connectionId === connectionId);
    if (!group) return;

    const taskIndex = group.tasks.findIndex((t) => t.id === taskId);
    if (taskIndex === -1) return;

    const previousTask = { ...group.tasks[taskIndex] };

    if (payload.status) {
        const statusObj = group.statuses.find((s) => s.status === payload.status);
        group.tasks[taskIndex] = {
            ...group.tasks[taskIndex],
            status: { status: payload.status, color: statusObj?.color ?? previousTask.status.color },
        };
    }

    try {
        await putJson(
            updateTaskRoute.url({ connection: connectionId, taskId }),
            payload as Record<string, unknown>,
        );
    } catch {
        group.tasks[taskIndex] = previousTask;
    }
}

const allTasksEmpty = (() => {
    if (!props.todaysTasksData) return false;
    return props.todaysTasksData.groups.every((g) => g.tasks.length === 0 && !g.error);
});

const multipleGroups = (() => {
    if (!props.todaysTasksData) return false;
    return props.todaysTasksData.groups.length > 1;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Zadania na dziś" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading title="Zadania na dziś" description="Twoje zadania z ClickUp zaplanowane na dziś." />
                <div class="flex items-center gap-2">
                    <Button v-if="hasConfig" variant="ghost" size="icon" :disabled="refreshing" @click="refresh">
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="todaysTasksConfigIndex()">
                            <Settings class="mr-2 h-4 w-4" />
                            Konfiguracja
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-if="!hasConfig" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">
                    Nie skonfigurowano połączeń ClickUp.
                    <Link :href="todaysTasksConfigIndex()" class="underline">Przejdź do konfiguracji</Link>,
                    aby wybrać połączenia.
                </p>
            </div>

            <template v-else>
                <Deferred data="todaysTasksData">
                    <template #fallback>
                        <ClickUpTaskBlockSkeleton />
                    </template>

                    <div class="space-y-4">
                        <p v-if="allTasksEmpty" class="text-sm text-muted-foreground">
                            Brak zadań na dziś. Dobra robota!
                        </p>

                        <template v-for="group in todaysTasksData?.groups" :key="group.connectionId">
                            <p v-if="multipleGroups" class="mt-2 text-xs font-medium text-muted-foreground first:mt-0">
                                {{ group.connectionName }}
                            </p>

                            <Alert v-if="group.error" variant="destructive">
                                <AlertDescription class="flex items-center justify-between">
                                    <span>{{ group.error }}</span>
                                    <Button variant="outline" size="sm" @click="refresh">Ponów</Button>
                                </AlertDescription>
                            </Alert>

                            <ClickUpTaskCard
                                v-for="task in group.tasks"
                                v-else
                                :key="task.id"
                                :task="task"
                                :statuses="group.statuses"
                                @select="(taskId) => openTaskDetail(group.connectionId, taskId)"
                                @update-task="(taskId, payload) => handleUpdateTask(group.connectionId, taskId, payload)"
                            />
                        </template>
                    </div>
                </Deferred>
            </template>
        </div>

        <ClickUpTaskDetail
            v-model:open="detailOpen"
            :connection-id="detailConnectionId"
            :task-id="detailTaskId"
        />
    </AppLayout>
</template>
