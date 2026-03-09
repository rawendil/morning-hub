<script setup lang="ts">
import { Deferred, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { useTranslations } from '@/composables/useTranslations';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import FeedBlockSkeleton from '@/components/morning-hub/FeedBlockSkeleton.vue';
import OnboardingModal from '@/components/morning-hub/OnboardingModal.vue';
import RoutineCompletionDialog from '@/components/morning-hub/RoutineCompletionDialog.vue';
import DashboardBlockRenderer from '@/components/morning-hub/DashboardBlockRenderer.vue';
import Heading from '@/components/Heading.vue';
import RoutineProgress from '@/components/morning-hub/RoutineProgress.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { useRoutineTimer } from '@/composables/useRoutineTimer';
import { dashboard } from '@/routes';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import type { BreadcrumbItem, BlockFeedData, BlockTasksData, RoutineBlock } from '@/types';

const { t } = useTranslations();

const props = defineProps<{
    blocks: RoutineBlock[];
}>();

const page = usePage();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('Panel'), href: dashboard() },
]);

function getTasksData(blockId: number): BlockTasksData | undefined {
    return (page.props as Record<string, unknown>)[`tasks_${blockId}`] as BlockTasksData | undefined;
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
    completedElapsedSeconds,
    start,
    pause,
    resume,
    reset,
    skip,
    formatTime,
} = useRoutineTimer(props.blocks);

const completedElapsedMinutes = computed(() => Math.floor(completedElapsedSeconds.value / 60));

const hasTimers = computed(() => props.blocks.some((b) => b.timer_minutes));
const totalMinutes = computed(() => props.blocks.reduce((sum, b) => sum + (b.timer_minutes ?? 0), 0));

const routineCompleteOpen = ref(false);

const allBlocksCompleted = computed(
    () => props.blocks.length > 0 && [...blockStates.value.values()].every((s) => s === 'completed'),
);

watch(
    blockStates,
    (newStates, oldStates) => {
        for (const [blockId, state] of newStates) {
            if (state === 'completed' && oldStates?.get(blockId) !== 'completed') {
                const block = props.blocks.find((b) => b.id === blockId);
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
        <Head :title="t('Panel')" />

        <div class="space-y-6 p-6">
            <Heading title="Morning Hub" :description="t('Twój codzienny panel rutyny.')" />

            <div v-if="blocks.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">
                    {{ t('Brak skonfigurowanych bloków rutyny.') }}
                    <Link :href="routineIndex.url()" class="underline">{{ t('Przejdź do Porannej Rutyny') }}</Link>,
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
                    <span class="text-sm text-muted-foreground">{{ completedElapsedMinutes }} / {{ totalMinutes }} min</span>
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

        <RoutineCompletionDialog
            v-model:open="routineCompleteOpen"
            :completed-minutes="completedElapsedMinutes"
            :total-blocks="props.blocks.filter((b) => blockStates.get(b.id) === 'completed').length"
        />
    </AppLayout>
</template>
