import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type * as DailyProgressModuleType from '@/composables/useDailyProgress';
import type { DailyProgress } from '@/types';

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

type DailyProgressModule = typeof DailyProgressModuleType;
type AxiosMock = {
    get: ReturnType<typeof vi.fn>;
    post: ReturnType<typeof vi.fn>;
    put: ReturnType<typeof vi.fn>;
    delete: ReturnType<typeof vi.fn>;
};

let useDailyProgress: DailyProgressModule['useDailyProgress'];
let axiosMock: AxiosMock;

function progress(overrides: Partial<DailyProgress> = {}): DailyProgress {
    return { date: '2026-09-22', blocks: [], habits: [], ...overrides };
}

beforeEach(async () => {
    setActivePinia(createPinia());
    vi.resetModules();
    vi.clearAllMocks();
    axiosMock = (await import('@/lib/axios')).default as unknown as AxiosMock;
    ({ useDailyProgress } = await import('@/composables/useDailyProgress'));
});

describe('habits', () => {
    it('exposes habits completed on the server', () => {
        const api = useDailyProgress();
        api.hydrate(
            progress({
                habits: [{ routine_block_id: 1, habit_id: 'habit-water' }],
            }),
        );

        expect(api.isHabitCompleted('habit-water')).toBe(true);
        expect(api.isHabitCompleted('habit-meditation')).toBe(false);
    });

    it('marks a habit completed before the request resolves', () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.post.mockReturnValue(new Promise(() => {}));

        api.toggleHabit(1, 'habit-water');

        expect(api.isHabitCompleted('habit-water')).toBe(true);
    });

    it('sends the desired state to the server', async () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.post.mockResolvedValue({ data: progress() });

        await api.toggleHabit(7, 'habit-water');

        expect(axiosMock.post).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/7/habits',
            { habit_id: 'habit-water', completed: true },
        );
    });

    it('adopts the server state once the request resolves', async () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.post.mockResolvedValue({
            data: progress({
                habits: [
                    { routine_block_id: 1, habit_id: 'habit-water' },
                    { routine_block_id: 1, habit_id: 'habit-meditation' },
                ],
            }),
        });

        await api.toggleHabit(1, 'habit-water');

        expect(api.isHabitCompleted('habit-meditation')).toBe(true);
    });

    it('reverts the habit when the request fails', async () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.post.mockRejectedValue(new Error('offline'));

        await api.toggleHabit(1, 'habit-water');

        expect(api.isHabitCompleted('habit-water')).toBe(false);
    });

    it('unchecks a completed habit', async () => {
        const api = useDailyProgress();
        api.hydrate(
            progress({
                habits: [{ routine_block_id: 1, habit_id: 'habit-water' }],
            }),
        );
        axiosMock.post.mockResolvedValue({ data: progress() });

        await api.toggleHabit(1, 'habit-water');

        expect(axiosMock.post).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/1/habits',
            { habit_id: 'habit-water', completed: false },
        );
        expect(api.isHabitCompleted('habit-water')).toBe(false);
    });
});

describe('blocks', () => {
    it('exposes blocks completed on the server', () => {
        const api = useDailyProgress();
        api.hydrate(
            progress({
                blocks: [
                    {
                        routine_block_id: 3,
                        status: 'completed',
                        elapsed_seconds: 120,
                    },
                ],
            }),
        );

        expect([...api.completedBlockIds.value]).toEqual([3]);
        expect(api.completedElapsedSeconds.value).toBe(120);
    });

    it('records a completion with the measured time', async () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.put.mockResolvedValue({ data: progress() });

        await api.recordBlockCompletion(3, 742);

        expect(axiosMock.put).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/3',
            { status: 'completed', elapsed_seconds: 742 },
        );
    });

    it('marks the block completed before the request resolves', () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.put.mockReturnValue(new Promise(() => {}));

        api.recordBlockCompletion(3, 60);

        expect(api.completedBlockIds.value.has(3)).toBe(true);
    });

    it('reverts the completion when the request fails', async () => {
        const api = useDailyProgress();
        api.hydrate(progress());
        axiosMock.put.mockRejectedValue(new Error('offline'));

        await api.recordBlockCompletion(3, 60);

        expect(api.completedBlockIds.value.has(3)).toBe(false);
    });

    it('clears a completion', async () => {
        const api = useDailyProgress();
        api.hydrate(
            progress({
                blocks: [
                    {
                        routine_block_id: 3,
                        status: 'completed',
                        elapsed_seconds: 120,
                    },
                ],
            }),
        );
        axiosMock.delete.mockResolvedValue({ data: progress() });

        await api.clearBlockCompletion(3);

        expect(axiosMock.delete).toHaveBeenCalledWith(
            '/morning-hub/daily/blocks/3',
        );
        expect(api.completedBlockIds.value.has(3)).toBe(false);
    });
});

describe('loading', () => {
    it('fetches the day from the server', async () => {
        const api = useDailyProgress();
        axiosMock.get.mockResolvedValue({
            data: progress({
                habits: [{ routine_block_id: 1, habit_id: 'habit-water' }],
            }),
        });

        await api.load();

        expect(axiosMock.get).toHaveBeenCalledWith('/morning-hub/daily');
        expect(api.isHabitCompleted('habit-water')).toBe(true);
    });
});
