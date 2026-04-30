<script setup lang="ts">
import { RefreshCw, Settings } from 'lucide-vue-next';
import { computed, onMounted, ref, toRef } from 'vue';
import { RouterLink } from 'vue-router';
import Heading from '@/components/Heading.vue';
import ClickUpTaskBlockSkeleton from '@/components/morning-hub/ClickUpTaskBlockSkeleton.vue';
import ClickUpTaskCard from '@/components/morning-hub/ClickUpTaskCard.vue';
import ClickUpTaskDetail from '@/components/morning-hub/ClickUpTaskDetail.vue';
import GoogleCalendarEventCard from '@/components/morning-hub/GoogleCalendarEventCard.vue';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useTodaysTimeline } from '@/composables/useTodaysTimeline';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type {
    BreadcrumbItem,
    BlockGoogleCalendarData,
    BlockTodaysTasksData,
    UpdateTaskPayload,
} from '@/types';

const { t } = useTranslations();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: t('Zadania na dziś'), href: '/todays-tasks' },
]);

const hasConfig = ref(false);
const hasCalendar = ref(false);
const todaysTasksData = ref<BlockTodaysTasksData | undefined>(undefined);
const calendarData = ref<BlockGoogleCalendarData | undefined>(undefined);
const loading = ref(false);
const refreshing = ref(false);

const detailOpen = ref(false);
const detailConnectionId = ref<number | null>(null);
const detailTaskId = ref<string | null>(null);

const { timeline } = useTodaysTimeline(
    toRef(todaysTasksData),
    toRef(calendarData),
);

async function loadData() {
    const { data } = await axiosInstance.get('/todays-tasks');
    hasConfig.value = data.hasConfig ?? false;
    hasCalendar.value = data.hasCalendar ?? false;
    todaysTasksData.value = data.todaysTasksData ?? undefined;
    calendarData.value = data.calendarData ?? undefined;
}

onMounted(async () => {
    loading.value = true;
    try {
        await loadData();
    } finally {
        loading.value = false;
    }
});

function openTaskDetail(connectionId: number, taskId: string) {
    detailConnectionId.value = connectionId;
    detailTaskId.value = taskId;
    detailOpen.value = true;
}

async function refresh() {
    refreshing.value = true;
    try {
        await loadData();
    } finally {
        refreshing.value = false;
    }
}

async function handleUpdateTask(
    connectionId: number,
    taskId: string,
    payload: UpdateTaskPayload,
) {
    if (!todaysTasksData.value) return;

    const group = todaysTasksData.value.groups.find(
        (g) => g.connectionId === connectionId,
    );
    if (!group) return;

    const taskIndex = group.tasks.findIndex((t) => t.id === taskId);
    if (taskIndex === -1) return;

    const previousTask = { ...group.tasks[taskIndex] };

    if (payload.status) {
        const statusObj = group.statuses.find(
            (s) => s.status === payload.status,
        );
        group.tasks[taskIndex] = {
            ...group.tasks[taskIndex],
            status: {
                status: payload.status,
                color: statusObj?.color ?? previousTask.status.color,
            },
        };
    }

    try {
        await axiosInstance.put(
            `/morning-hub/clickup/${connectionId}/tasks/${taskId}`,
            payload as Record<string, unknown>,
        );
    } catch {
        group.tasks[taskIndex] = previousTask;
    }
}

const hasAnySource = computed(() => hasConfig.value || hasCalendar.value);

const allEmpty = computed(() => {
    if (!todaysTasksData.value && !calendarData.value) return false;

    const tasksEmpty =
        !todaysTasksData.value ||
        todaysTasksData.value.groups.every(
            (g) => g.tasks.length === 0 && !g.error,
        );
    const eventsEmpty =
        !calendarData.value ||
        (calendarData.value.events.length === 0 && !calendarData.value.error);

    return tasksEmpty && eventsEmpty;
});

