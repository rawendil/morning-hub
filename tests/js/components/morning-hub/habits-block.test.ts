import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import HabitsBlock from '@/components/morning-hub/HabitsBlock.vue';
import { useDailyProgress } from '@/composables/useDailyProgress';
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

function makeBlock(): RoutineBlock {
    return {
        id: 1,
        type: 'habits',
        name: 'Codzienne nawyki',
        sort_order: 0,
        timer_minutes: 5,
        clickup_connection_id: null,
        google_calendar_connection_id: null,
        config: {
            habits: [
                { id: 'habit-water', label: 'Wypij szklankę wody' },
                { id: 'habit-meditation', label: 'Medytacja 5 minut' },
            ],
        },
    };
}

function mountHabits() {
    return mount(HabitsBlock, {
        props: {
            block: makeBlock(),
            isActiveBlock: false,
            isCompleted: false,
            isTimerRunning: false,
            isTimerExpired: false,
            remainingSeconds: 0,
            formattedTime: '',
        },
    });
}

function progress(overrides: Partial<DailyProgress> = {}): DailyProgress {
    return { date: '2026-09-22', blocks: [], habits: [], ...overrides };
}

let axiosMock: { post: ReturnType<typeof vi.fn> };

beforeEach(async () => {
    setActivePinia(createPinia());
    vi.clearAllMocks();
    axiosMock = (await import('@/lib/axios')).default as unknown as typeof axiosMock;
    useDailyProgress().hydrate(progress());
});

describe('HabitsBlock', () => {
    it('sends the habit to the server when its checkbox is clicked', async () => {
        axiosMock.post.mockResolvedValue({ data: progress() });
        const wrapper = mountHabits();

        await wrapper.get('#habit-1-habit-water').trigger('click');

        expect(axiosMock.post).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/1/habits',
            { habit_id: 'habit-water', completed: true },
        );
    });

    it('shows habits already completed on the server as checked', () => {
        useDailyProgress().hydrate(
            progress({
                habits: [
                    { routine_block_id: 1, habit_id: 'habit-water' },
                ],
            }),
        );

        const wrapper = mountHabits();

        expect(
            wrapper.get('#habit-1-habit-water').attributes('aria-checked'),
        ).toBe('true');
        expect(
            wrapper.get('#habit-1-habit-meditation').attributes('aria-checked'),
        ).toBe('false');
    });

    it('counts completed habits in the block progress', () => {
        useDailyProgress().hydrate(
            progress({
                habits: [{ routine_block_id: 1, habit_id: 'habit-water' }],
            }),
        );

        expect(mountHabits().text()).toContain('1/2');
    });
});
