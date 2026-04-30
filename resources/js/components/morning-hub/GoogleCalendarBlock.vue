<script setup lang="ts">
import { CalendarX, ExternalLink, SkipForward } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import GoogleCalendarBlockSkeleton from '@/components/morning-hub/GoogleCalendarBlockSkeleton.vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { resolveBlockIcon } from '@/lib/block-icons';
import type { BlockGoogleCalendarData, RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    eventsData: BlockGoogleCalendarData | undefined;
    isActiveBlock: boolean;
    isTimerRunning: boolean;
    isTimerExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

const emit = defineEmits<{
    timerStart: [];
    timerPause: [];
    timerResume: [];
    timerReset: [];
    timerSkip: [];
}>();

const { t } = useTranslations();

const events = computed(() => props.eventsData?.events ?? []);

function formatEventTime(dateStr: string): string {
    try {
        const date = new Date(dateStr);
        return new Intl.DateTimeFormat(undefined, {
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    } catch {
        return dateStr;
    }
}
</script>

<template>
    <GoogleCalendarBlockSkeleton v-if="!eventsData" />
    <Card v-else :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-3"
        >
            <div class="flex items-center gap-2">
                <component
                    :is="resolveBlockIcon(block)"
                    class="h-4 w-4 text-muted-foreground"
                />
                <CardTitle class="text-base">{{ block.name }}</CardTitle>
                <RoutineTimerBadge
                    v-if="block.timer_minutes"
                    :timer-minutes="block.timer_minutes"
                    :is-active="isActiveBlock"
                    :is-running="isTimerRunning"
                    :is-expired="isTimerExpired"
                    :remaining-seconds="remainingSeconds"
                    :formatted-time="formattedTime"
                    @start="emit('timerStart')"
                    @pause="emit('timerPause')"
                    @resume="emit('timerResume')"
                    @reset="emit('timerReset')"
                />
            </div>
            <div class="flex items-center gap-1">
                <Button
                    v-if="isActiveBlock"
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="emit('timerSkip')"
                >
                    <SkipForward class="h-4 w-4" />
                </Button>
                <a
                    href="https://calendar.google.com"
                    target="_blank"
                    rel="noopener noreferrer"
                >
                    <Button variant="ghost" size="icon" class="h-8 w-8">
                        <ExternalLink class="h-4 w-4" />
                    </Button>
                </a>
            </div>
        </CardHeader>

        <CardContent class="space-y-1 pt-0">
            <p
                v-if="eventsData.error === 'google_calendar_auth_expired'"
                class="text-sm text-destructive"
            >
                {{ t('Token wygasł.') }}
                <RouterLink to="/morning-hub/google-calendar" class="underline">{{
                    t('Połącz ponownie')
                }}</RouterLink>
            </p>

            <p v-else-if="eventsData.error" class="text-sm text-destructive">
                {{ t('Nie udało się pobrać wydarzeń.') }}
            </p>

            <div
                v-else-if="events.length === 0"
                class="flex items-center gap-2 py-2 text-sm text-muted-foreground"
            >
                <CalendarX class="h-4 w-4" />
                {{ t('Brak wydarzeń na dziś.') }}
            </div>

            <div
                v-for="event in events"
                :key="event.id"
                class="flex items-start gap-3 rounded-md px-2 py-2 transition-colors hover:bg-muted/50"
            >
                <span
                    class="mt-1.5 h-3 w-3 shrink-0 rounded-full"
                    :style="{ backgroundColor: event.calendar_color }"
                />
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <Badge
                            v-if="event.all_day"
                            variant="secondary"
                            class="text-xs"
                        >
                            {{ t('Cały dzień') }}
                        </Badge>
                        <span v-else class="text-xs text-muted-foreground">
                            {{ formatEventTime(event.start) }} –
                            {{ formatEventTime(event.end) }}
                        </span>
                    </div>
                    <p class="mt-0.5 truncate text-sm font-medium">
                        {{ event.title }}
                    </p>
                    <p
                        v-if="event.location"
                        class="truncate text-xs text-muted-foreground"
                    >
                        {{ event.location }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
