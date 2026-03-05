<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { RefreshCw, SkipForward } from 'lucide-vue-next';
import { resolveBlockIcon } from '@/lib/block-icons';
import { computed, ref } from 'vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { updateTask as updateTaskRoute } from '@/routes/morning-hub/clickup';
import type { BlockTodaysTasksData, RoutineBlock, UpdateTaskPayload } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    todaysTasksData: BlockTodaysTasksData | undefined;
    isActiveBlock: boolean;
    isTimerRunning: boolean;
    isTimerExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

const emit = defineEmits<{
    selectTask: [connectionId: number, taskId: string];
    timerStart: [];
    timerPause: [];
    timerResume: [];
    timerReset: [];
    timerSkip: [];
}>();

const { putJson } = useClickUpApi();

const refreshing = ref(false);

const allTasksEmpty = computed(() => {
    if (!props.todaysTasksData) return false;
    return props.todaysTasksData.groups.every((g) => g.tasks.length === 0 && !g.error);
});

const multipleGroups = computed(() => {
    if (!props.todaysTasksData) return false;
    return props.todaysTasksData.groups.length > 1;
});

function refresh() {
    refreshing.value = true;
    router.reload({
        only: [`todays_tasks_${props.block.id}`],
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
</script>

<template>
    <ClickUpTaskBlockSkeleton v-if="!todaysTasksData" />

    <Card v-else :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <component :is="resolveBlockIcon(block)" class="h-4 w-4 text-muted-foreground" />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <RoutineTimerBadge
                    v-if="block.timer_minutes"
                    :timer-minutes="block.timer_minutes"
                    :is-active="isActiveBlock"
                    :is-running="isTimerRunning"
                    :is-expired="isTimerExpired"
                    :remaining-seconds="remainingSeconds"
                    :formatted-time="formattedTime"
                    @start="emit('timerStart')"
                    @pause="emit('timerPause')"
                    @resume="emit('timerResume')"
                    @reset="emit('timerReset')"
                />
            </div>
            <div class="flex items-center gap-1">
                <Button v-if="isActiveBlock" variant="ghost" size="icon" class="h-8 w-8" @click="emit('timerSkip')">
                    <SkipForward class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" class="h-8 w-8" :disabled="refreshing" @click="refresh">
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-2 pt-0">
            <p v-if="allTasksEmpty" class="text-sm text-muted-foreground">
                Brak zadań na dziś. Dobra robota!
            </p>

            <template v-for="group in todaysTasksData.groups" :key="group.connectionId">
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
                    @select="(taskId) => emit('selectTask', group.connectionId, taskId)"
                    @update-task="(taskId, payload) => handleUpdateTask(group.connectionId, taskId, payload)"
                />
            </template>
        </CardContent>
    </Card>
</template>
