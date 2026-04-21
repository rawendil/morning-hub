<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/composables/useTranslations';
import type { GoogleCalendarEvent } from '@/types';

defineProps<{
    event: GoogleCalendarEvent;
}>();

const { t } = useTranslations();

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
    <div class="flex items-center gap-3 rounded-md border px-3 py-2 text-sm">
        <span
            class="h-2.5 w-2.5 shrink-0 rounded-full"
            :style="{ backgroundColor: event.calendar_color }"
        />

        <span class="min-w-0 flex-1 truncate">
            {{ event.title }}
        </span>

        <div class="flex shrink-0 items-center gap-1.5">
            <Badge v-if="event.all_day" variant="secondary" class="text-xs">
                {{ t('Cały dzień') }}
            </Badge>
            <Badge v-else variant="outline" class="text-xs">
                {{ formatEventTime(event.start) }} –
                {{ formatEventTime(event.end) }}
            </Badge>

            <Badge
                v-if="event.location"
                variant="outline"
                class="max-w-32 truncate text-xs text-muted-foreground"
            >
                {{ event.location }}
            </Badge>
        </div>
    </div>
</template>
