<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Clock, RefreshCw } from 'lucide-vue-next';
import { ref } from 'vue';
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
import type { BlockTasksData, RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    tasksData: BlockTasksData | undefined;
}>();

const emit = defineEmits<{
    selectTask: [connectionId: number, taskId: string];
}>();

const refreshing = ref(false);

function refresh() {
    refreshing.value = true;
    router.reload({
        only: [`tasks_${props.block.id}`],
        onFinish: () => { refreshing.value = false; },
    });
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
            <Button variant="ghost" size="icon" class="h-8 w-8" :disabled="refreshing" @click="refresh">
                <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
            </Button>
        </CardHeader>
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
                @select="(taskId) => emit('selectTask', block.clickup_connection_id!, taskId)"
            />
        </CardContent>
    </Card>
</template>
