<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTranslations } from '@/composables/useTranslations';
import type { ClickUpStatus, ClickUpTask, UpdateTaskPayload } from '@/types';

const props = defineProps<{
    task: ClickUpTask;
    statuses: ClickUpStatus[];
}>();

const { t } = useTranslations();

const emit = defineEmits<{
    select: [taskId: string];
    updateTask: [taskId: string, payload: UpdateTaskPayload];
}>();

const dueLabel = computed(() => {
    if (!props.task.due_date) return null;

    const due = new Date(Number(props.task.due_date));
    const now = new Date();
    const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    const dayAfter = new Date(tomorrow);
    dayAfter.setDate(dayAfter.getDate() + 1);

    if (due < today) return { text: t('Po terminie'), class: 'text-destructive border-destructive' };
    if (due < tomorrow) return { text: t('Dziś'), class: 'text-orange-600 border-orange-400' };
    if (due < dayAfter) return { text: t('Jutro'), class: '' };

    return { text: due.toLocaleDateString(), class: '' };
});
</script>

<template>
    <div class="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
        <DropdownMenu v-if="statuses.length">
            <DropdownMenuTrigger as-child>
                <button
                    class="h-2.5 w-2.5 shrink-0 cursor-pointer rounded-full ring-offset-background focus:outline-none focus:ring-2 focus:ring-ring"
                    :style="{ backgroundColor: task.status.color }"
                    :title="task.status.status"
                />
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="w-44">
                <DropdownMenuItem
                    v-for="status in statuses"
                    :key="status.status"
                    class="gap-2"
                    @click="emit('updateTask', task.id, { status: status.status })"
                >
                    <span
                        class="h-2 w-2 shrink-0 rounded-full"
                        :style="{ backgroundColor: status.color }"
                    />
                    {{ status.status }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
        <span
            v-else
            class="h-2.5 w-2.5 shrink-0 rounded-full"
            :style="{ backgroundColor: task.status.color }"
            :title="task.status.status"
        />

        <button
            class="min-w-0 flex-1 truncate text-left hover:underline"
            @click="emit('select', task.id)"
        >
            <span class="text-muted-foreground">{{ task.list.name }}: </span>{{ task.name }}
        </button>

        <div class="flex shrink-0 items-center gap-1.5">
            <Badge
                v-if="task.priority"
                variant="outline"
                class="gap-1 text-xs"
                :style="{ borderColor: task.priority.color, color: task.priority.color }"
            >
                {{ task.priority.priority }}
            </Badge>

            <Badge
                v-if="dueLabel"
                variant="outline"
                class="text-xs"
                :class="dueLabel.class"
            >
                {{ dueLabel.text }}
            </Badge>

            <a
                :href="task.url"
                target="_blank"
                rel="noopener noreferrer"
                class="text-muted-foreground hover:text-foreground"
                @click.stop
            >
                <ExternalLink class="h-3.5 w-3.5" />
            </a>
        </div>
    </div>
</template>
