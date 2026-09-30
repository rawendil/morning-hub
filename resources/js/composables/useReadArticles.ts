import { ref } from 'vue';
import { toast } from 'vue-sonner';
import { useTranslations } from '@/composables/useTranslations';
import axiosInstance from '@/lib/axios';
import type { FeedItem } from '@/types';

const LEGACY_STORAGE_KEY = 'read-articles';

export type UseReadArticlesReturn = {
    hydrate: (links: string[] | null | undefined) => void;
    isRead: (link: string) => boolean;
    toggleRead: (link: string) => Promise<void>;
    visibleItems: (items: FeedItem[], showRead: boolean) => FeedItem[];
    unreadCount: (items: FeedItem[]) => number;
};

const readLinks = ref<Set<string>>(new Set());

function dropLegacyStorage(): void {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        localStorage.removeItem(LEGACY_STORAGE_KEY);
    } catch {
        // Storage may be unavailable; the legacy list is ignored either way.
    }
}

/**
 * Articles the user marked read, owned by the server so the list follows
 * the account across devices. Every change is applied locally first and
 * rolled back if the request fails.
 */
export function useReadArticles(): UseReadArticlesReturn {
    const { t } = useTranslations();

    function hydrate(links: string[] | null | undefined): void {
        readLinks.value = new Set(links ?? []);
        dropLegacyStorage();
    }

    function isRead(link: string): boolean {
        return readLinks.value.has(link);
    }

    async function toggleRead(link: string): Promise<void> {
        const previous = readLinks.value;
        const read = !previous.has(link);
        const optimistic = new Set(previous);

        if (read) {
            optimistic.add(link);
        } else {
            optimistic.delete(link);
        }

        readLinks.value = optimistic;

        try {
            const { data } = await axiosInstance.post(
                '/morning-hub/read-articles',
                { link, read },
            );
            readLinks.value = new Set(data as string[]);
        } catch {
            readLinks.value = previous;
            toast.error(t('Nie udało się zapisać statusu artykułu.'));
        }
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
        hydrate,
        isRead,
        toggleRead,
        visibleItems,
        unreadCount,
    };
}
