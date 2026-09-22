import { mount, RouterLinkStub } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { beforeEach, describe, expect, it } from 'vitest';
import Welcome from '@/pages/Welcome.vue';
import english from '../../../../lang/en.json';

const POLISH_DIACRITICS = /[ąćęłńóśźż]/i;

function mountWelcome(locale: 'pl' | 'en' = 'pl') {
    localStorage.setItem('locale', locale);
    setActivePinia(createPinia());

    return mount(Welcome, {
        global: {
            stubs: {
                RouterLink: RouterLinkStub,
            },
        },
    });
}

beforeEach(() => {
    localStorage.clear();
});

describe('Welcome research section', () => {
    it('renders the section with one card per claim', () => {
        const wrapper = mountWelcome();
        const section = wrapper.find('[data-testid="research"]');

        expect(section.exists()).toBe(true);
        expect(
            section.findAll('[data-testid="research-card"]').length,
        ).toBeGreaterThanOrEqual(3);
    });

    it('gives every card exactly one sourced figure', () => {
        const wrapper = mountWelcome();
        const section = wrapper.find('[data-testid="research"]');

        const cards = section.findAll('[data-testid="research-card"]');
        const sources = section.findAll('[data-testid="research-source"]');
        const stats = section.findAll('[data-testid="research-stat"]');

        expect(sources).toHaveLength(cards.length);
        expect(stats).toHaveLength(cards.length);
    });

    it('opens every source safely and points it at a resolvable DOI', () => {
        const wrapper = mountWelcome();
        const sources = wrapper
            .find('[data-testid="research"]')
            .findAll('[data-testid="research-source"]');

        expect(sources.length).toBeGreaterThan(0);

        for (const source of sources) {
            expect(source.attributes('rel')).toBe('noopener noreferrer');
            expect(source.attributes('target')).toBe('_blank');
            expect(source.attributes('href')).toMatch(
                /^https:\/\/doi\.org\/10\./,
            );
        }
    });

    it('cites a distinct source per card', () => {
        const wrapper = mountWelcome();
        const hrefs = wrapper
            .find('[data-testid="research"]')
            .findAll('[data-testid="research-source"]')
            .map((source) => source.attributes('href'));

        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('repeats none of the debunked habit-formation claims', () => {
        const text = mountWelcome()
            .find('[data-testid="research"]')
            .text()
            .toLowerCase();

        expect(text).not.toMatch(/21 dni/);
        expect(text).not.toMatch(/21 days/);
        expect(text).not.toMatch(/maltz/);
    });

    it('sits between the how-it-works and why-Morning-Hub sections', () => {
        const html = mountWelcome().html();

        const howItWorks = html.indexOf('Rozpocznij swoją codzienną');
        const research = html.indexOf('data-testid="research"');
        const why = html.indexOf('Dlaczego Morning Hub?');

        expect(howItWorks).toBeGreaterThan(-1);
        expect(why).toBeGreaterThan(-1);
        expect(research).toBeGreaterThan(howItWorks);
        expect(research).toBeLessThan(why);
    });

    it('has an English translation for every string it renders', () => {
        const section = mountWelcome('pl').find('[data-testid="research"]');

        const rendered = [
            ...section.findAll('h2'),
            ...section.findAll('p'),
            ...section.findAll('[data-slot="card-title"]'),
            ...section.findAll('[data-testid="research-source"]'),
        ]
            .map((node) => node.text())
            .filter((text) => text.length > 0);

        expect(rendered.length).toBeGreaterThan(0);

        for (const text of rendered) {
            expect(
                Object.prototype.hasOwnProperty.call(english, text),
                `brak tlumaczenia EN dla klucza: "${text}"`,
            ).toBe(true);
        }
    });

    it('renders no Polish left-overs once switched to English', () => {
        const polish = mountWelcome('pl')
            .find('[data-testid="research"]')
            .text();
        const translated = mountWelcome('en')
            .find('[data-testid="research"]')
            .text();

        expect(translated).not.toBe(polish);
        expect(POLISH_DIACRITICS.test(translated)).toBe(false);
    });
});
