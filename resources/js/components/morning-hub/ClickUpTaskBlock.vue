<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Clock, Plus, RefreshCw } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { createTask as createTaskRoute, statuses as statusesRoute, updateTask as updateTaskRoute } from '@/routes/morning-hub/clickup';
import type { BlockTasksData, ClickUpStatus, RoutineBlock, UpdateTaskPayload } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    tasksData: BlockTasksData | undefined;
}>();

const emit = defineEmits<{
    selectTask: [connectionId: number, taskId: string];
}>();

const { fetchJson, postJson, putJson } = useClickUpApi();

const refreshing = ref(false);
const availableStatuses = ref<ClickUpStatus[]>([]);
const showCreateForm = ref(false);
const newTaskName = ref('');
const creating = ref(false);

onMounted(async () => {
    if (!props.block.clickup_connection_id || !props.block.clickup_connection?.default_list_id) return;
    try {
        availableStatuses.value = await fetchJson<ClickUpStatus[]>(
            statusesRoute.url(props.block.clickup_connection_id, {
                query: { list_id: props.block.clickup_connection.default_list_id },
            }),
        );
    } catch {
        // Silently fail — status dropdown will be disabled
    }
});

function refresh() {
    refreshing.value = true;
    router.reload({
        only: [`tasks_${props.block.id}`],
        onFinish: () => { refreshing.value = false; },
    });
}

async function handleUpdateTask(taskId: string, payload: UpdateTaskPayload) {
    if (!props.tasksData || !props.block.clickup_connection_id) return;

    const taskIndex = props.tasksData.tasks.findIndex((t) => t.id === taskId);
    if (taskIndex === -1) return;

    const previousTask = { ...props.tasksData.tasks[taskIndex] };

    // Optimistic update
    if (payload.status) {
        const statusObj = availableStatuses.value.find((s) => s.status === payload.status);
        props.tasksData.tasks[taskIndex] = {
            ...props.tasksData.tasks[taskIndex],
            status: { status: payload.status, color: statusObj?.color ?? previousTask.status.color },
        };
    }

    try {
        await putJson(
            updateTaskRoute.url({ connection: props.block.clickup_connection_id, taskId }),
            payload as Record<string, unknown>,
        );
    } catch {
        // Rollback on error
        props.tasksData.tasks[taskIndex] = previousTask;
    }
}

async function handleCreateTask() {
    if (!newTaskName.value.trim() || !props.block.clickup_connection_id) return;
    const listId = props.block.clickup_connection?.default_list_id;
    if (!listId) return;

    creating.value = true;
    try {
        await postJson(createTaskRoute.url(props.block.clickup_connection_id), {
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
    <ClickUpTaskBlockSkeleton v-if="!tasksData" />

    <Card v-else>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <Badge v-if="block.timer_minutes" variant="outline" class="gap-1">
                    <Clock class="h-3 w-3" />
                    {{ block.timer_minutes }}m
                </Badge>
            </div>
            <div class="flex items-center gap-1">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="showCreateForm = !showCreateForm">
                    <Plus class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" class="h-8 w-8" :disabled="refreshing" @click="refresh">
                    <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                </Button>
            </div>
        </CardHeader>

        <div v-if="showCreateForm" class="space-y-2 border-t px-4 py-3">
            <Input
                v-model="newTaskName"
                placeholder="Task name..."
                :disabled="creating"
                @keyup.enter="handleCreateTask"
            />
            <div class="flex justify-end gap-2">
                <Button variant="ghost" size="sm" @click="showCreateForm = false">Cancel</Button>
                <Button size="sm" :disabled="!newTaskName.trim() || creating" @click="handleCreateTask">
                    {{ creating ? 'Creating...' : 'Create' }}
                </Button>
            </div>
        </div>

        <CardContent class="space-y-2 pt-0">
            <Alert v-if="tasksData.error" variant="destructive">
                <AlertDescription class="flex items-center justify-between">
                    <span>{{ tasksData.error }}</span>
                    <Button variant="outline" size="sm" @click="refresh">Retry</Button>
                </AlertDescription>
            </Alert>

            <p v-else-if="tasksData.tasks.length === 0" class="text-sm text-muted-foreground">
                No tasks due today.
            </p>

            <ClickUpTaskCard
                v-for="task in tasksData.tasks"
                v-else
                :key="task.id"
                :task="task"
                :statuses="availableStatuses"
                @select="(taskId) => emit('selectTask', block.clickup_connection_id!, taskId)"
                @update-task="handleUpdateTask"
            />
        </CardContent>
    </Card>
</template>
