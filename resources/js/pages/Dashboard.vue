<script setup lang="ts">
import { Deferred, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import FeedBlockSkeleton from '@/components/morning-hub/FeedBlockSkeleton.vue';
import OnboardingModal from '@/components/morning-hub/OnboardingModal.vue';
import DashboardBlockRenderer from '@/components/morning-hub/DashboardBlockRenderer.vue';
import Heading from '@/components/Heading.vue';
import RoutineProgress from '@/components/morning-hub/RoutineProgress.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useRoutineTimer } from '@/composables/useRoutineTimer';
import { dashboard } from '@/routes';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import type { BreadcrumbItem, BlockFeedData, BlockTasksData, RoutineBlock } from '@/types';

const props = defineProps<{
    blocks: RoutineBlock[];
}>();

const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Panel', href: dashboard() },
];

function getTasksData(blockId: number): BlockTasksData | undefined {
    return (page.props as Record<string, unknown>)[`tasks_${blockId}`] as BlockTasksData | undefined;
}

function getHabitsData(blockId: number): number[] {
    return ((page.props as Record<string, unknown>)[`habits_${blockId}`] as number[]) ?? [];
}

function getFeedData(blockId: number): BlockFeedData | undefined {
    return (page.props as Record<string, unknown>)[`feed_${blockId}`] as BlockFeedData | undefined;
}

const detailOpen = ref(false);
const detailConnectionId = ref<number | null>(null);
const detailTaskId = ref<string | null>(null);

function openTaskDetail(connectionId: number, taskId: string) {
    detailConnectionId.value = connectionId;
    detailTaskId.value = taskId;
    detailOpen.value = true;
}

const {
    activeBlockId,
    remainingSeconds,
    isRunning,
    isExpired,
    blockStates,
    start,
    pause,
    resume,
    reset,
    skip,
    formatTime,
} = useRoutineTimer(props.blocks);

const hasTimers = computed(() => props.blocks.some((b) => b.timer_minutes));
const totalMinutes = computed(() => props.blocks.reduce((sum, b) => sum + (b.timer_minutes ?? 0), 0));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Panel" />

        <div class="space-y-6 p-6">
            <Heading title="Morning Hub" description="Twój codzienny panel rutyny." />

            <div v-if="blocks.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">
                    Brak skonfigurowanych bloków rutyny.
                    <Link :href="routineIndex.url()" class="underline">Przejdź do Porannej Rutyny</Link>,
                    aby skonfigurować bloki.
                </p>
            </div>

            <template v-else>
                <div v-if="hasTimers" class="flex items-center justify-between">
                    <RoutineProgress
                        :blocks="blocks"
                        :block-states="blockStates"
                        :active-block-id="activeBlockId"
                        @select-block="(id) => start(id)"
                    />
                    <span class="text-sm text-muted-foreground">{{ totalMinutes }} min</span>
                </div>

                <div class="grid gap-4">
                    <template v-for="block in blocks" :key="block.id">
                        <Deferred
                            v-if="block.type === 'clickup' && block.clickup_connection_id"
                            :data="`tasks_${block.id}`"
                        >
                            <template #fallback>
                                <ClickUpTaskBlockSkeleton />
                            </template>
                            <DashboardBlockRenderer
                                :block="block"
                                :tasks-data="getTasksData(block.id)"
                                :completed-indices="getHabitsData(block.id)"
                                :is-active-block="activeBlockId === block.id"
                                :is-timer-running="activeBlockId === block.id && isRunning"
                                :is-timer-expired="activeBlockId === block.id && isExpired"
                                :remaining-seconds="activeBlockId === block.id ? remainingSeconds : 0"
                                :formatted-time="activeBlockId === block.id ? formatTime(remainingSeconds) : ''"
                                @select-task="openTaskDetail"
                                @timer-start="start(block.id)"
                                @timer-pause="pause()"
                                @timer-resume="resume()"
                                @timer-reset="reset()"
                                @timer-skip="skip()"
                            />
                        </Deferred>
                        <Deferred
                            v-else-if="block.type === 'feed' && block.config?.sources?.length"
                            :data="`feed_${block.id}`"
                        >
                            <template #fallback>
                                <FeedBlockSkeleton />
                            </template>
                            <DashboardBlockRenderer
                                :block="block"
                                :feed-data="getFeedData(block.id)"
                                :completed-indices="getHabitsData(block.id)"
                                :is-active-block="activeBlockId === block.id"
                                :is-timer-running="activeBlockId === block.id && isRunning"
                                :is-timer-expired="activeBlockId === block.id && isExpired"
                                :remaining-seconds="activeBlockId === block.id ? remainingSeconds : 0"
                                :formatted-time="activeBlockId === block.id ? formatTime(remainingSeconds) : ''"
                                @select-task="openTaskDetail"
                                @timer-start="start(block.id)"
                                @timer-pause="pause()"
                                @timer-resume="resume()"
                                @timer-reset="reset()"
                                @timer-skip="skip()"
                            />
                        </Deferred>
                        <DashboardBlockRenderer
                            v-else
                            :block="block"
                            :feed-data="getFeedData(block.id)"
                            :completed-indices="getHabitsData(block.id)"
                            :is-active-block="activeBlockId === block.id"
                            :is-timer-running="activeBlockId === block.id && isRunning"
                            :is-timer-expired="activeBlockId === block.id && isExpired"
                            :remaining-seconds="activeBlockId === block.id ? remainingSeconds : 0"
                            :formatted-time="activeBlockId === block.id ? formatTime(remainingSeconds) : ''"
                            @select-task="openTaskDetail"
                            @timer-start="start(block.id)"
                            @timer-pause="pause()"
                            @timer-resume="resume()"
                            @timer-reset="reset()"
                            @timer-skip="skip()"
                        />
                    </template>
                </div>
            </template>
        </div>

        <ClickUpTaskDetail
            v-model:open="detailOpen"
            :connection-id="detailConnectionId"
            :task-id="detailTaskId"
        />

        <OnboardingModal />
    </AppLayout>
</template>
