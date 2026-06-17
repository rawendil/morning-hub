import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import { useRoutineTimer } from '@/composables/useRoutineTimer';
import type {
    UseRoutineTimerOptions,
    UseRoutineTimerReturn,
} from '@/composables/useRoutineTimer';
import type { RoutineBlock } from '@/types';

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
