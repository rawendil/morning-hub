<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Skeleton } from '@/components/ui/skeleton';
import { task as taskRoute } from '@/routes/morning-hub/clickup';
import type { ClickUpTaskDetail } from '@/types';

const props = defineProps<{
    connectionId: number | null;
    taskId: string | null;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const loading = ref(false);
const error = ref<string | null>(null);
const taskDetail = ref<ClickUpTaskDetail | null>(null);

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

watch(isOpen, async (open) => {
    if (!open || !props.connectionId || !props.taskId) return;

    loading.value = true;
    error.value = null;
    taskDetail.value = null;

    try {
        const url = taskRoute.url({ connection: props.connectionId, taskId: props.taskId });
        const response = await fetch(url, {
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
        });
        if (!response.ok) throw new Error('Failed to load task details');
        const json = await response.json();
        taskDetail.value = json.data;
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Unknown error';
    } finally {
        loading.value = false;
    }
});

function formatDate(ms: string | null): string {
    if (!ms) return 'None';
    return new Date(Number(ms)).toLocaleDateString();
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>
                    <template v-if="loading">
                        <Skeleton class="h-6 w-64" />
                    </template>
                    <template v-else-if="taskDetail">{{ taskDetail.name }}</template>
                    <template v-else>Task Detail</template>
                </DialogTitle>
            </DialogHeader>

            <div v-if="loading" class="space-y-4">
                <Skeleton class="h-4 w-48" />
                <Skeleton class="h-20 w-full" />
                <Skeleton class="h-4 w-32" />
            </div>

            <div v-else-if="error" class="text-sm text-destructive">
                {{ error }}
            </div>

            <div v-else-if="taskDetail" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge :style="{ backgroundColor: taskDetail.status.color, color: '#fff' }">
                        {{ taskDetail.status.status }}
                    </Badge>
                    <Badge
                        v-if="taskDetail.priority"
                        variant="outline"
                        :style="{ borderColor: taskDetail.priority.color, color: taskDetail.priority.color }"
                    >
                        {{ taskDetail.priority.priority }}
                    </Badge>
                    <Badge v-if="taskDetail.due_date" variant="outline">
                        Due: {{ formatDate(taskDetail.due_date) }}
                    </Badge>
                    <Badge v-for="tag in taskDetail.tags" :key="tag.name" :style="{ backgroundColor: tag.tag_bg, color: '#fff' }">
                        {{ tag.name }}
                    </Badge>
                </div>

                <div v-if="taskDetail.description" class="rounded-md border p-3 text-sm whitespace-pre-wrap">
                    {{ taskDetail.description }}
                </div>
                <p v-else class="text-sm text-muted-foreground">No description.</p>

                <div v-if="taskDetail.subtasks?.length" class="space-y-2">
                    <h4 class="text-sm font-medium">Subtasks ({{ taskDetail.subtasks.length }})</h4>
                    <div v-for="sub in taskDetail.subtasks" :key="sub.id" class="flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm">
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :style="{ backgroundColor: sub.status.color }"
                        />
                        <span class="truncate">{{ sub.name }}</span>
                    </div>
                </div>

                <div class="pt-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="taskDetail.url" target="_blank" rel="noopener noreferrer" class="gap-2">
                            <ExternalLink class="h-4 w-4" />
                            Open in ClickUp
                        </a>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
