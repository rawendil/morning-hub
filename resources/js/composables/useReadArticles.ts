import { ref } from 'vue';
import type { FeedItem } from '@/types';

const STORAGE_KEY = 'read-articles';
const MAX_AGE_MS = 30 * 24 * 60 * 60 * 1000;

export type UseReadArticlesReturn = {
    isRead: (link: string) => boolean;
    toggleRead: (link: string) => void;
    visibleItems: (items: FeedItem[], showRead: boolean) => FeedItem[];
    unreadCount: (items: FeedItem[]) => number;
};

function loadFromStorage(): Record<string, number> {
    if (typeof window === 'undefined') {
        return {};
    }

    try {
        const raw = localStorage.getItem(STORAGE_KEY);

        return raw ? JSON.parse(raw) : {};
    } catch {
        return {};
    }
}

function cleanup(data: Record<string, number>): Record<string, number> {
    const now = Date.now();
    const cleaned: Record<string, number> = {};

    for (const [link, timestamp] of Object.entries(data)) {
        if (now - timestamp < MAX_AGE_MS) {
            cleaned[link] = timestamp;
        }
    }

    return cleaned;
}

function persist(data: Record<string, number>): void {
    if (typeof window === 'undefined') {
        return;
    }

    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
}

const readLinks = ref<Record<string, number>>(cleanup(loadFromStorage()));
persist(readLinks.value);

export function useReadArticles(): UseReadArticlesReturn {
    function isRead(link: string): boolean {
        return link in readLinks.value;
    }

    function toggleRead(link: string): void {
        const updated = { ...readLinks.value };

        if (link in updated) {
            delete updated[link];
        } else {
            updated[link] = Date.now();
        }

        readLinks.value = updated;
        persist(readLinks.value);
    }

    function visibleItems(items: FeedItem[], showRead: boolean): FeedItem[] {
        if (showRead) {
            return items;
        }

        return items.filter((item) => !isRead(item.link));
    }

    function unreadCount(items: FeedItem[]): number {
        return items.filter((item) => !isRead(item.link)).length;
    }

    return {
        isRead,
        toggleRead,
        visibleItems,
        unreadCount,
    };
}
