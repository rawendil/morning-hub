import { describe, expect, it } from 'vitest';
import { toHabits } from '@/lib/habits';

describe('toHabits', () => {
    it('keeps identified habits as they are', () => {
        expect(toHabits([{ id: 'habit-a', label: 'Woda' }])).toEqual([
            { id: 'habit-a', label: 'Woda' },
        ]);
    });

    it('reads legacy string habits without an id', () => {
        expect(toHabits(['Woda'])).toEqual([{ id: '', label: 'Woda' }]);
    });

    it('returns an empty list for anything that is not an array', () => {
        expect(toHabits(undefined)).toEqual([]);
        expect(toHabits(null)).toEqual([]);
        expect(toHabits('Woda')).toEqual([]);
    });

    it('tolerates malformed entries', () => {
        expect(toHabits([{ label: 'Woda' }, {}])).toEqual([
            { id: '', label: 'Woda' },
            { id: '', label: '' },
        ]);
    });
});
