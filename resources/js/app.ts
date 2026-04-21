import { createInertiaApp } from '@inertiajs/vue3';
import * as Sentry from '@sentry/vue';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import type { DefineComponent } from 'vue';
import { createApp, h } from 'vue';
import { Toaster } from 'vue-sonner';
import CookieConsentModal from '@/components/CookieConsentModal.vue';
import 'vue-sonner/style.css';
import '../css/app.css';
import { initializeTheme } from '@/composables/useAppearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob<DefineComponent>('./pages/**/*.vue'),
        ),
    setup({ el, App, props, plugin }) {
        const app = createApp({
            render: () => [
                h(App, props),
                h(Toaster, { position: 'bottom-right', richColors: true }),
                h(CookieConsentModal),
            ],
        }).use(plugin);

        Sentry.init({
            app,
            dsn: import.meta.env.VITE_SENTRY_DSN,
            environment: import.meta.env.VITE_APP_ENV ?? 'production',
            integrations: [
                Sentry.browserTracingIntegration(),
                Sentry.replayIntegration(),
            ],
            tracesSampleRate: parseFloat(
                import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? '0',
            ),
            replaysSessionSampleRate: 0,
            replaysOnErrorSampleRate: 1.0,
            enabled: !!import.meta.env.VITE_SENTRY_DSN,
        });

        app.mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
