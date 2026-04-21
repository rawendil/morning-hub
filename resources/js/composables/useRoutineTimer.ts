import { computed, onBeforeUnmount, ref } from 'vue';
import type { ComputedRef, Ref } from 'vue';
import type { RoutineBlock } from '@/types';

export type BlockTimerState = 'pending' | 'active' | 'expired' | 'completed';

export type UseRoutineTimerReturn = {
    activeBlockId: Ref<number | null>;
    remainingSeconds: Ref<number>;
    isRunning: Ref<boolean>;
    isExpired: ComputedRef<boolean>;
    blockStates: ComputedRef<Map<number, BlockTimerState>>;
    completedElapsedSeconds: ComputedRef<number>;
    start: (blockId: number) => void;
    pause: () => void;
    resume: () => void;
    reset: () => void;
    skip: () => void;
    formatTime: (seconds: number) => string;
};

type StoredTimerState = {
    date: string;
    activeBlockId: number | null;
    remainingSeconds: number;
    completedBlockIds: number[];
    elapsedSeconds: Record<number, number>;
};

const STORAGE_KEY = 'morning-hub-timer-state';

function todayString(): string {
    return new Date().toISOString().slice(0, 10);
}

function loadState(): StoredTimerState | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return null;
        }

        const parsed: StoredTimerState = JSON.parse(raw);
        if (parsed.date !== todayString()) {
            localStorage.removeItem(STORAGE_KEY);
            return null;
        }

        return parsed;
    } catch {
        return null;
    }
}

function persistState(state: StoredTimerState): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

export function useRoutineTimer(blocks: RoutineBlock[]): UseRoutineTimerReturn {
    const stored = loadState();

    const activeBlockId = ref<number | null>(stored?.activeBlockId ?? null);
    const remainingSeconds = ref(stored?.remainingSeconds ?? 0);
    const isRunning = ref(false);
    const completedBlockIds = ref(
        new Set<number>(stored?.completedBlockIds ?? []),
    );
    const elapsedSeconds = ref<Map<number, number>>(
        new Map(
            Object.entries(stored?.elapsedSeconds ?? {}).map(([k, v]) => [
                Number(k),
                v,
            ]),
        ),
    );

    let intervalId: ReturnType<typeof setInterval> | null = null;
    let segmentStartedAt: number | null = null;

    function accumulateElapsed(): void {
        if (segmentStartedAt === null || activeBlockId.value === null) {
            return;
        }

        const elapsed = Math.floor((Date.now() - segmentStartedAt) / 1000);
        const current = elapsedSeconds.value.get(activeBlockId.value) ?? 0;
        elapsedSeconds.value.set(activeBlockId.value, current + elapsed);
        segmentStartedAt = null;
    }

    function saveState(): void {
        persistState({
            date: todayString(),
            activeBlockId: activeBlockId.value,
            remainingSeconds: remainingSeconds.value,
            completedBlockIds: [...completedBlockIds.value],
            elapsedSeconds: Object.fromEntries(elapsedSeconds.value),
        });
    }

    function clearTimer(): void {
        accumulateElapsed();
        if (intervalId !== null) {
            clearInterval(intervalId);
            intervalId = null;
        }
        isRunning.value = false;
    }

    function startInterval(): void {
        clearTimer();
        if (remainingSeconds.value <= 0) {
            return;
        }

        segmentStartedAt = Date.now();
        isRunning.value = true;
        intervalId = setInterval(() => {
            remainingSeconds.value--;
            if (remainingSeconds.value <= 0) {
                remainingSeconds.value = 0;
                clearTimer();
            }
            saveState();
        }, 1000);
    }

    function getBlock(blockId: number): RoutineBlock | undefined {
        return blocks.find((b) => b.id === blockId);
    }

    function start(blockId: number): void {
        clearTimer();
        activeBlockId.value = blockId;
        elapsedSeconds.value.set(blockId, 0);

        const block = getBlock(blockId);
        const minutes = block?.timer_minutes ?? 0;
        remainingSeconds.value = minutes * 60;

        if (remainingSeconds.value > 0) {
            startInterval();
        }
        saveState();
    }

    function pause(): void {
        clearTimer();
        saveState();
    }

    function resume(): void {
        if (activeBlockId.value === null || remainingSeconds.value <= 0) {
            return;
        }
        startInterval();
    }

    function reset(): void {
        clearTimer();
        if (activeBlockId.value === null) {
            return;
        }

        const block = getBlock(activeBlockId.value);
        remainingSeconds.value = (block?.timer_minutes ?? 0) * 60;
        elapsedSeconds.value.set(activeBlockId.value, 0);
        saveState();
    }

    function skip(): void {
        if (activeBlockId.value !== null) {
            completedBlockIds.value.add(activeBlockId.value);
        }
        clearTimer();
        activeBlockId.value = null;
        remainingSeconds.value = 0;
        saveState();
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

    const completedElapsedSeconds = computed(() => {
        let total = 0;
        for (const blockId of completedBlockIds.value) {
            total += elapsedSeconds.value.get(blockId) ?? 0;
        }
        return total;
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
        completedElapsedSeconds,
        start,
        pause,
        resume,
        reset,
        skip,
        formatTime,
    };
}
