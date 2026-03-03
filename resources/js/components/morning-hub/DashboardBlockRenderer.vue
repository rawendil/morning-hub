<script setup lang="ts">
import ClickUpTaskBlock from '@/components/morning-hub/ClickUpTaskBlock.vue';
import PlaceholderBlock from '@/components/morning-hub/PlaceholderBlock.vue';
import type { BlockTasksData, RoutineBlock } from '@/types';

defineProps<{
    block: RoutineBlock;
    tasksData: BlockTasksData | undefined;
}>();

const emit = defineEmits<{
    selectTask: [connectionId: number, taskId: string];
}>();
</script>

<template>
    <ClickUpTaskBlock
        v-if="block.type === 'clickup'"
        :block="block"
        :tasks-data="tasksData"
        @select-task="(connId, taskId) => emit('selectTask', connId, taskId)"
    />
    <PlaceholderBlock v-else :block="block" />
</template>
