<script setup lang="ts">
import { Brain, Check, Plus } from 'lucide-vue-next';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { createTask as createTaskRoute } from '@/routes/morning-hub/clickup';
import type { RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
}>();

const { postJson } = useClickUpApi();

const input = ref('');
const submitting = ref(false);
const recentTasks = ref<string[]>([]);

const listId = props.block.clickup_connection?.default_list_id;
const connectionId = props.block.clickup_connection_id;

async function handleSubmit() {
    if (!input.value.trim() || !connectionId || !listId) return;

    submitting.value = true;
    try {
        await postJson(createTaskRoute.url(connectionId), {
            list_id: listId,
            name: input.value.trim(),
        });
        recentTasks.value.unshift(input.value.trim());
        if (recentTasks.value.length > 5) recentTasks.value.pop();
        input.value = '';
    } catch {
        // Could show error
    } finally {
        submitting.value = false;
    }
}

function handleKeydown(event: KeyboardEvent) {
    if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
        event.preventDefault();
        handleSubmit();
    }
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <Brain class="h-4 w-4 text-muted-foreground" />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <Badge v-if="block.timer_minutes" variant="outline" class="gap-1">
                    {{ block.timer_minutes }}m
                </Badge>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 pt-0">
            <template v-if="connectionId && listId">
                <Textarea
                    v-model="input"
                    placeholder="Quick thought → ClickUp task..."
                    :disabled="submitting"
                    class="min-h-20"
                    @keydown="handleKeydown"
                />
                <div class="flex justify-end">
                    <Button
                        size="sm"
                        :disabled="!input.trim() || submitting"
                        @click="handleSubmit"
                    >
                        <Plus class="h-4 w-4" />
                        {{ submitting ? 'Creating...' : 'Create Task' }}
                    </Button>
                </div>

                <div v-if="recentTasks.length" class="space-y-1">
                    <p class="text-xs text-muted-foreground">Recently created</p>
                    <div
                        v-for="(task, i) in recentTasks"
                        :key="i"
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Check class="h-3.5 w-3.5 text-green-500" />
                        <span class="truncate">{{ task }}</span>
                    </div>
                </div>
            </template>

            <p v-else class="text-sm text-muted-foreground">
                Configure a ClickUp connection to start dumping thoughts as tasks.
            </p>
        </CardContent>
    </Card>
</template>
