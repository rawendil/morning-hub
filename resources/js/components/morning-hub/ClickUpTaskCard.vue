<script setup lang="ts">
import { ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { ClickUpTask } from '@/types';

const props = defineProps<{
    task: ClickUpTask;
}>();

const emit = defineEmits<{
    select: [taskId: string];
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

    if (due < today) return { text: 'Overdue', class: 'text-destructive border-destructive' };
    if (due < tomorrow) return { text: 'Today', class: 'text-orange-600 border-orange-400' };
    if (due < dayAfter) return { text: 'Tomorrow', class: '' };

    return { text: due.toLocaleDateString(), class: '' };
});
</script>

<template>
    <div class="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
        <span
            class="h-2.5 w-2.5 shrink-0 rounded-full"
            :style="{ backgroundColor: task.status.color }"
            :title="task.status.status"
        />

        <button
            class="min-w-0 flex-1 truncate text-left hover:underline"
            @click="emit('select', task.id)"
        >
            {{ task.name }}
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
