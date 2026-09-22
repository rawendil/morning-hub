<script setup lang="ts">
import { SkipForward } from 'lucide-vue-next';
import { computed } from 'vue';
import BlockCompletedBadge from '@/components/morning-hub/BlockCompletedBadge.vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useDailyProgress } from '@/composables/useDailyProgress';
import { useTranslations } from '@/composables/useTranslations';
import { resolveBlockIcon } from '@/lib/block-icons';
import { toHabits } from '@/lib/habits';
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
const { isHabitCompleted, toggleHabit } = useDailyProgress();

const habits = computed(() => toHabits(props.block.config?.habits));
const completedCount = computed(
    () => habits.value.filter((habit) => isHabitCompleted(habit.id)).length,
);
const progress = computed(
    () => `${completedCount.value}/${habits.value.length}`,
);
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
            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">{{
                    progress
                }}</span>
                <Button
                    v-if="isActiveBlock"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="emit('timerSkip')"
                >
                    <SkipForward class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-2 pt-0">
            <div
                v-for="(habit, index) in habits"
                :key="habit.id || index"
                class="flex items-center gap-3"
            >
                <Checkbox
                    :id="`habit-${block.id}-${habit.id || index}`"
                    :model-value="isHabitCompleted(habit.id)"
                    :disabled="!habit.id"
                    @update:model-value="toggleHabit(block.id, habit.id)"
                />
                <Label
                    :for="`habit-${block.id}-${habit.id || index}`"
                    class="text-sm leading-none"
                    :class="{
                        'text-muted-foreground line-through': isHabitCompleted(
                            habit.id,
                        ),
                    }"
                >
                    {{ habit.label }}
                </Label>
            </div>

            <p v-if="habits.length === 0" class="text-sm text-muted-foreground">
                {{
                    t(
                        'Brak skonfigurowanych nawyków. Edytuj ten blok, aby dodać codzienne nawyki.',
                    )
                }}
            </p>
        </CardContent>
    </Card>
</template>
