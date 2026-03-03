<script setup lang="ts">
import { Clock, Pause, Play, RotateCcw } from 'lucide-vue-next';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    timerMinutes: number;
    isActive: boolean;
    isRunning: boolean;
    isExpired: boolean;
    remainingSeconds: number;
    formattedTime: string;
}>();

defineEmits<{
    start: [];
    pause: [];
    resume: [];
    reset: [];
}>();

const badgeVariant = computed(() => {
    if (!props.isActive) return 'outline' as const;
    if (props.isExpired) return 'destructive' as const;
    if (props.isRunning) return 'default' as const;
    return 'secondary' as const;
});

const displayText = computed(() => {
    if (!props.isActive) return `${props.timerMinutes}m`;
    return props.formattedTime;
});
</script>

<template>
    <div class="flex items-center gap-1">
        <Badge :variant="badgeVariant" class="gap-1">
            <Clock v-if="!isActive" class="h-3 w-3" />
            {{ displayText }}
        </Badge>

        <Button
            v-if="!isActive"
            variant="ghost"
            size="icon"
            class="h-6 w-6"
            @click="$emit('start')"
        >
            <Play class="h-3 w-3" />
        </Button>
        <template v-else>
            <Button
                v-if="isExpired"
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                @click="$emit('reset')"
            >
                <RotateCcw class="h-3 w-3" />
            </Button>
            <Button
                v-else-if="isRunning"
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                @click="$emit('pause')"
            >
                <Pause class="h-3 w-3" />
            </Button>
            <Button
                v-else
                variant="ghost"
                size="icon"
                class="h-6 w-6"
                @click="$emit('resume')"
            >
                <Play class="h-3 w-3" />
            </Button>
        </template>
    </div>
</template>
