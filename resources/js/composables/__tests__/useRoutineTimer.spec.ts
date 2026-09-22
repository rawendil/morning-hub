import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import { useDailyProgress } from '@/composables/useDailyProgress';
import { useRoutineTimer } from '@/composables/useRoutineTimer';
import type {
    UseRoutineTimerOptions,
    UseRoutineTimerReturn,
} from '@/composables/useRoutineTimer';
import type { DailyProgress, RoutineBlock } from '@/types';

vi.mock('@/lib/axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
        put: vi.fn(),
        delete: vi.fn(),
    },
}));

vi.mock('vue-sonner', () => ({
    toast: { error: vi.fn(), success: vi.fn() },
}));

function emptyProgress(overrides: Partial<DailyProgress> = {}): DailyProgress {
    return { date: '2026-09-22', blocks: [], habits: [], ...overrides };
}

function makeBlock(id: number, timerMinutes: number | null): RoutineBlock {
    return {
        id,
        type: 'custom',
        name: `Block ${id}`,
        sort_order: id,
        timer_minutes: timerMinutes,
        clickup_connection_id: null,
        google_calendar_connection_id: null,
        config: null,
    };
}

function mountTimer(
    blocks: RoutineBlock[],
    options: UseRoutineTimerOptions = {},
): UseRoutineTimerReturn {
    let api!: UseRoutineTimerReturn;

    mount(
        defineComponent({
            setup() {
                api = useRoutineTimer(blocks, options);
                return () => null;
            },
        }),
    );

    return api;
}

beforeEach(() => {
    setActivePinia(createPinia());
});

describe('useRoutineTimer onExpire', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('calls onExpire with the block id when the countdown reaches zero', () => {
        const onExpire = vi.fn();
        const timer = mountTimer([makeBlock(1, 1)], { onExpire });

        timer.start(1);
        expect(onExpire).not.toHaveBeenCalled();

        vi.advanceTimersByTime(60_000);

        expect(timer.remainingSeconds.value).toBe(0);
        expect(onExpire).toHaveBeenCalledTimes(1);
        expect(onExpire).toHaveBeenCalledWith(1);
    });

    it('does not call onExpire when the timer is paused before expiry', () => {
        const onExpire = vi.fn();
        const timer = mountTimer([makeBlock(1, 1)], { onExpire });

        timer.start(1);
        vi.advanceTimersByTime(30_000);
        timer.pause();
        vi.advanceTimersByTime(60_000);

        expect(onExpire).not.toHaveBeenCalled();
    });
});

describe('useRoutineTimer completion', () => {
    let axiosMock: {
        put: ReturnType<typeof vi.fn>;
        delete: ReturnType<typeof vi.fn>;
    };

    beforeEach(async () => {
        setActivePinia(createPinia());
        localStorage.clear();
        vi.useFakeTimers();
        vi.clearAllMocks();
        axiosMock = (await import('@/lib/axios'))
            .default as unknown as typeof axiosMock;
        useDailyProgress().hydrate(emptyProgress());
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('records the completion with the measured time when a block is skipped', () => {
        axiosMock.put.mockResolvedValue({ data: emptyProgress() });
        const timer = mountTimer([makeBlock(1, 5)]);

        timer.start(1);
        vi.advanceTimersByTime(30_000);
        timer.skip();

        expect(axiosMock.put).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/1',
            { status: 'completed', elapsed_seconds: 30 },
        );
    });

    it('reads completed blocks from the server state', () => {
        useDailyProgress().hydrate(
            emptyProgress({
                blocks: [
                    {
                        routine_block_id: 1,
                        status: 'completed',
                        elapsed_seconds: 120,
                    },
                ],
            }),
        );

        const timer = mountTimer([makeBlock(1, 5)]);

        expect(timer.blockStates.value.get(1)).toBe('completed');
        expect(timer.completedElapsedSeconds.value).toBe(120);
    });

    it('keeps completed blocks out of browser storage', () => {
        axiosMock.put.mockResolvedValue({ data: emptyProgress() });
        const timer = mountTimer([makeBlock(1, 5)]);

        timer.start(1);
        timer.skip();

        expect(localStorage.getItem('morning-hub-timer-state')).not.toContain(
            'completedBlockIds',
        );
    });
});
