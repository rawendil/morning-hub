import type { BlockTimerState } from '@/composables/useRoutineTimer';

/**
 * Blocks that became completed through the user's own action in this session.
 * Blocks already finished when the day was loaded from the server are left
 * out, so a page reload does not replay yesterday evening's celebration.
 */
export function newlyCompletedBlockIds(
    newStates: Map<number, BlockTimerState>,
    oldStates: Map<number, BlockTimerState> | undefined,
    completedOnLoad: Set<number>,
): number[] {
    const ids: number[] = [];

    for (const [blockId, state] of newStates) {
        if (state !== 'completed' || completedOnLoad.has(blockId)) {
            continue;
        }

        if (oldStates?.get(blockId) !== 'completed') {
            ids.push(blockId);
        }
    }

    return ids;
}
