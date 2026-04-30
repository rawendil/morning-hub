import '../css/app.css'
import 'vue-sonner/style.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router/index'
import { useAuthStore } from './stores/auth'
import { initializeTheme } from './composables/useAppearance'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)

Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN,
    environment: import.meta.env.VITE_APP_ENV ?? 'production',
    integrations: [
        Sentry.browserTracingIntegration({ router }),
        Sentry.replayIntegration(),
    ],
    tracesSampleRate: parseFloat(import.meta.env.VITE_SENTRY_TRACES_SAMPLE_RATE ?? '0'),
    replaysSessionSampleRate: 0,
    replaysOnErrorSampleRate: 1.0,
    enabled: !!import.meta.env.VITE_SENTRY_DSN,
})

initializeTheme()

const authStore = useAuthStore()

authStore.initialize().then(() => {
    app.use(router)
    app.mount('#app')
})
