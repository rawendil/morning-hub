import { describe, expect, it } from 'vitest';
import type { BlockTimerState } from '@/composables/useRoutineTimer';
import { newlyCompletedBlockIds } from '@/lib/routine-notifications';

function states(
    entries: Record<number, BlockTimerState>,
): Map<number, BlockTimerState> {
    return new Map(
        Object.entries(entries).map(([id, state]) => [Number(id), state]),
    );
}

describe('newlyCompletedBlockIds', () => {
    it('reports a block that has just been completed', () => {
        expect(
            newlyCompletedBlockIds(
                states({ 1: 'completed' }),
                states({ 1: 'active' }),
                new Set(),
            ),
        ).toEqual([1]);
    });

    it('ignores a block that was already completed', () => {
        expect(
            newlyCompletedBlockIds(
                states({ 1: 'completed' }),
                states({ 1: 'completed' }),
                new Set(),
            ),
        ).toEqual([]);
    });

    it('ignores blocks completed before this page load', () => {
        expect(
            newlyCompletedBlockIds(
                states({ 1: 'completed' }),
                undefined,
                new Set([1]),
            ),
        ).toEqual([]);
    });

    it('reports only the blocks completed in this session', () => {
        expect(
            newlyCompletedBlockIds(
                states({ 1: 'completed', 2: 'completed', 3: 'pending' }),
                states({ 1: 'completed', 2: 'active', 3: 'pending' }),
                new Set([1]),
            ),
        ).toEqual([2]);
    });
});
