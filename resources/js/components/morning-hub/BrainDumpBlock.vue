<script setup lang="ts">
import { Check, Plus, SkipForward } from 'lucide-vue-next';
import { ref } from 'vue';
import BlockCompletedBadge from '@/components/morning-hub/BlockCompletedBadge.vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { useTranslations } from '@/composables/useTranslations';
import axiosInstance from '@/lib/axios';
import { resolveBlockIcon } from '@/lib/block-icons';
import type { RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    isActiveBlock: boolean;
    isCompleted: boolean;
    isTimerRunning: boolean;
    isTimerExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

const emit = defineEmits<{
    timerStart: [];
    timerPause: [];
    timerResume: [];
    timerReset: [];
    timerSkip: [];
}>();

const { t } = useTranslations();

const input = ref('');
const submitting = ref(false);
const recentTasks = ref<string[]>([]);

const listId = props.block.clickup_connection?.default_list_id;
const connectionId = props.block.clickup_connection_id;

async function handleSubmit() {
    if (!input.value.trim() || !connectionId || !listId) return;

    submitting.value = true;
    try {
        await axiosInstance.post(`/morning-hub/clickup/${connectionId}/tasks`, {
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
    <Card
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
            <div v-if="isActiveBlock" class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="emit('timerSkip')"
                >
                    <SkipForward class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-3 pt-0">
            <template v-if="connectionId && listId">
                <Textarea
                    v-model="input"
                    :placeholder="t('Szybka myśl → zadanie ClickUp...')"
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
                        {{
                            submitting ? t('Tworzenie...') : t('Utwórz zadanie')
                        }}
                    </Button>
                </div>

                <div v-if="recentTasks.length" class="space-y-1">
                    <p class="text-xs text-muted-foreground">
                        {{ t('Ostatnio utworzone') }}
                    </p>
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
                {{
                    t(
                        'Skonfiguruj połączenie ClickUp, aby zacząć zapisywać myśli jako zadania.',
                    )
                }}
            </p>
        </CardContent>
    </Card>
</template>
