import { flushPromises, mount, RouterLinkStub } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type * as VueRouter from 'vue-router';
import Register from '@/pages/auth/Register.vue';
import { useAuthStore } from '@/stores/auth';

vi.mock('vue-router', async (importOriginal) => ({
    ...(await importOriginal<typeof VueRouter>()),
    useRouter: () => ({ push: vi.fn() }),
}));

function mountRegister() {
    setActivePinia(createPinia());

    return mount(Register, {
        attachTo: document.body,
        global: { stubs: { RouterLink: RouterLinkStub } },
    });
}

async function fillAndSubmit(wrapper: ReturnType<typeof mountRegister>) {
    await wrapper.find('#name').setValue('Jan');
    await wrapper.find('#email').setValue('jan@example.com');
    await wrapper.find('#password').setValue('Password1!');
    await wrapper.find('#password_confirmation').setValue('Password1!');
    await wrapper.find('form').trigger('submit');
    await flushPromises();
}

beforeEach(() => {
    localStorage.clear();
});

afterEach(() => {
    document.body.innerHTML = '';
});

describe('Register terms consent', () => {
    it('links both legal documents in a new tab', () => {
        const consent = mountRegister().find('[data-testid="terms-consent"]');
        const expectedLinks = [
            ['terms-link', '/terms-of-service'],
            ['privacy-link', '/privacy-policy'],
        ];

        for (const [testId, href] of expectedLinks) {
            const link = consent.find(`[data-testid="${testId}"]`);

            expect(link.attributes('href')).toBe(href);
            expect(link.attributes('target')).toBe('_blank');
            expect(link.attributes('rel')).toContain('noopener');
        }
    });

    it('sends terms: true once the checkbox is ticked', async () => {
        const wrapper = mountRegister();
        const register = vi
            .spyOn(useAuthStore(), 'register')
            .mockResolvedValue();

        await wrapper.find('#terms').trigger('click');
        await fillAndSubmit(wrapper);

        expect(register).toHaveBeenCalledWith(
            expect.objectContaining({ terms: true }),
        );
    });

    it('sends terms: false when the checkbox is ticked and unticked', async () => {
        const wrapper = mountRegister();
        const register = vi
            .spyOn(useAuthStore(), 'register')
            .mockResolvedValue();

        await wrapper.find('#terms').trigger('click');
        await wrapper.find('#terms').trigger('click');
        await fillAndSubmit(wrapper);

        expect(register).toHaveBeenCalledWith(
            expect.objectContaining({ terms: false }),
        );
    });

    it('shows the server validation error under the checkbox', async () => {
        const wrapper = mountRegister();
        vi.spyOn(useAuthStore(), 'register').mockRejectedValue({
            response: {
                status: 422,
                data: { errors: { terms: ['Musisz zaakceptować'] } },
            },
        });

        await fillAndSubmit(wrapper);

        expect(wrapper.find('[data-testid="terms-consent"]').text()).toContain(
            'Musisz zaakceptować',
        );
    });
});
