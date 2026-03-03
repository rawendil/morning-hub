<script setup lang="ts">
import type { BlockTimerState } from '@/composables/useRoutineTimer';
import type { RoutineBlock } from '@/types';

defineProps<{
    blocks: RoutineBlock[];
    blockStates: Map<number, BlockTimerState>;
    activeBlockId: number | null;
}>();

const emit = defineEmits<{
    selectBlock: [blockId: number];
}>();

function dotClasses(blockId: number, state: BlockTimerState | undefined): string {
    switch (state) {
        case 'completed':
            return 'bg-primary';
        case 'active':
            return 'bg-primary animate-pulse';
        case 'expired':
            return 'bg-destructive';
        default:
            return 'bg-muted-foreground/30';
    }
}
</script>

<template>
    <div class="flex items-center gap-1">
        <template v-for="(block, index) in blocks" :key="block.id">
            <button
                class="h-2.5 w-2.5 shrink-0 cursor-pointer rounded-full transition-colors"
                :class="dotClasses(block.id, blockStates.get(block.id))"
                :title="block.name"
                @click="emit('selectBlock', block.id)"
            />
            <div v-if="index < blocks.length - 1" class="h-px w-3 bg-border" />
        </template>
    </div>
</template>
