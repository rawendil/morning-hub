<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import DashboardBlockRenderer from '@/components/morning-hub/DashboardBlockRenderer.vue';
import FeedBlockSkeleton from '@/components/morning-hub/FeedBlockSkeleton.vue';
import GoogleCalendarBlockSkeleton from '@/components/morning-hub/GoogleCalendarBlockSkeleton.vue';
import OnboardingModal from '@/components/morning-hub/OnboardingModal.vue';
import RoutineCompletionDialog from '@/components/morning-hub/RoutineCompletionDialog.vue';
import RoutineProgress from '@/components/morning-hub/RoutineProgress.vue';
import { Skeleton } from '@/components/ui/skeleton';
import { useRoutineTimer } from '@/composables/useRoutineTimer';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type {
    BreadcrumbItem,
    BlockFeedData,
    BlockGoogleCalendarData,
    BlockTasksData,
    RoutineBlock,
} from '@/types';

const { t } = useTranslations();

const loading = ref(true);
const blocks = ref<RoutineBlock[]>([]);
const blockTasksData = ref<Record<number, BlockTasksData>>({});
const blockFeedData = ref<Record<number, BlockFeedData>>({});
const blockEventsData = ref<Record<number, BlockGoogleCalendarData>>({});

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('Panel'), href: '/dashboard' },
]);

function getTasksData(blockId: number): BlockTasksData | undefined {
    return blockTasksData.value[blockId];
}

function getFeedData(blockId: number): BlockFeedData | undefined {
    return blockFeedData.value[blockId];
}

function getEventsData(blockId: number): BlockGoogleCalendarData | undefined {
    return blockEventsData.value[blockId];
}

onMounted(async () => {
    try {
        const { data } = await axiosInstance.get('/dashboard');
        blocks.value = data.blocks ?? [];

        if (data.blocks_data) {
            for (const [key, value] of Object.entries(data.blocks_data)) {
                const match = key.match(/^tasks_(\d+)$/);
                if (match) {
                    blockTasksData.value[Number(match[1])] =
                        value as BlockTasksData;
                    continue;
                }
                const feedMatch = key.match(/^feed_(\d+)$/);
                if (feedMatch) {
                    blockFeedData.value[Number(feedMatch[1])] =
                        value as BlockFeedData;
                    continue;
                }
                const eventsMatch = key.match(/^events_(\d+)$/);
                if (eventsMatch) {
                    blockEventsData.value[Number(eventsMatch[1])] =
                        value as BlockGoogleCalendarData;
                }
            }
        }
    } finally {
        loading.value = false;
    }
});

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
    completedElapsedSeconds,
    start,
    pause,
    resume,
    reset,
    skip,
    formatTime,
} = useRoutineTimer(blocks);

const completedElapsedMinutes = computed(() =>
    Math.floor(completedElapsedSeconds.value / 60),
);

const hasTimers = computed(() => blocks.value.some((b) => b.timer_minutes));
const totalMinutes = computed(() =>
    blocks.value.reduce((sum, b) => sum + (b.timer_minutes ?? 0), 0),
);

const routineCompleteOpen = ref(false);

const allBlocksCompleted = computed(
    () =>
        blocks.value.length > 0 &&
        [...blockStates.value.values()].every((s) => s === 'completed'),
);

watch(
    blockStates,
    (newStates, oldStates) => {
        for (const [blockId, state] of newStates) {
            if (
                state === 'completed' &&
                oldStates?.get(blockId) !== 'completed'
            ) {
                const block = blocks.value.find((b) => b.id === blockId);
                if (block) {
                    toast.success(`${block.name} — ukończono! ✓`);
                }
            }
        }
    },
    { immediate: false },
);

