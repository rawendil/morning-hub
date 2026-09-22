import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { useTranslations } from '@/composables/useTranslations';
import axiosInstance from '@/lib/axios';
import type { BlockCompletionStatus, DailyProgress } from '@/types';

const emptyProgress: DailyProgress = { date: '', blocks: [], habits: [] };

const state = ref<DailyProgress>({ ...emptyProgress });

/**
 * Today's routine progress, owned by the server. Every change is applied
 * locally first and rolled back if the request fails.
 */
export function useDailyProgress() {
    const { t } = useTranslations();

    function hydrate(progress: DailyProgress | null | undefined): void {
        state.value = progress
            ? {
                  date: progress.date,
                  blocks: progress.blocks ?? [],
                  habits: progress.habits ?? [],
              }
            : { ...emptyProgress };
    }

    async function load(): Promise<void> {
        const { data } = await axiosInstance.get('/morning-hub/daily');
        hydrate(data as DailyProgress);
    }

    function isHabitCompleted(habitId: string): boolean {
        return state.value.habits.some((habit) => habit.habit_id === habitId);
    }

    function completedHabitIds(blockId: number): string[] {
        return state.value.habits
            .filter((habit) => habit.routine_block_id === blockId)
            .map((habit) => habit.habit_id);
    }

    async function persist(
        request: () => Promise<{ data: DailyProgress }>,
        optimistic: DailyProgress,
    ): Promise<void> {
        const previous = state.value;
        state.value = optimistic;

        try {
            const { data } = await request();
            hydrate(data);
        } catch {
            state.value = previous;
            toast.error(t('Nie udało się zapisać postępu.'));
        }
    }

    async function toggleHabit(
        blockId: number,
        habitId: string,
    ): Promise<void> {
        const completed = !isHabitCompleted(habitId);

        await persist(
            () =>
                axiosInstance.post(
                    `/morning-hub/daily/blocks/${blockId}/habits`,
                    { habit_id: habitId, completed },
                ),
            {
                ...state.value,
                habits: completed
                    ? [
                          ...state.value.habits,
                          { routine_block_id: blockId, habit_id: habitId },
                      ]
                    : state.value.habits.filter(
                          (habit) => habit.habit_id !== habitId,
                      ),
            },
        );
    }

    const completedBlockIds = computed(
        () =>
            new Set(state.value.blocks.map((block) => block.routine_block_id)),
    );

    const completedElapsedSeconds = computed(() =>
        state.value.blocks.reduce(
            (total, block) => total + (block.elapsed_seconds ?? 0),
            0,
        ),
    );

    function elapsedSecondsFor(blockId: number): number | null {
        return (
            state.value.blocks.find(
                (block) => block.routine_block_id === blockId,
            )?.elapsed_seconds ?? null
        );
    }

    async function recordBlockCompletion(
        blockId: number,
        elapsedSeconds: number,
        status: BlockCompletionStatus = 'completed',
    ): Promise<void> {
        await persist(
            () =>
                axiosInstance.put(`/morning-hub/daily/blocks/${blockId}`, {
                    status,
                    elapsed_seconds: elapsedSeconds,
                }),
            {
                ...state.value,
                blocks: [
                    ...state.value.blocks.filter(
                        (block) => block.routine_block_id !== blockId,
                    ),
                    {
                        routine_block_id: blockId,
                        status,
                        elapsed_seconds: elapsedSeconds,
                    },
                ],
            },
        );
    }

    async function clearBlockCompletion(blockId: number): Promise<void> {
        await persist(
            () => axiosInstance.delete(`/morning-hub/daily/blocks/${blockId}`),
            {
                ...state.value,
                blocks: state.value.blocks.filter(
                    (block) => block.routine_block_id !== blockId,
                ),
            },
        );
    }

    return {
        state,
        date: computed(() => state.value.date),
        hydrate,
        load,
        isHabitCompleted,
        completedHabitIds,
        toggleHabit,
        completedBlockIds,
        completedElapsedSeconds,
        elapsedSecondsFor,
        recordBlockCompletion,
        clearBlockCompletion,
    };
}
