import { ref } from 'vue';

const STORAGE_KEY = 'cookie_consent';

type CookieConsent = {
    decision: 'granted' | 'denied';
    timestamp: string;
};

const consentState = ref<'granted' | 'denied' | null>(null);
const showModal = ref(false);

function loadConsent(): CookieConsent | null {
    if (typeof window === 'undefined') {
        return null;
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : null;
    } catch {
        return null;
    }
}

function saveConsent(decision: 'granted' | 'denied'): void {
    if (typeof window === 'undefined') {
        return;
    }

    const data: CookieConsent = {
        decision,
        timestamp: new Date().toISOString(),
    };

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

function updateGtagConsent(decision: 'granted' | 'denied'): void {
    if (typeof window === 'undefined' || typeof window.gtag !== 'function') {
        return;
    }

    window.gtag('consent', 'update', {
        analytics_storage: decision,
    });
}

export function useCookieConsent() {
    function init(): void {
        const stored = loadConsent();

        if (stored) {
            consentState.value = stored.decision;

            if (stored.decision === 'granted') {
                updateGtagConsent('granted');
            }
        } else {
            showModal.value = true;
        }
    }

    function acceptConsent(): void {
        consentState.value = 'granted';
        saveConsent('granted');
        updateGtagConsent('granted');
        showModal.value = false;
    }

    function rejectConsent(): void {
        consentState.value = 'denied';
        saveConsent('denied');
        showModal.value = false;
    }

    function openSettings(): void {
        showModal.value = true;
    }

    return {
        consentState,
        showModal,
        init,
        acceptConsent,
        rejectConsent,
        openSettings,
    };
}
