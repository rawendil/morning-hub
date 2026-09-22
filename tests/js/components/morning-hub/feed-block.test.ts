import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import FeedBlock from '@/components/morning-hub/FeedBlock.vue';
import { useReadArticles } from '@/composables/useReadArticles';
import type { BlockFeedData, RoutineBlock } from '@/types';

const UNREAD_LINK = 'https://example.test/unread';
const READ_LINK = 'https://example.test/read';

function makeBlock(): RoutineBlock {
    return {
        id: 4,
        type: 'feed',
        name: 'Nowości z branży',
        sort_order: 0,
        timer_minutes: null,
        clickup_connection_id: null,
        google_calendar_connection_id: null,
        config: null,
    };
}

function feedData(): BlockFeedData {
    return {
        items: [
            {
                title: 'Artykuł nieprzeczytany',
                link: UNREAD_LINK,
                source: 'Example',
                published_at: new Date().toISOString(),
            },
            {
                title: 'Artykuł przeczytany',
                link: READ_LINK,
                source: 'Example',
                published_at: new Date().toISOString(),
            },
        ],
        error: null,
    };
}

function mountFeed() {
    return mount(FeedBlock, {
        props: {
            block: makeBlock(),
            feedData: feedData(),
            isActiveBlock: false,
            isCompleted: false,
            isTimerRunning: false,
            isTimerExpired: false,
            remainingSeconds: 0,
            formattedTime: '',
        },
        attachTo: document.body,
    });
}

beforeEach(() => {
    setActivePinia(createPinia());

    const { isRead, toggleRead } = useReadArticles();
    if (!isRead(READ_LINK)) {
        toggleRead(READ_LINK);
    }
    if (isRead(UNREAD_LINK)) {
        toggleRead(UNREAD_LINK);
    }
});

describe('FeedBlock read-articles switch', () => {
    it('starts switched off and hides read articles', () => {
        const wrapper = mountFeed();

        expect(wrapper.get('[role=switch]').attributes('aria-checked')).toBe(
            'false',
        );
        expect(wrapper.text()).toContain('Artykuł nieprzeczytany');
        expect(wrapper.text()).not.toContain('Artykuł przeczytany');
    });

    it('reveals read articles and turns on when the switch is clicked', async () => {
        const wrapper = mountFeed();

        await wrapper.get('[role=switch]').trigger('click');

        expect(wrapper.get('[role=switch]').attributes('aria-checked')).toBe(
            'true',
        );
        expect(wrapper.text()).toContain('Artykuł przeczytany');
    });

    it('keeps the switch in sync when the eye icon is clicked instead', async () => {
        const wrapper = mountFeed();

        await wrapper.get('svg.lucide-eye-icon').trigger('click');

        expect(wrapper.text()).toContain('Artykuł przeczytany');
        expect(wrapper.get('[role=switch]').attributes('aria-checked')).toBe(
            'true',
        );
    });

    it('does not nest the switch inside another button', () => {
        const wrapper = mountFeed();

        expect(wrapper.findAll('button button')).toHaveLength(0);
    });

    it('marks an article read when its checkbox is clicked', async () => {
        const wrapper = mountFeed();
        const { isRead } = useReadArticles();

        await wrapper.get('[role=checkbox]').trigger('click');

        expect(isRead(UNREAD_LINK)).toBe(true);
    });

    it('keeps the article checkbox reachable by keyboard', () => {
        const wrapper = mountFeed();

        expect(wrapper.get('[role=checkbox]').attributes('tabindex')).not.toBe(
            '-1',
        );
    });
});
