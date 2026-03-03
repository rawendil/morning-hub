import { computed, onBeforeUnmount, ref, type ComputedRef, type Ref } from 'vue';
import type { RoutineBlock } from '@/types';

export type BlockTimerState = 'pending' | 'active' | 'expired' | 'completed';

export type UseRoutineTimerReturn = {
    activeBlockId: Ref<number | null>;
    remainingSeconds: Ref<number>;
    isRunning: Ref<boolean>;
    isExpired: ComputedRef<boolean>;
    blockStates: ComputedRef<Map<number, BlockTimerState>>;
    start: (blockId: number) => void;
    pause: () => void;
    resume: () => void;
    reset: () => void;
    skip: () => void;
    formatTime: (seconds: number) => string;
};

export function useRoutineTimer(blocks: RoutineBlock[]): UseRoutineTimerReturn {
    const activeBlockId = ref<number | null>(null);
    const remainingSeconds = ref(0);
    const isRunning = ref(false);
    const completedBlockIds = ref(new Set<number>());

    let intervalId: ReturnType<typeof setInterval> | null = null;

    function clearTimer(): void {
        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }
        isRunning.value = false;
    }

    function startInterval(): void {
        clearTimer();
        if (remainingSeconds.value <= 0) return;

        isRunning.value = true;
        intervalId = setInterval(() => {
            remainingSeconds.value--;
            if (remainingSeconds.value <= 0) {
                remainingSeconds.value = 0;
                clearTimer();
            }
        }, 1000);
    }

    function getBlock(blockId: number): RoutineBlock | undefined {
        return blocks.find((b) => b.id === blockId);
    }

    function start(blockId: number): void {
        clearTimer();
        activeBlockId.value = blockId;

        const block = getBlock(blockId);
        const minutes = block?.timer_minutes ?? 0;
        remainingSeconds.value = minutes * 60;

        if (remainingSeconds.value > 0) {
            startInterval();
        }
    }

    function pause(): void {
        clearTimer();
    }

    function resume(): void {
        if (activeBlockId.value === null || remainingSeconds.value <= 0) return;
        startInterval();
    }

    function reset(): void {
        clearTimer();
        if (activeBlockId.value === null) return;

        const block = getBlock(activeBlockId.value);
        remainingSeconds.value = (block?.timer_minutes ?? 0) * 60;
    }

    function skip(): void {
        if (activeBlockId.value !== null) {
            completedBlockIds.value.add(activeBlockId.value);
        }
        clearTimer();
        activeBlockId.value = null;
        remainingSeconds.value = 0;
    }

    const isExpired = computed(
        () => activeBlockId.value !== null && remainingSeconds.value === 0,
    );

    const blockStates = computed(() => {
        const states = new Map<number, BlockTimerState>();
        for (const block of blocks) {
            if (completedBlockIds.value.has(block.id)) {
                states.set(block.id, 'completed');
            } else if (block.id === activeBlockId.value) {
                states.set(block.id, isExpired.value ? 'expired' : 'active');
            } else {
                states.set(block.id, 'pending');
            }
        }
        return states;
    });

    function formatTime(seconds: number): string {
        const m = Math.floor(seconds / 60);
        const s = seconds % 60;
        return `${m}:${s.toString().padStart(2, '0')}`;
    }

    onBeforeUnmount(() => {
        clearTimer();
    });

    return {
        activeBlockId,
        remainingSeconds,
        isRunning,
        isExpired,
        blockStates,
        start,
        pause,
        resume,
        reset,
        skip,
        formatTime,
    };
}