watch(
    allBlocksCompleted,
    (isComplete) => {
        if (isComplete) {
            routineCompleteOpen.value = true;
        }
    },
    { immediate: false },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <Heading
                title="Morning Hub"
                :description="t('Twój codzienny panel rutyny.')"
            />

            <div v-if="loading" class="space-y-3">
                <Skeleton class="h-24 w-full rounded-lg" />
                <Skeleton class="h-24 w-full rounded-lg" />
                <Skeleton class="h-24 w-full rounded-lg" />
            </div>

            <div
                v-else-if="blocks.length === 0"
                class="rounded-lg border border-dashed p-8 text-center"
            >
                <p class="text-muted-foreground">
                    {{ t('Brak skonfigurowanych bloków rutyny.') }}
                    <RouterLink to="/morning-hub/routine" class="underline">{{
                        t('Przejdź do Porannej Rutyny')
                    }}</RouterLink
                    >,
                    {{ t('aby skonfigurować bloki.') }}
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
                    <span class="text-sm text-muted-foreground"
                        >{{ completedElapsedMinutes }} /
                        {{ totalMinutes }} min</span
                    >
                </div>

                <div class="grid gap-4">
                    <template v-for="block in blocks" :key="block.id">
                        <template
                            v-if="
                                block.type === 'clickup' &&
                                block.clickup_connection_id
                            "
                        >
                            <ClickUpTaskBlockSkeleton
                                v-if="!getTasksData(block.id)"
                            />
                            <DashboardBlockRenderer
                                v-else
                                :block="block"
                                :tasks-data="getTasksData(block.id)"
                                :is-active-block="activeBlockId === block.id"
                                :is-timer-running="
                                    activeBlockId === block.id && isRunning
                                "
                                :is-timer-expired="
                                    activeBlockId === block.id && isExpired
                                "
                                :remaining-seconds="
                                    activeBlockId === block.id
                                        ? remainingSeconds
                                        : 0
                                "
                                :formatted-time="
                                    activeBlockId === block.id
                                        ? formatTime(remainingSeconds)
                                        : ''
                                "
                                @select-task="openTaskDetail"
                                @timer-start="start(block.id)"
                                @timer-pause="pause()"
                                @timer-resume="resume()"
                                @timer-reset="reset()"
                                @timer-skip="skip()"
                            />
                        </template>
                        <template
                            v-else-if="
                                block.type === 'feed' &&
                                (block.config?.sources as unknown[] | undefined)
                                    ?.length
                            "
                        >
                            <FeedBlockSkeleton v-if="!getFeedData(block.id)" />
                            <DashboardBlockRenderer
                                v-else
                                :block="block"
                                :feed-data="getFeedData(block.id)"
                                :is-active-block="activeBlockId === block.id"
                                :is-timer-running="
                                    activeBlockId === block.id && isRunning
                                "
                                :is-timer-expired="
                                    activeBlockId === block.id && isExpired
                                "
                                :remaining-seconds="
                                    activeBlockId === block.id
                                        ? remainingSeconds
                                        : 0
                                "
                                :formatted-time="
                                    activeBlockId === block.id
                                        ? formatTime(remainingSeconds)
                                        : ''
                                "
                                @select-task="openTaskDetail"
                                @timer-start="start(block.id)"
                                @timer-pause="pause()"
                                @timer-resume="resume()"
                                @timer-reset="reset()"
                                @timer-skip="skip()"
                            />
                        </template>
                        <template
                            v-else-if="
                                block.type === 'google_calendar' &&
                                block.google_calendar_connection_id
                            "
                        >
                            <GoogleCalendarBlockSkeleton
                                v-if="!getEventsData(block.id)"
                            />
                            <DashboardBlockRenderer
                                v-else
                                :block="block"
                                :events-data="getEventsData(block.id)"
                                :is-active-block="activeBlockId === block.id"
                                :is-timer-running="
                                    activeBlockId === block.id && isRunning
                                "
                                :is-timer-expired="
                                    activeBlockId === block.id && isExpired
                                "
                                :remaining-seconds="
                                    activeBlockId === block.id
                                        ? remainingSeconds
                                        : 0
                                "
                                :formatted-time="
                                    activeBlockId === block.id
                                        ? formatTime(remainingSeconds)
                                        : ''
                                "
                                @select-task="openTaskDetail"
                                @timer-start="start(block.id)"
                                @timer-pause="pause()"
                                @timer-resume="resume()"
                                @timer-reset="reset()"
                                @timer-skip="skip()"
                            />
                        </template>
                        <DashboardBlockRenderer
                            v-else
                            :block="block"
                            :feed-data="getFeedData(block.id)"
                            :is-active-block="activeBlockId === block.id"
                            :is-timer-running="
                                activeBlockId === block.id && isRunning
                            "
                            :is-timer-expired="
                                activeBlockId === block.id && isExpired
                            "
                            :remaining-seconds="
                                activeBlockId === block.id
                                    ? remainingSeconds
                                    : 0
                            "
                            :formatted-time="
                                activeBlockId === block.id
                                    ? formatTime(remainingSeconds)
                                    : ''
                            "
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

        <RoutineCompletionDialog
            v-model:open="routineCompleteOpen"
            :completed-minutes="completedElapsedMinutes"
            :total-blocks="
                blocks.filter((b) => blockStates.get(b.id) === 'completed')
                    .length
            "
        />
    </AppLayout>
</template>
