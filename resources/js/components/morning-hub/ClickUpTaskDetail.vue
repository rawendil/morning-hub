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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Textarea } from '@/components/ui/textarea';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { useTranslations } from '@/composables/useTranslations';
import {
    comments as commentsRoute,
    createComment as createCommentRoute,
    task as taskRoute,
    updateTask as updateTaskRoute,
} from '@/routes/morning-hub/clickup';
import type {
    ClickUpComment,
    ClickUpTaskDetail,
    UpdateTaskPayload,
} from '@/types';

const props = defineProps<{
    connectionId: number | null;
    taskId: string | null;
}>();

const emit = defineEmits<{
    taskUpdated: [];
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const { t } = useTranslations();
const { fetchJson, postJson, putJson } = useClickUpApi();

const loading = ref(false);
const saving = ref(false);
const error = ref<string | null>(null);
const taskDetail = ref<ClickUpTaskDetail | null>(null);
const taskComments = ref<ClickUpComment[]>([]);
const newComment = ref('');
const addingComment = ref(false);

async function loadTaskDetail() {
    if (!props.connectionId || !props.taskId) return;

    loading.value = true;
    error.value = null;
    taskDetail.value = null;
    taskComments.value = [];

    try {
        const [detail, comments] = await Promise.all([
            fetchJson<ClickUpTaskDetail>(
                taskRoute.url({
                    connection: props.connectionId,
                    taskId: props.taskId,
                }),
            ),
            fetchJson<ClickUpComment[]>(
                commentsRoute.url({
                    connection: props.connectionId,
                    taskId: props.taskId,
                }),
            ),
        ]);
        taskDetail.value = detail;
        taskComments.value = comments;
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Unknown error';
    } finally {
        loading.value = false;
    }
}

watch(isOpen, (open) => {
    if (open) loadTaskDetail();
});

async function saveTaskField(payload: UpdateTaskPayload) {
    if (!props.connectionId || !props.taskId) return;
    saving.value = true;
    try {
        await putJson(
            updateTaskRoute.url({
                connection: props.connectionId,
                taskId: props.taskId,
            }),
            payload as Record<string, unknown>,
        );
        await loadTaskDetail();
        emit('taskUpdated');
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Update failed';
    } finally {
        saving.value = false;
    }
}

function handlePriorityChange(value: string) {
    const priority = value === 'none' ? null : parseInt(value);
    saveTaskField({ priority });
}

function handleDueDateChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const dueDate = input.value ? new Date(input.value).getTime() : null;
    saveTaskField({ due_date: dueDate });
}

function formatDateForInput(ms: string | null): string {
    if (!ms) return '';
    return new Date(Number(ms)).toISOString().split('T')[0];
}

