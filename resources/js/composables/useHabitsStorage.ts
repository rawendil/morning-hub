import { ref } from 'vue';

type StoredHabitsState = {
    date: string;
    blocks: Record<number, number[]>;
};

const STORAGE_KEY = 'morning-hub-habits-state';

function todayString(): string {
    return new Date().toISOString().slice(0, 10);
}

function loadState(): StoredHabitsState {
    if (typeof window === 'undefined') {
        return { date: todayString(), blocks: {} };
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        if (!raw) {
            return { date: todayString(), blocks: {} };
        }

        const parsed: StoredHabitsState = JSON.parse(raw);
        if (parsed.date !== todayString()) {
            localStorage.removeItem(STORAGE_KEY);
            return { date: todayString(), blocks: {} };
        }

        return parsed;
    } catch {
        return { date: todayString(), blocks: {} };
    }
}

function saveState(state: StoredHabitsState): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
}

const state = ref<StoredHabitsState>(loadState());

export function useHabitsStorage() {
    function getCompleted(blockId: number): number[] {
        return state.value.blocks[blockId] ?? [];
    }

    function toggle(blockId: number, index: number): number[] {
        const current = getCompleted(blockId);
        const updated = current.includes(index)
            ? current.filter((i) => i !== index)
            : [...current, index];

        state.value = {
            ...state.value,
            blocks: { ...state.value.blocks, [blockId]: updated },
        };
        saveState(state.value);

        return updated;
    }

    return { state, getCompleted, toggle };
}
