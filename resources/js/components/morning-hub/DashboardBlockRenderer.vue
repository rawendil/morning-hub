<script setup lang="ts">
import BrainDumpBlock from '@/components/morning-hub/BrainDumpBlock.vue';
import ClickUpTaskBlock from '@/components/morning-hub/ClickUpTaskBlock.vue';
import FeedBlock from '@/components/morning-hub/FeedBlock.vue';
import HabitsBlock from '@/components/morning-hub/HabitsBlock.vue';
import PlaceholderBlock from '@/components/morning-hub/PlaceholderBlock.vue';
import TodaysTasksBlock from '@/components/morning-hub/TodaysTasksBlock.vue';
import type { BlockFeedData, BlockTasksData, BlockTodaysTasksData, RoutineBlock } from '@/types';

defineProps<{
    block: RoutineBlock;
    tasksData?: BlockTasksData;
    todaysTasksData?: BlockTodaysTasksData;
    feedData?: BlockFeedData;
    isActiveBlock: boolean;
    isTimerRunning: boolean;
    isTimerExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

const emit = defineEmits<{
    selectTask: [connectionId: number, taskId: string];
    timerStart: [];
    timerPause: [];
    timerResume: [];
    timerReset: [];
    timerSkip: [];
}>();
</script>

<template>
    <ClickUpTaskBlock
        v-if="block.type === 'clickup'"
        :block="block"
        :tasks-data="tasksData"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @select-task="(connId, taskId) => emit('selectTask', connId, taskId)"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
    <BrainDumpBlock
        v-else-if="block.type === 'braindump'"
        :block="block"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
    <HabitsBlock
        v-else-if="block.type === 'habits'"
        :block="block"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
    <FeedBlock
        v-else-if="block.type === 'feed'"
        :block="block"
        :feed-data="feedData"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
    <TodaysTasksBlock
        v-else-if="block.type === 'todays_tasks'"
        :block="block"
        :todays-tasks-data="todaysTasksData"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @select-task="(connId, taskId) => emit('selectTask', connId, taskId)"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
    <PlaceholderBlock
        v-else
        :block="block"
        :is-active-block="isActiveBlock"
        :is-timer-running="isTimerRunning"
        :is-timer-expired="isTimerExpired"
        :remaining-seconds="remainingSeconds"
        :formatted-time="formattedTime"
        @timer-start="emit('timerStart')"
        @timer-pause="emit('timerPause')"
        @timer-resume="emit('timerResume')"
        @timer-reset="emit('timerReset')"
        @timer-skip="emit('timerSkip')"
    />
</template>
