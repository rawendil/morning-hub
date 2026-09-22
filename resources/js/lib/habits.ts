import type { Habit } from '@/types';

/**
 * Read habits out of a block config, tolerating the legacy shape where a
 * habit was a plain label without a stable identifier.
 */
export function toHabits(value: unknown): Habit[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value.map((entry): Habit => {
        if (typeof entry === 'string') {
            return { id: '', label: entry };
        }

        const habit = entry as Partial<Habit>;

        return {
            id: typeof habit?.id === 'string' ? habit.id : '',
            label: typeof habit?.label === 'string' ? habit.label : '',
        };
    });
}
