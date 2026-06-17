<script setup lang="ts">
import { Plus, RefreshCw, SkipForward } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import BlockCompletedBadge from '@/components/morning-hub/BlockCompletedBadge.vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import axiosInstance from '@/lib/axios';
import { resolveBlockIcon } from '@/lib/block-icons';
import type {
    BlockTasksData,
    ClickUpStatus,
    RoutineBlock,
    UpdateTaskPayload,
} from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    tasksData: BlockTasksData | undefined;
    isActiveBlock: boolean;
    isCompleted: boolean;
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
    refresh: [];
}>();

const { t } = useTranslations();

const availableStatuses = ref<ClickUpStatus[]>([]);
const showCreateForm = ref(false);
const newTaskName = ref('');
const creating = ref(false);

onMounted(async () => {
    if (
        !props.block.clickup_connection_id ||
        !props.block.clickup_connection?.default_list_id
    ) {
        return;
    }
    try {
        const id = props.block.clickup_connection_id;
        const listId = props.block.clickup_connection.default_list_id;
        const response = await axiosInstance.get(
            `/morning-hub/clickup/${id}/statuses?list_id=${listId}`,
        );
        availableStatuses.value = response.data.data;
    } catch {
        // Silently fail — status dropdown will be disabled
    }
});

function refresh() {
    emit('refresh');
}

async function handleUpdateTask(taskId: string, payload: UpdateTaskPayload) {
    if (!props.tasksData || !props.block.clickup_connection_id) return;

    const taskIndex = props.tasksData.tasks.findIndex((t) => t.id === taskId);
    if (taskIndex === -1) return;

    const previousTask = { ...props.tasksData.tasks[taskIndex] };

    // Optimistic update
    if (payload.status) {
        const statusObj = availableStatuses.value.find(
            (s) => s.status === payload.status,
        );
        // eslint-disable-next-line vue/no-mutating-props
        props.tasksData.tasks[taskIndex] = {
            ...props.tasksData.tasks[taskIndex],
            status: {
                status: payload.status,
                color: statusObj?.color ?? previousTask.status.color,
            },
        };
    }

    try {
        const id = props.block.clickup_connection_id;
        await axiosInstance.put(
            `/morning-hub/clickup/${id}/tasks/${taskId}`,
            payload,
        );
    } catch {
        // Rollback on error
        // eslint-disable-next-line vue/no-mutating-props
        props.tasksData.tasks[taskIndex] = previousTask;
    }
}

async function handleCreateTask() {
    if (!newTaskName.value.trim() || !props.block.clickup_connection_id) return;
    const listId = props.block.clickup_connection?.default_list_id;
    if (!listId) return;

    creating.value = true;
    try {
        const id = props.block.clickup_connection_id;
        await axiosInstance.post(`/morning-hub/clickup/${id}/tasks`, {
            list_id: listId,
            name: newTaskName.value.trim(),
        });
        newTaskName.value = '';
        showCreateForm.value = false;
        refresh();
    } catch {
        // Could show error
    } finally {
        creating.value = false;
    }
}
</script>

<template>
    <Card
        v-if="!block.clickup_connection_id"
        :class="{
            'ring-2 ring-primary/30': isActiveBlock,
            'opacity-60 transition-opacity hover:opacity-100': isCompleted,
        }"
    >
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-3"
        >
            <div class="flex items-center gap-2">
                <component
                    :is="resolveBlockIcon(block)"
                    class="h-4 w-4 text-muted-foreground"
                />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <BlockCompletedBadge v-if="isCompleted" />
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <p class="text-sm text-muted-foreground">
                {{ t('Skonfiguruj połączenie ClickUp, aby zobaczyć zadania.') }}
            </p>
        </CardContent>
    </Card>

    <ClickUpTaskBlockSkeleton v-else-if="!tasksData" />

    <Card
        v-else
        :class="{
            'ring-2 ring-primary/30': isActiveBlock,
            'opacity-60 transition-opacity hover:opacity-100': isCompleted,
        }"
    >
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-3"
        >
            <div class="flex items-center gap-2">
                <component
                    :is="resolveBlockIcon(block)"
                    class="h-4 w-4 text-muted-foreground"
                />
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
                <BlockCompletedBadge v-if="isCompleted" />
            </div>
            <div class="flex items-center gap-1">
                <Button
                    v-if="isActiveBlock"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="emit('timerSkip')"
                >
                    <SkipForward class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="showCreateForm = !showCreateForm"
                >
                    <Plus class="h-4 w-4" />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="refresh"
                >
                    <RefreshCw class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>

        <div v-if="showCreateForm" class="space-y-2 border-t px-4 py-3">
            <Input
                v-model="newTaskName"
                :placeholder="t('Nazwa zadania...')"
                :disabled="creating"
                @keyup.enter="handleCreateTask"
            />
            <div class="flex justify-end gap-2">
                <Button
                    variant="ghost"
                    size="sm"
                    @click="showCreateForm = false"
                    >{{ t('Anuluj') }}</Button
                >
                <Button
                    size="sm"
                    :disabled="!newTaskName.trim() || creating"
                    @click="handleCreateTask"
                >
                    {{ creating ? t('Tworzenie...') : t('Utwórz') }}
                </Button>
            </div>
        </div>

        <CardContent class="space-y-2 pt-0">
            <Alert v-if="tasksData.error" variant="destructive">
                <AlertDescription class="flex items-center justify-between">
                    <span>{{ tasksData.error }}</span>
                    <Button variant="outline" size="sm" @click="refresh">{{
                        t('Ponów')
                    }}</Button>
                </AlertDescription>
            </Alert>

            <p
                v-else-if="tasksData.tasks.length === 0"
                class="text-sm text-muted-foreground"
            >
                {{ t('Brak zadań na dziś.') }}
            </p>

            <ClickUpTaskCard
                v-for="task in tasksData.tasks"
                v-else
                :key="task.id"
                :task="task"
                :statuses="availableStatuses"
                @select="
                    (taskId) =>
                        emit('selectTask', block.clickup_connection_id!, taskId)
                "
                @update-task="handleUpdateTask"
            />
        </CardContent>
    </Card>
</template>
