<script setup lang="ts">
import { Brain, CalendarCheck, NotebookPen, SkipForward, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BlockType, RoutineBlock } from '@/types';

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

const typeConfig: Record<Exclude<BlockType, 'clickup'>, { icon: typeof Brain; label: string }> = {
    braindump: { icon: Brain, label: 'Brain Dump' },
    notes: { icon: NotebookPen, label: 'Notes' },
    plan: { icon: CalendarCheck, label: 'Plan' },
    custom: { icon: Wrench, label: 'Custom' },
};

const config = computed(() => typeConfig[props.block.type as Exclude<BlockType, 'clickup'>] ?? typeConfig.custom);
</script>

<template>
    <Card class="border-dashed" :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <component :is="config.icon" class="h-4 w-4 text-muted-foreground" />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <Badge variant="secondary">{{ config.label }}</Badge>
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
            <div v-if="isActiveBlock" class="flex items-center gap-1">
                <Button variant="ghost" size="icon" class="h-8 w-8" @click="emit('timerSkip')">
                    <SkipForward class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <p class="text-sm text-muted-foreground">Coming soon</p>
        </CardContent>
    </Card>
</template>
