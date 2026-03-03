<script setup lang="ts">
import { Brain, CalendarCheck, Clock, NotebookPen, Wrench } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { BlockType, RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
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
    <Card class="border-dashed">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <component :is="config.icon" class="h-4 w-4 text-muted-foreground" />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <Badge variant="secondary">{{ config.label }}</Badge>
                <Badge v-if="block.timer_minutes" variant="outline" class="gap-1">
                    <Clock class="h-3 w-3" />
                    {{ block.timer_minutes }}m
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <p class="text-sm text-muted-foreground">Coming in Phase 3</p>
        </CardContent>
    </Card>
</template>
