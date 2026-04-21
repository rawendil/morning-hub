<script setup lang="ts">
import { SkipForward } from 'lucide-vue-next';
import { computed } from 'vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useHabitsStorage } from '@/composables/useHabitsStorage';
import { useTranslations } from '@/composables/useTranslations';
import { resolveBlockIcon } from '@/lib/block-icons';
import type { RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    isActiveBlock: boolean;
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
const { state, toggle: toggleHabit } = useHabitsStorage();

const habits = computed(() => (props.block.config?.habits as string[]) ?? []);
const completed = computed(() => state.value.blocks[props.block.id] ?? []);
const progress = computed(
    () => `${completed.value.length}/${habits.value.length}`,
);
</script>

<template>
    <Card :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
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
                :key="index"
                class="flex items-center gap-3"
            >
                <Checkbox
                    :id="`habit-${block.id}-${index}`"
                    :checked="completed.includes(index)"
                    @update:checked="toggleHabit(block.id, index)"
                />
                <Label
                    :for="`habit-${block.id}-${index}`"
                    class="text-sm leading-none"
                    :class="{
                        'text-muted-foreground line-through':
                            completed.includes(index),
                    }"
                >
                    {{ habit }}
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
