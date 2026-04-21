import { computed } from 'vue';
import type { Ref } from 'vue';
import type {
    BlockGoogleCalendarData,
    BlockTodaysTasksData,
    TimelineItem,
} from '@/types';

export function useTodaysTimeline(
    todaysTasksData: Ref<BlockTodaysTasksData | undefined>,
    calendarData: Ref<BlockGoogleCalendarData | undefined>,
) {
    const timeline = computed<TimelineItem[]>(() => {
        const items: TimelineItem[] = [];

        if (todaysTasksData.value) {
            for (const group of todaysTasksData.value.groups) {
                for (const task of group.tasks) {
                    items.push({
                        type: 'task',
                        sortTime: task.start_date ? Number(task.start_date) : 0,
                        connectionId: group.connectionId,
                        task,
                        statuses: group.statuses,
                    });
                }
            }
        }

        if (calendarData.value?.events) {
            for (const event of calendarData.value.events) {
                items.push({
                    type: 'event',
                    sortTime: event.all_day
                        ? 0
                        : new Date(event.start).getTime(),
                    event,
                });
            }
        }

        items.sort((a, b) => {
            if (a.sortTime !== b.sortTime) return a.sortTime - b.sortTime;
            if (a.type !== b.type) return a.type === 'event' ? -1 : 1;
            return 0;
        });

        return items;
    });

    return { timeline };
}