async function handleAddComment() {
    if (!newComment.value.trim() || !props.connectionId || !props.taskId)
        return;
    addingComment.value = true;
    try {
        await postJson(
            createCommentRoute.url({
                connection: props.connectionId,
                taskId: props.taskId,
            }),
            { comment_text: newComment.value.trim() },
        );
        newComment.value = '';
        taskComments.value = await fetchJson<ClickUpComment[]>(
            commentsRoute.url({
                connection: props.connectionId,
                taskId: props.taskId,
            }),
        );
    } catch (e) {
        error.value = e instanceof Error ? e.message : 'Failed to add comment';
    } finally {
        addingComment.value = false;
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="max-h-[85vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle>
                    <template v-if="loading">
                        <Skeleton class="h-6 w-64" />
                    </template>
                    <template v-else-if="taskDetail">{{
                        taskDetail.name
                    }}</template>
                    <template v-else>{{ t('Szczegóły zadania') }}</template>
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
                <!-- Status + editable fields -->
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        :style="{
                            backgroundColor: taskDetail.status.color,
                            color: '#fff',
                        }"
                    >
                        {{ taskDetail.status.status }}
                    </Badge>

                    <Select
                        :model-value="
                            taskDetail.priority?.id?.toString() ?? 'none'
                        "
                        :disabled="saving"
                        @update:model-value="
                            (v) =>
                                handlePriorityChange(
                                    v == null ? 'none' : String(v),
                                )
                        "
                    >
                        <SelectTrigger class="h-7 w-32">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="1">{{ t('Pilny') }}</SelectItem>
                            <SelectItem value="2">{{ t('Wysoki') }}</SelectItem>
                            <SelectItem value="3">{{
                                t('Normalny')
                            }}</SelectItem>
                            <SelectItem value="4">{{ t('Niski') }}</SelectItem>
                            <SelectItem value="none">{{
                                t('Brak')
                            }}</SelectItem>
                        </SelectContent>
                    </Select>

                    <Input
                        type="date"
                        :model-value="formatDateForInput(taskDetail.due_date)"
                        :disabled="saving"
                        class="h-7 w-40"
                        @change="handleDueDateChange"
                    />

                    <Badge
                        v-for="tag in taskDetail.tags"
                        :key="tag.name"
                        :style="{ backgroundColor: tag.tag_bg, color: '#fff' }"
                    >
                        {{ tag.name }}
                    </Badge>
                </div>

                <!-- Description -->
                <div
                    v-if="taskDetail.description"
                    class="rounded-md border p-3 text-sm whitespace-pre-wrap"
                >
                    {{ taskDetail.description }}
                </div>
                <p v-else class="text-sm text-muted-foreground">
                    {{ t('Brak opisu.') }}
                </p>

                <!-- Subtasks -->
                <div v-if="taskDetail.subtasks?.length" class="space-y-2">
                    <h4 class="text-sm font-medium">
                        {{ t('Podzadania') }} ({{ taskDetail.subtasks.length }})
                    </h4>
                    <div
                        v-for="sub in taskDetail.subtasks"
                        :key="sub.id"
                        class="flex items-center gap-2 rounded-md border px-3 py-1.5 text-sm"
                    >
                        <span
                            class="h-2 w-2 shrink-0 rounded-full"
                            :style="{ backgroundColor: sub.status.color }"
                        />
                        <span class="truncate">{{ sub.name }}</span>
                    </div>
                </div>

                <!-- Comments -->
                <div class="space-y-3">
                    <h4 class="text-sm font-medium">
                        {{ t('Komentarze')
                        }}{{
                            taskComments.length
                                ? ` (${taskComments.length})`
                                : ''
                        }}
                    </h4>
                    <div
                        v-for="comment in taskComments"
                        :key="comment.id"
                        class="space-y-1 rounded-md border p-3 text-sm"
                    >
                        <div
                            class="flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="font-medium text-foreground">{{
                                comment.user.username
                            }}</span>
                            <span>{{
                                new Date(Number(comment.date)).toLocaleString()
                            }}</span>
                        </div>
                        <p class="whitespace-pre-wrap">
                            {{ comment.comment_text }}
                        </p>
                    </div>
                </div>

                <!-- Add Comment -->
                <div class="space-y-2 border-t pt-4">
                    <Textarea
                        v-model="newComment"
                        :placeholder="t('Napisz komentarz...')"
                        :disabled="addingComment"
                        class="min-h-20"
                    />
                    <div class="flex justify-end">
                        <Button
                            size="sm"
                            :disabled="!newComment.trim() || addingComment"
                            @click="handleAddComment"
                        >
                            {{
                                addingComment
                                    ? t('Wysyłanie...')
                                    : t('Dodaj komentarz')
                            }}
                        </Button>
                    </div>
                </div>

                <!-- Open in ClickUp -->
                <div class="pt-2">
                    <Button variant="outline" size="sm" as-child>
                        <a
                            :href="taskDetail.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="gap-2"
                        >
                            <ExternalLink class="h-4 w-4" />
                            {{ t('Otwórz w ClickUp') }}
                        </a>
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
