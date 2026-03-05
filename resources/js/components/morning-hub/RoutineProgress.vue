<script setup lang="ts">
import type { BlockTimerState } from '@/composables/useRoutineTimer';
import type { RoutineBlock } from '@/types';
import { Check } from 'lucide-vue-next';

defineProps<{
    blocks: RoutineBlock[];
    blockStates: Map<number, BlockTimerState>;
    activeBlockId: number | null;
}>();

const emit = defineEmits<{
    selectBlock: [blockId: number];
}>();

function dotClasses(state: BlockTimerState | undefined): string {
    switch (state) {
        case 'completed':
            return 'bg-primary text-primary-foreground';
        case 'active':
            return 'bg-primary animate-pulse';
        case 'expired':
            return 'bg-destructive';
        default:
            return 'bg-muted-foreground/30';
    }
}

function connectorClasses(state: BlockTimerState | undefined): string {
    if (state === 'completed') {
        return 'bg-primary';
    }

    return 'bg-border';
}
</script>

<template>
    <div class="flex items-center gap-1">
        <template v-for="(block, index) in blocks" :key="block.id">
            <button
                class="flex shrink-0 cursor-pointer items-center justify-center rounded-full transition-colors"
                :class="[
                    dotClasses(blockStates.get(block.id)),
                    blockStates.get(block.id) === 'completed' ? 'h-3.5 w-3.5' : 'h-2.5 w-2.5',
                ]"
                :title="block.name"
                @click="emit('selectBlock', block.id)"
            >
                <Check
                    v-if="blockStates.get(block.id) === 'completed'"
                    class="h-2.5 w-2.5"
                    :stroke-width="3"
                />
            </button>
            <div
                v-if="index < blocks.length - 1"
                class="h-px w-3 transition-colors"
                :class="connectorClasses(blockStates.get(block.id))"
            />
        </template>
    </div>
</template>
