import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type * as ReadArticlesModuleType from '@/composables/useReadArticles';
import type { FeedItem } from '@/types';

vi.mock('@/lib/axios', () => ({
    default: { post: vi.fn() },
}));

vi.mock('vue-sonner', () => ({
    toast: { error: vi.fn(), success: vi.fn() },
}));

type ReadArticlesModule = typeof ReadArticlesModuleType;
type AxiosMock = { post: ReturnType<typeof vi.fn> };

const LINK = 'https://example.test/articles/1';
const OTHER_LINK = 'https://example.test/articles/2';

let useReadArticles: ReadArticlesModule['useReadArticles'];
let axiosMock: AxiosMock;
let toastMock: { error: ReturnType<typeof vi.fn> };

function item(link: string): FeedItem {
    return {
        title: link,
        link,
        source: 'Example',
        published_at: '2026-09-30T06:00:00.000Z',
    };
}

beforeEach(async () => {
    setActivePinia(createPinia());
    vi.resetModules();
    vi.clearAllMocks();
    localStorage.clear();
    axiosMock = (await import('@/lib/axios')).default as unknown as AxiosMock;
    toastMock = (await import('vue-sonner')).toast as unknown as {
        error: ReturnType<typeof vi.fn>;
    };
    ({ useReadArticles } = await import('@/composables/useReadArticles'));
});

describe('useReadArticles', () => {
    it('exposes links read on the server', () => {
        const api = useReadArticles();
        api.hydrate([LINK]);

        expect(api.isRead(LINK)).toBe(true);
        expect(api.isRead(OTHER_LINK)).toBe(false);
        expect(api.unreadCount([item(LINK), item(OTHER_LINK)])).toBe(1);
    });

    it('hides read articles unless asked to show them', () => {
        const api = useReadArticles();
        api.hydrate([LINK]);
        const items = [item(LINK), item(OTHER_LINK)];

        expect(api.visibleItems(items, false)).toEqual([item(OTHER_LINK)]);
        expect(api.visibleItems(items, true)).toEqual(items);
    });

    it('marks an article read before the request resolves', () => {
        const api = useReadArticles();
        api.hydrate([]);
        axiosMock.post.mockReturnValue(new Promise(() => {}));

        api.toggleRead(LINK);

        expect(api.isRead(LINK)).toBe(true);
        expect(axiosMock.post).toHaveBeenCalledWith(
            '/morning-hub/read-articles',
            { link: LINK, read: true },
        );
    });

    it('marks a read article unread', async () => {
        const api = useReadArticles();
        api.hydrate([LINK]);
        axiosMock.post.mockResolvedValue({ data: [] });

        await api.toggleRead(LINK);

        expect(api.isRead(LINK)).toBe(false);
        expect(axiosMock.post).toHaveBeenCalledWith(
            '/morning-hub/read-articles',
            { link: LINK, read: false },
        );
    });

    it('adopts the server list after a successful request', async () => {
        const api = useReadArticles();
        api.hydrate([]);
        axiosMock.post.mockResolvedValue({ data: [OTHER_LINK, LINK] });

        await api.toggleRead(LINK);

        expect(api.isRead(OTHER_LINK)).toBe(true);
    });

    it('rolls back and warns when the request fails', async () => {
        const api = useReadArticles();
        api.hydrate([]);
        axiosMock.post.mockRejectedValue(new Error('network'));

        await api.toggleRead(LINK);

        expect(api.isRead(LINK)).toBe(false);
        expect(toastMock.error).toHaveBeenCalled();
    });

    it('drops the legacy browser-only read list', () => {
        localStorage.setItem('read-articles', JSON.stringify({ [LINK]: 1 }));

        useReadArticles().hydrate([]);

        expect(localStorage.getItem('read-articles')).toBeNull();
    });
});
