import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent } from 'vue';
import {
    fadeVolumeFor,
    TIMER_SOUND_STORAGE_KEY,
    useTimerSound,
} from '@/composables/useTimerSound';

function mountTimerSound(): ReturnType<typeof useTimerSound> {
    let api!: ReturnType<typeof useTimerSound>;

    mount(
        defineComponent({
            setup() {
                api = useTimerSound();
                return () => null;
            },
        }),
    );

    return api;
}

describe('useTimerSound', () => {
    beforeEach(() => {
        localStorage.clear();
        vi.restoreAllMocks();
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it('is enabled by default when no preference is stored', () => {
        const { enabled } = mountTimerSound();

        expect(enabled.value).toBe(true);
    });

    it('persists the preference to localStorage', () => {
        const { enabled, setEnabled } = mountTimerSound();

        setEnabled(false);
        expect(enabled.value).toBe(false);
        expect(localStorage.getItem(TIMER_SOUND_STORAGE_KEY)).toBe('false');

        setEnabled(true);
        expect(enabled.value).toBe(true);
        expect(localStorage.getItem(TIMER_SOUND_STORAGE_KEY)).toBe('true');
    });

    it('restores a stored disabled preference on mount', () => {
        localStorage.setItem(TIMER_SOUND_STORAGE_KEY, 'false');

        const { enabled } = mountTimerSound();

        expect(enabled.value).toBe(false);
    });

    it('plays the sound when enabled', () => {
        const play = vi
            .spyOn(window.HTMLMediaElement.prototype, 'play')
            .mockResolvedValue();

        const { setEnabled, playTimerEndSound } = mountTimerSound();
        setEnabled(true);

        playTimerEndSound();

        expect(play).toHaveBeenCalledOnce();
    });

    it('does not play the sound when disabled', () => {
        const play = vi
            .spyOn(window.HTMLMediaElement.prototype, 'play')
            .mockResolvedValue();

        const { setEnabled, playTimerEndSound } = mountTimerSound();
        setEnabled(false);

        playTimerEndSound();

        expect(play).not.toHaveBeenCalled();
    });
});

describe('fadeVolumeFor', () => {
    it('ramps linearly up from 0 during the first second', () => {
        expect(fadeVolumeFor(0, 5)).toBe(0);
        expect(fadeVolumeFor(0.25, 5)).toBeCloseTo(0.25);
        expect(fadeVolumeFor(0.5, 5)).toBeCloseTo(0.5);
        expect(fadeVolumeFor(1, 5)).toBe(1);
    });

    it('stays at full volume between the fades', () => {
        expect(fadeVolumeFor(1, 5)).toBe(1);
        expect(fadeVolumeFor(2.5, 5)).toBe(1);
        expect(fadeVolumeFor(4, 5)).toBe(1);
    });

    it('ramps linearly down to 0 during the final second', () => {
        expect(fadeVolumeFor(4.5, 5)).toBeCloseTo(0.5);
        expect(fadeVolumeFor(4.75, 5)).toBeCloseTo(0.25);
        expect(fadeVolumeFor(5, 5)).toBe(0);
    });

    it('falls back to full volume when duration is unknown', () => {
        expect(fadeVolumeFor(0, NaN)).toBe(1);
        expect(fadeVolumeFor(0, 0)).toBe(1);
    });
});
