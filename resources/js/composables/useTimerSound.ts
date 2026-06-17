import type { Ref } from 'vue';
import { onMounted, ref } from 'vue';

export type UseTimerSoundReturn = {
    enabled: Ref<boolean>;
    setEnabled: (value: boolean) => void;
    playTimerEndSound: () => void;
};

export const TIMER_SOUND_STORAGE_KEY = 'morning-hub-timer-sound';
const TIMER_SOUND_URL = '/sounds/koniec-sesji.mp3';
const FADE_SECONDS = 1;
const FADE_TICK_MS = 50;

const enabled = ref(true);

/**
 * Linear volume envelope: fades in over the first FADE_SECONDS (0 -> 1) and
 * fades out over the final FADE_SECONDS (1 -> 0), staying at full volume in
 * between. Returns full volume when the duration is not yet known.
 */
export function fadeVolumeFor(
    currentTime: number,
    duration: number,
    fadeSeconds: number = FADE_SECONDS,
): number {
    if (!Number.isFinite(duration) || duration <= 0) {
        return 1;
    }

    if (fadeSeconds <= 0) {
        return 1;
    }

    const fadeIn = currentTime / fadeSeconds;
    const fadeOut = (duration - currentTime) / fadeSeconds;

    return Math.min(1, Math.max(0, Math.min(fadeIn, fadeOut)));
}

function readStoredPreference(): boolean {
    if (typeof window === 'undefined') {
        return true;
    }

    const raw = localStorage.getItem(TIMER_SOUND_STORAGE_KEY);
    if (raw === null) {
        return true;
    }

    return raw === 'true';
}

let audio: HTMLAudioElement | null = null;
let fadeIntervalId: ReturnType<typeof setInterval> | null = null;

function clearFade(): void {
    if (fadeIntervalId !== null) {
        clearInterval(fadeIntervalId);
        fadeIntervalId = null;
    }
}

function getAudioElement(): HTMLAudioElement | null {
    if (typeof window === 'undefined') {
        return null;
    }

    if (audio === null) {
        audio = new Audio(TIMER_SOUND_URL);
        audio.preload = 'auto';
    }

    return audio;
}

export function useTimerSound(): UseTimerSoundReturn {
    onMounted(() => {
        enabled.value = readStoredPreference();
    });

    function setEnabled(value: boolean): void {
        enabled.value = value;

        if (typeof window !== 'undefined') {
            localStorage.setItem(
                TIMER_SOUND_STORAGE_KEY,
                value ? 'true' : 'false',
            );
        }
    }

    function playTimerEndSound(): void {
        if (!enabled.value) {
            return;
        }

        const element = getAudioElement();
        if (element === null) {
            return;
        }

        clearFade();
        element.currentTime = 0;
        element.volume = fadeVolumeFor(element.currentTime, element.duration);

        element.onended = () => {
            clearFade();
            element.volume = 1;
        };

        fadeIntervalId = setInterval(() => {
            element.volume = fadeVolumeFor(
                element.currentTime,
                element.duration,
            );
        }, FADE_TICK_MS);

        void element.play().catch(() => {
            clearFade();
            element.volume = 1;
        });
    }

    return {
        enabled,
        setEnabled,
        playTimerEndSound,
    };
}
