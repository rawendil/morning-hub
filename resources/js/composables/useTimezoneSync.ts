import axiosInstance from '@/lib/axios';

const SESSION_KEY = 'morning-hub-timezone';

function readSynced(): string | null {
    try {
        return sessionStorage.getItem(SESSION_KEY);
    } catch {
        return null;
    }
}

function rememberSynced(timezone: string): void {
    try {
        sessionStorage.setItem(SESSION_KEY, timezone);
    } catch {
        // A browser that refuses session storage just re-syncs next time.
    }
}

/**
 * Tells the server which timezone this browser is in, so it can decide what
 * "today" means for the account. Best effort — a failure never blocks the UI.
 */
export function useTimezoneSync() {
    async function syncTimezone(): Promise<void> {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

        if (!timezone || readSynced() === timezone) {
            return;
        }

        try {
            await axiosInstance.put('/settings/timezone', { timezone });
            rememberSynced(timezone);
        } catch {
            // Keep the dashboard usable even if the timezone cannot be stored.
        }
    }

    return { syncTimezone };
}
