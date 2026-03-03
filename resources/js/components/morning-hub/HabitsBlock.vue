<script setup lang="ts">
import { ListChecks, SkipForward } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import HabitToggleController from '@/actions/App/Http/Controllers/MorningHub/HabitToggleController';
import type { RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    completedIndices: number[];
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

const habits = computed(() => (props.block.config?.habits as string[]) ?? []);
const completed = ref<number[]>([...props.completedIndices]);

const progress = computed(() => `${completed.value.length}/${habits.value.length}`);

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function toggle(index: number) {
    const was = completed.value.includes(index);

    // Optimistic toggle
    if (was) {
        completed.value = completed.value.filter((i) => i !== index);
    } else {
        completed.value = [...completed.value, index];
    }

    try {
        const response = await fetch(HabitToggleController.url(props.block.id), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ index }),
        });

        if (response.ok) {
            const data = await response.json();
            completed.value = data.completed;
        } else {
            // Rollback
            if (was) {
                completed.value = [...completed.value, index];
            } else {
                completed.value = completed.value.filter((i) => i !== index);
            }
        }
    } catch {
        // Rollback
        if (was) {
            completed.value = [...completed.value, index];
        } else {
            completed.value = completed.value.filter((i) => i !== index);
        }
    }
}
</script>

<template>
    <Card :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <ListChecks class="h-4 w-4 text-muted-foreground" />
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
                <span class="text-xs text-muted-foreground">{{ progress }}</span>
                <Button v-if="isActiveBlock" variant="ghost" size="icon" class="h-8 w-8" @click="emit('timerSkip')">
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
                    @update:checked="toggle(index)"
                />
                <Label
                    :for="`habit-${block.id}-${index}`"
                    class="text-sm leading-none"
                    :class="{ 'text-muted-foreground line-through': completed.includes(index) }"
                >
                    {{ habit }}
                </Label>
            </div>

            <p v-if="habits.length === 0" class="text-sm text-muted-foreground">
                Brak skonfigurowanych nawyków. Edytuj ten blok, aby dodać codzienne nawyki.
            </p>
        </CardContent>
    </Card>
</template>
