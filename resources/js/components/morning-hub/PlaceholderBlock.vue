<script setup lang="ts">
import { SkipForward } from 'lucide-vue-next';
import RoutineTimerBadge from '@/components/morning-hub/RoutineTimerBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { resolveBlockIcon } from '@/lib/block-icons';
import type { RoutineBlock } from '@/types';

defineProps<{
    block: RoutineBlock;
    isActiveBlock: boolean;
    isTimerRunning: boolean;
    isTimerExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

const { t } = useTranslations();

const emit = defineEmits<{
    timerStart: [];
    timerPause: [];
    timerResume: [];
    timerReset: [];
    timerSkip: [];
}>();
</script>

<template>
    <Card
        class="border-dashed"
        :class="{ 'ring-2 ring-primary/30': isActiveBlock }"
    >
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
            <div v-if="isActiveBlock" class="flex items-center gap-1">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-8 w-8"
                    @click="emit('timerSkip')"
                >
                    <SkipForward class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <a
                v-if="block.config?.placeholder_url"
                :href="block.config.placeholder_url as string"
                target="_blank"
                rel="noopener noreferrer"
                class="text-sm text-primary hover:underline"
            >
                {{
                    block.config?.placeholder_text ||
                    block.config.placeholder_url
                }}
            </a>
            <p
                v-else-if="block.config?.placeholder_text"
                class="text-sm text-muted-foreground"
            >
                {{ block.config.placeholder_text }}
            </p>
            <p v-else class="text-sm text-muted-foreground">
                {{ t('Wkrótce') }}
            </p>
        </CardContent>
    </Card>
</template>
