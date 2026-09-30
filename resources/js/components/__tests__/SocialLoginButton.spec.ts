import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { expect, it } from 'vitest';
import SocialLoginButton from '@/components/SocialLoginButton.vue';

it('tells the user that continuing with Google accepts the legal documents', () => {
    setActivePinia(createPinia());

    const notice = mount(SocialLoginButton).find(
        '[data-testid="google-terms-notice"]',
    );

    expect(notice.text()).toContain('18');
    expect(notice.find('[data-testid="terms-link"]').attributes('href')).toBe(
        '/terms-of-service',
    );
    expect(notice.find('[data-testid="privacy-link"]').attributes('href')).toBe(
        '/privacy-policy',
    );
});
