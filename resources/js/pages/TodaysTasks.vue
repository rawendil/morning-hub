<script setup lang="ts">
import { Deferred, Head, Link, router } from '@inertiajs/vue3';
import { RefreshCw, Settings } from 'lucide-vue-next';
import { computed, ref, toRef } from 'vue';
import Heading from '@/components/Heading.vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import GoogleCalendarEventCard from '@/components/morning-hub/GoogleCalendarEventCard.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useClickUpApi } from '@/composables/useClickUpApi';
import { useTodaysTimeline } from '@/composables/useTodaysTimeline';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { todaysTasks } from '@/routes';
import { updateTask as updateTaskRoute } from '@/routes/morning-hub/clickup';
import { index as googleCalendarIndex } from '@/routes/morning-hub/google-calendar';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import type { BreadcrumbItem, BlockGoogleCalendarData, BlockTodaysTasksData, UpdateTaskPayload } from '@/types';

const { t } = useTranslations();

const props = defineProps<{
    hasConfig: boolean;
    hasCalendar: boolean;
    todaysTasksData?: BlockTodaysTasksData;
    calendarData?: BlockGoogleCalendarData;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('Zadania na dziś'), href: todaysTasks() },
]);

const { putJson } = useClickUpApi();

const refreshing = ref(false);

const detailOpen = ref(false);
const detailConnectionId = ref<number | null>(null);
const detailTaskId = ref<string | null>(null);

const { timeline } = useTodaysTimeline(
    toRef(() => props.todaysTasksData),
    toRef(() => props.calendarData),
);

function openTaskDetail(connectionId: number, taskId: string) {
    detailConnectionId.value = connectionId;
    detailTaskId.value = taskId;
    detailOpen.value = true;
}

function refresh() {
    refreshing.value = true;
    router.reload({
        only: ['todaysTasksData', 'calendarData'],
        onFinish: () => { refreshing.value = false; },
    });
}

async function handleUpdateTask(connectionId: number, taskId: string, payload: UpdateTaskPayload) {
    if (!props.todaysTasksData) return;

    const group = props.todaysTasksData.groups.find((g) => g.connectionId === connectionId);
    if (!group) return;

    const taskIndex = group.tasks.findIndex((t) => t.id === taskId);
    if (taskIndex === -1) return;

    const previousTask = { ...group.tasks[taskIndex] };

    if (payload.status) {
        const statusObj = group.statuses.find((s) => s.status === payload.status);
        group.tasks[taskIndex] = {
            ...group.tasks[taskIndex],
            status: { status: payload.status, color: statusObj?.color ?? previousTask.status.color },
        };
    }

    try {
        await putJson(
            updateTaskRoute.url({ connection: connectionId, taskId }),
            payload as Record<string, unknown>,
        );
    } catch {
        group.tasks[taskIndex] = previousTask;
    }
}

const hasAnySource = computed(() => props.hasConfig || props.hasCalendar);

const deferredProps = computed(() => {
    const parts: string[] = [];
    if (props.hasConfig) parts.push('todaysTasksData');
    if (props.hasCalendar) parts.push('calendarData');
    return parts.join(' ');
});

const allEmpty = computed(() => {
    if (!props.todaysTasksData && !props.calendarData) return false;

    const tasksEmpty = !props.todaysTasksData
        || props.todaysTasksData.groups.every((g) => g.tasks.length === 0 && !g.error);
    const eventsEmpty = !props.calendarData
        || (props.calendarData.events.length === 0 && !props.calendarData.error);

    return tasksEmpty && eventsEmpty;
});

const errorGroups = computed(() => {
    if (!props.todaysTasksData) return [];
    return props.todaysTasksData.groups.filter((g) => g.error);
});

function itemKey(item: (typeof timeline.value)[number]): string {
    return item.type === 'task' ? `task-${item.task.id}` : `event-${item.event.id}`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('Zadania na dziś')" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading :title="t('Zadania na dziś')" :description="t('Twoje zadania i wydarzenia na dziś.')" />
                <div class="flex items-center gap-2">
                    <Button v-if="hasAnySource" variant="ghost" size="icon" :disabled="refreshing" @click="refresh">
                        <RefreshCw class="h-4 w-4" :class="{ 'animate-spin': refreshing }" />
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="todaysTasksConfigIndex()">
                            <Settings class="mr-2 h-4 w-4" />
                            {{ t('Konfiguracja') }}
                        </Link>
                    </Button>
                </div>
            </div>

            <div v-if="!hasAnySource" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">
                    {{ t('Nie skonfigurowano źródeł.') }}
                    <Link :href="todaysTasksConfigIndex()" class="underline">{{ t('Skonfiguruj ClickUp') }}</Link>
                    {{ t('lub') }}
                    <Link :href="googleCalendarIndex.url()" class="underline">{{ t('połącz Google Calendar') }}</Link>.
                </p>
            </div>

            <template v-else>
                <Deferred :data="deferredProps">
                    <template #fallback>
                        <ClickUpTaskBlockSkeleton />
                    </template>

                    <div class="space-y-2">
                        <Alert v-if="calendarData?.error === 'google_calendar_auth_expired'" variant="destructive">
                            <AlertDescription>
                                {{ t('Token Google Calendar wygasł.') }}
                                <Link :href="googleCalendarIndex.url()" class="underline">{{ t('Połącz ponownie') }}</Link>
                            </AlertDescription>
                        </Alert>

                        <Alert v-else-if="calendarData?.error" variant="destructive">
                            <AlertDescription class="flex items-center justify-between">
                                <span>{{ t('Nie udało się pobrać wydarzeń z kalendarza.') }}</span>
                                <Button variant="outline" size="sm" @click="refresh">{{ t('Ponów') }}</Button>
                            </AlertDescription>
                        </Alert>

                        <template v-for="group in errorGroups" :key="group.connectionId">
                            <Alert variant="destructive">
                                <AlertDescription class="flex items-center justify-between">
                                    <span>{{ group.connectionName }}: {{ group.error }}</span>
                                    <Button variant="outline" size="sm" @click="refresh">{{ t('Ponów') }}</Button>
                                </AlertDescription>
                            </Alert>
                        </template>

                        <p v-if="allEmpty" class="text-sm text-muted-foreground">
                            {{ t('Brak zadań i wydarzeń na dziś. Dobra robota!') }}
                        </p>

                        <template v-for="item in timeline" :key="itemKey(item)">
                            <ClickUpTaskCard
                                v-if="item.type === 'task'"
                                :task="item.task"
                                :statuses="item.statuses"
                                @select="(taskId) => openTaskDetail(item.connectionId, taskId)"
                                @update-task="(taskId, payload) => handleUpdateTask(item.connectionId, taskId, payload)"
                            />
                            <GoogleCalendarEventCard
                                v-else
                                :event="item.event"
                            />
                        </template>
                    </div>
                </Deferred>
            </template>
        </div>

        <ClickUpTaskDetail
            v-model:open="detailOpen"
            :connection-id="detailConnectionId"
            :task-id="detailTaskId"
        />
    </AppLayout>
</template>