const errorGroups = computed(() => {
    if (!todaysTasksData.value) return [];
    return todaysTasksData.value.groups.filter((g) => g.error);
});

function itemKey(item: (typeof timeline.value)[number]): string {
    return item.type === 'task'
        ? `task-${item.task.id}`
        : `event-${item.event.id}`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading
                    :title="t('Zadania na dziś')"
                    :description="t('Twoje zadania i wydarzenia na dziś.')"
                />
                <div class="flex items-center gap-2">
                    <Button
                        v-if="hasAnySource"
                        variant="ghost"
                        size="icon"
                        :disabled="refreshing"
                        @click="refresh"
                    >
                        <RefreshCw
                            class="h-4 w-4"
                            :class="{ 'animate-spin': refreshing }"
                        />
                    </Button>
                    <Button variant="outline" size="sm" as-child>
                        <RouterLink to="/morning-hub/todays-tasks">
                            <Settings class="mr-2 h-4 w-4" />
                            {{ t('Konfiguracja') }}
                        </RouterLink>
                    </Button>
                </div>
            </div>

            <ClickUpTaskBlockSkeleton v-if="loading" />

            <template v-else>
                <div
                    v-if="!hasAnySource"
                    class="rounded-lg border border-dashed p-8 text-center"
                >
                    <p class="text-muted-foreground">
                        {{ t('Nie skonfigurowano źródeł.') }}
                        <RouterLink
                            to="/morning-hub/todays-tasks"
                            class="underline"
                            >{{ t('Skonfiguruj ClickUp') }}</RouterLink
                        >
                        {{ t('lub') }}
                        <RouterLink
                            to="/morning-hub/google-calendar"
                            class="underline"
                            >{{ t('połącz Google Calendar') }}</RouterLink
                        >.
                    </p>
                </div>

                <div v-else class="space-y-2">
                    <Alert
                        v-if="
                            calendarData?.error ===
                            'google_calendar_auth_expired'
                        "
                        variant="destructive"
                    >
                        <AlertDescription>
                            {{ t('Token Google Calendar wygasł.') }}
                            <RouterLink
                                to="/morning-hub/google-calendar"
                                class="underline"
                                >{{ t('Połącz ponownie') }}</RouterLink
                            >
                        </AlertDescription>
                    </Alert>

                    <Alert
                        v-else-if="calendarData?.error"
                        variant="destructive"
                    >
                        <AlertDescription
                            class="flex items-center justify-between"
                        >
                            <span>{{
                                t(
                                    'Nie udało się pobrać wydarzeń z kalendarza.',
                                )
                            }}</span>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="refresh"
                                >{{ t('Ponów') }}</Button
                            >
                        </AlertDescription>
                    </Alert>

                    <template
                        v-for="group in errorGroups"
                        :key="group.connectionId"
                    >
                        <Alert variant="destructive">
                            <AlertDescription
                                class="flex items-center justify-between"
                            >
                                <span
                                    >{{ group.connectionName }}:
                                    {{ group.error }}</span
                                >
                                <Button
                                    variant="outline"
                                    size="sm"
                                    @click="refresh"
                                    >{{ t('Ponów') }}</Button
                                >
                            </AlertDescription>
                        </Alert>
                    </template>

                    <p
                        v-if="allEmpty"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            t(
                                'Brak zadań i wydarzeń na dziś. Dobra robota!',
                            )
                        }}
                    </p>

                    <template v-for="item in timeline" :key="itemKey(item)">
                        <ClickUpTaskCard
                            v-if="item.type === 'task'"
                            :task="item.task"
                            :statuses="item.statuses"
                            @select="
                                (taskId) =>
                                    openTaskDetail(item.connectionId, taskId)
                            "
                            @update-task="
                                (taskId, payload) =>
                                    handleUpdateTask(
                                        item.connectionId,
                                        taskId,
                                        payload,
                                    )
                            "
                        />
                        <GoogleCalendarEventCard
                            v-else
                            :event="item.event"
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
    </AppLayout>
</template>
