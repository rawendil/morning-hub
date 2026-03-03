<script setup lang="ts">
import { ExternalLink, Rss, SkipForward } from 'lucide-vue-next';
import { computed } from 'vue';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { BlockFeedData, RoutineBlock } from '@/types';

const props = defineProps<{
    block: RoutineBlock;
    feedData: BlockFeedData | undefined;
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

const items = computed(() => props.feedData?.items ?? []);

function timeAgo(isoDate: string): string {
    const now = Date.now();
    const then = new Date(isoDate).getTime();
    const diffMs = now - then;

    const minutes = Math.floor(diffMs / 60000);
    if (minutes < 60) return `${minutes} min temu`;

    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours} godz. temu`;

    const days = Math.floor(hours / 24);
    if (days === 1) return 'wczoraj';

    return `${days} dni temu`;
}
</script>

<template>
    <Card :class="{ 'ring-2 ring-primary/30': isActiveBlock }">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-2">
                <Rss class="h-4 w-4 text-muted-foreground" />
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
            <div class="flex items-center gap-2">
                <span v-if="items.length" class="text-xs text-muted-foreground">{{ items.length }} artykułów</span>
                <Button v-if="isActiveBlock" variant="ghost" size="icon" class="h-8 w-8" @click="emit('timerSkip')">
                    <SkipForward class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>

        <CardContent class="space-y-1 pt-0">
            <p v-if="feedData?.error" class="text-sm text-destructive">
                {{ feedData.error }}
            </p>

            <p v-else-if="items.length === 0" class="text-sm text-muted-foreground">
                Brak artykułów. Sprawdź źródła RSS lub zwiększ zakres dni.
            </p>

            <a
                v-for="(item, index) in items"
                :key="index"
                :href="item.link"
                target="_blank"
                rel="noopener noreferrer"
                class="flex items-start gap-3 rounded-md px-2 py-2 transition-colors hover:bg-muted/50"
            >
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary" class="shrink-0 text-xs">{{ item.source }}</Badge>
                        <span class="text-xs text-muted-foreground">{{ timeAgo(item.published_at) }}</span>
                    </div>
                    <p class="mt-0.5 truncate text-sm font-medium">{{ item.title }}</p>
                </div>
                <ExternalLink class="mt-1 h-3.5 w-3.5 shrink-0 text-muted-foreground" />
            </a>
        </CardContent>
    </Card>
</template>
