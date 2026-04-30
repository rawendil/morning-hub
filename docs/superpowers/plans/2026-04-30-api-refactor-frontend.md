# API Refactor — Etap 2: Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Prerequisite:** Etap 1 (Backend) musi być zakończony i przetestowany.

**Goal:** Zastąpić `@inertiajs/vue3` przez Vue Router + Axios SPA — frontend komunikuje się z backendem wyłącznie przez REST API z Bearer tokenem.

**Architecture:** Nowy `app.ts` z `createApp` + Vue Router zastępuje `createInertiaApp`. Axios z interceptorami zarządza autentykacją. `useAuthStore` (Pinia) zastępuje `HandleInertiaRequests` middleware jako źródło danych globalnych. Istniejące strony Vue są przepisywane bez `usePage`, `useForm`, `<Link>`, `<Form>`.

**Tech Stack:** Vue 3, Vue Router 4, Axios, Pinia, Vitest + Vue Test Utils (nowe), Playwright (nowe)

---

## File Map

### Nowe pliki
- `resources/js/lib/axios.ts` — skonfigurowana instancja Axios z interceptorami
- `resources/js/router/index.ts` — Vue Router z navigation guards
- `resources/js/stores/auth.ts` — Pinia store dla stanu użytkownika
- `resources/js/app.ts` — przepisany (createApp zamiast createInertiaApp)
- `resources/views/app.blade.php` — uproszczony (bez @inertia)
- `vitest.config.ts` — konfiguracja Vitest
- `playwright.config.ts` — konfiguracja Playwright
- `tests/js/lib/axios.test.ts` — testy interceptorów
- `tests/js/router/guards.test.ts` — testy navigation guards
- `tests/js/stores/auth.test.ts` — testy auth store

### Modyfikowane pliki
- `package.json` — dodanie vue-router, axios, pinia, vitest, @vue/test-utils, playwright; usunięcie @inertiajs/vue3, @laravel/vite-plugin-wayfinder
- `vite.config.ts` — usunięcie Wayfinder plugin
- `resources/js/app.ts` — pełny rewrite
- `resources/views/app.blade.php` — uproszczenie
- `app/Providers/AppServiceProvider.php` — konfiguracja URL resetowania hasła dla SPA
- Wszystkie Vue pages w `resources/js/pages/` — usunięcie Inertia API

### Usuwane pliki
- `resources/js/pages/` — `usePage()`, `useForm()`, `<Link>`, `<Form>` z każdej strony
- `resources/js/composables/useClickUpApi.ts` — zastąpiony przez Axios
- Wygenerowane pliki Wayfinder w `resources/js/routes/` i `resources/js/actions/`

---

## Task 1: Konfiguracja narzędzi testowych (Vitest + Playwright)

**Files:**
- Create: `vitest.config.ts`
- Create: `playwright.config.ts`
- Modify: `package.json`

- [ ] **Krok 1: Zainstaluj zależności testowe**

```bash
npm install --save-dev vitest @vue/test-utils @vitejs/plugin-vue jsdom @playwright/test
npx playwright install --with-deps chromium
```

- [ ] **Krok 2: Utwórz `vitest.config.ts`**

```ts
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'

export default defineConfig({
    plugins: [vue()],
    test: {
        environment: 'jsdom',
        globals: true,
    },
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
        },
    },
})
```

- [ ] **Krok 3: Utwórz `playwright.config.ts`**

```ts
import { defineConfig, devices } from '@playwright/test'

export default defineConfig({
    testDir: './tests/e2e',
    use: {
        baseURL: 'http://localhost:8000',
        headless: true,
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
        },
    ],
})
```

- [ ] **Krok 4: Dodaj skrypty do `package.json`**

W sekcji `"scripts"` dodaj:

```json
"test": "vitest run",
"test:watch": "vitest",
"test:e2e": "playwright test"
```

- [ ] **Krok 5: Zweryfikuj że Vitest działa**

Utwórz `tests/js/example.test.ts`:

```ts
import { describe, it, expect } from 'vitest'

describe('sanity check', () => {
    it('works', () => {
        expect(1 + 1).toBe(2)
    })
})
```

```bash
npm test
```

Oczekiwany output: 1 test PASS.

- [ ] **Krok 6: Commit**

```bash
git add vitest.config.ts playwright.config.ts package.json package-lock.json tests/js/example.test.ts
git commit -m "feat: add vitest and playwright testing setup"
```

---

## Task 2: Zainstaluj Vue Router + Axios + Pinia, usuń Inertia + Wayfinder

**Files:**
- Modify: `package.json`
- Modify: `vite.config.ts`

- [ ] **Krok 1: Zainstaluj nowe zależności, usuń stare**

```bash
npm install vue-router@4 axios pinia
npm uninstall @inertiajs/vue3 @laravel/vite-plugin-wayfinder
```

- [ ] **Krok 2: Usuń Wayfinder plugin z `vite.config.ts`**

Otwórz `vite.config.ts`. Usuń import `wayfinder` i jego wpis z tablicy `plugins`. Nie zmieniaj nic innego.

Przykładowy wygląd `vite.config.ts` po zmianie (dostosuj do aktualnej zawartości pliku):

```ts
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import { sentryVitePlugin } from '@sentry/vite-plugin'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.ts', 'resources/css/app.css'],
            refresh: true,
        }),
        vue(),
        tailwindcss(),
        sentryVitePlugin({
            org: process.env.SENTRY_ORG,
            project: process.env.SENTRY_PROJECT,
        }),
    ],
    // ... pozostałe opcje bez zmian
})
```

- [ ] **Krok 3: Usuń wygenerowane pliki Wayfinder**

```bash
rm -rf resources/js/routes/ resources/js/actions/
```

- [ ] **Krok 4: Sprawdź że build działa**

```bash
npm run build
```

Oczekiwany output: build sukces (będą błędy TypeScript jeśli strony jeszcze importują z `@inertiajs/vue3` — to będzie naprawione w kolejnych taskach).

- [ ] **Krok 5: Commit**

```bash
git add package.json package-lock.json vite.config.ts
git commit -m "feat: add vue-router, axios, pinia; remove inertia and wayfinder"
```

---

## Task 3: Axios — konfiguracja z interceptorami + testy

**Files:**
- Create: `resources/js/lib/axios.ts`
- Create: `tests/js/lib/axios.test.ts`

- [ ] **Krok 1: Napisz testy interceptorów**

Utwórz `tests/js/lib/axios.test.ts`:

```ts
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import MockAdapter from 'axios-mock-adapter'

// Dynamiczny import żeby router był mockowany przed importem axios.ts
const mockPush = vi.fn()
vi.mock('@/router/index', () => ({
    default: { push: mockPush },
}))

// Instalacja axios-mock-adapter wymaga: npm install --save-dev axios-mock-adapter
import axiosInstance from '@/lib/axios'
const mock = new MockAdapter(axiosInstance)

beforeEach(() => {
    localStorage.clear()
    mock.reset()
    mockPush.mockClear()
})

afterEach(() => {
    mock.restore()
})

describe('axios request interceptor', () => {
    it('attaches Authorization header when token exists in localStorage', async () => {
        localStorage.setItem('token', 'my-test-token')
        // baseURL='/api' + '/user' = '/api/user'
        mock.onGet('/api/user').reply(200, { user: {} })

        await axiosInstance.get('/user').catch(() => {})
        expect(mock.history.get[0].headers?.Authorization).toBe('Bearer my-test-token')
    })

    it('does not attach Authorization header when no token', async () => {
        mock.onGet('/api/user').reply(401)

        await axiosInstance.get('/user').catch(() => {})
        expect(mock.history.get[0].headers?.Authorization).toBeUndefined()
    })
})

describe('axios response interceptor', () => {
    it('removes token and redirects to /login on 401', async () => {
        localStorage.setItem('token', 'expired-token')
        mock.onGet('/api/dashboard').reply(401)

        await axiosInstance.get('/dashboard').catch(() => {})

        expect(localStorage.getItem('token')).toBeNull()
        expect(mockPush).toHaveBeenCalledWith('/login')
    })

    it('does not redirect on 422', async () => {
        localStorage.setItem('token', 'valid-token')
        mock.onPost('/api/auth/login').reply(422, { errors: { email: ['Invalid'] } })

        await axiosInstance.post('/auth/login', {}).catch(() => {})

        expect(mockPush).not.toHaveBeenCalled()
    })
})
```

- [ ] **Krok 2: Zainstaluj axios-mock-adapter**

```bash
npm install --save-dev axios-mock-adapter
```

- [ ] **Krok 3: Uruchom testy — muszą failować**

```bash
npm test tests/js/lib/axios.test.ts
```

Oczekiwany output: FAIL (plik `@/lib/axios` nie istnieje).

- [ ] **Krok 4: Utwórz `resources/js/lib/axios.ts`**

```ts
import axios from 'axios'
import router from '@/router/index'

const axiosInstance = axios.create({
    baseURL: '/api',
    headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
    },
})

axiosInstance.interceptors.request.use((config) => {
    const token = localStorage.getItem('token')
    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }
    return config
})

axiosInstance.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token')
            router.push('/login')
        }
        return Promise.reject(error)
    },
)

export default axiosInstance
```

- [ ] **Krok 5: Uruchom testy — muszą przechodzić**

```bash
npm test tests/js/lib/axios.test.ts
```

Oczekiwany output: 4 testy PASS.

- [ ] **Krok 6: Commit**

```bash
git add resources/js/lib/axios.ts tests/js/lib/axios.test.ts package.json package-lock.json
git commit -m "feat: add axios instance with auth interceptors"
```

---

## Task 4: Auth Store (Pinia) + testy

**Files:**
- Create: `resources/js/stores/auth.ts`
- Create: `tests/js/stores/auth.test.ts`

- [ ] **Krok 1: Napisz testy**

Utwórz `tests/js/stores/auth.test.ts`:

```ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAuthStore } from '@/stores/auth'

vi.mock('@/lib/axios', () => ({
    default: {
        get: vi.fn(),
        post: vi.fn(),
    },
}))

import axiosInstance from '@/lib/axios'

beforeEach(() => {
    setActivePinia(createPinia())
    localStorage.clear()
    vi.clearAllMocks()
})

describe('useAuthStore', () => {
    it('is not authenticated by default', () => {
        const store = useAuthStore()
        expect(store.isAuthenticated).toBe(false)
        expect(store.user).toBeNull()
    })

    it('login stores token in localStorage and fetches user', async () => {
        vi.mocked(axiosInstance.post).mockResolvedValueOnce({
            data: { token: 'test-token', user: { id: 1, name: 'Jan', email: 'jan@example.com' } },
        })

        const store = useAuthStore()
        await store.login({ email: 'jan@example.com', password: 'password' })

        expect(localStorage.getItem('token')).toBe('test-token')
        expect(store.isAuthenticated).toBe(true)
        expect(store.user?.name).toBe('Jan')
    })

    it('logout removes token and clears user', async () => {
        vi.mocked(axiosInstance.post).mockResolvedValueOnce({ data: {} })

        const store = useAuthStore()
        store.$patch({ user: { id: 1, name: 'Jan', email: 'jan@example.com', google_avatar: null, email_verified_at: null } })
        localStorage.setItem('token', 'test-token')

        await store.logout()

        expect(localStorage.getItem('token')).toBeNull()
        expect(store.user).toBeNull()
        expect(store.isAuthenticated).toBe(false)
    })

    it('initialize fetches user when token exists', async () => {
        localStorage.setItem('token', 'existing-token')
        vi.mocked(axiosInstance.get).mockResolvedValueOnce({
            data: { user: { id: 1, name: 'Jan', email: 'jan@example.com' }, locale: 'pl', appearance: 'system' },
        })

        const store = useAuthStore()
        await store.initialize()

        expect(store.user?.name).toBe('Jan')
        expect(store.locale).toBe('pl')
    })

    it('initialize does nothing when no token', async () => {
        const store = useAuthStore()
        await store.initialize()

        expect(axiosInstance.get).not.toHaveBeenCalled()
        expect(store.user).toBeNull()
    })
})
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
npm test tests/js/stores/auth.test.ts
```

- [ ] **Krok 3: Utwórz `resources/js/stores/auth.ts`**

```ts
import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axiosInstance from '@/lib/axios'

interface User {
    id: number
    name: string
    email: string
    google_avatar: string | null
    email_verified_at: string | null
}

export const useAuthStore = defineStore('auth', () => {
    const user = ref<User | null>(null)
    const locale = ref<string>('pl')
    const appearance = ref<string>('system')

    const isAuthenticated = computed(() => user.value !== null)

    async function initialize(): Promise<void> {
        const token = localStorage.getItem('token')
        if (!token) return

        try {
            const { data } = await axiosInstance.get('/user')
            user.value = data.user
            locale.value = data.locale
            appearance.value = data.appearance
        } catch {
            localStorage.removeItem('token')
        }
    }

    async function login(credentials: { email: string; password: string }): Promise<{ requires_2fa?: boolean; temp_token?: string }> {
        const { data } = await axiosInstance.post('/auth/login', credentials)

        if (data.requires_2fa) {
            return { requires_2fa: true, temp_token: data.temp_token }
        }

        localStorage.setItem('token', data.token)
        user.value = data.user

        return {}
    }

    async function loginWithTwoFactor(tempToken: string, code: string): Promise<void> {
        const { data } = await axiosInstance.post('/auth/two-factor', { temp_token: tempToken, code })
        localStorage.setItem('token', data.token)
        user.value = data.user
    }

    async function logout(): Promise<void> {
        try {
            await axiosInstance.post('/auth/logout')
        } finally {
            localStorage.removeItem('token')
            user.value = null
        }
    }

    async function register(payload: { name: string; email: string; password: string; password_confirmation: string }): Promise<void> {
        const { data } = await axiosInstance.post('/auth/register', payload)
        localStorage.setItem('token', data.token)
        user.value = data.user
    }

    return { user, locale, appearance, isAuthenticated, initialize, login, loginWithTwoFactor, logout, register }
})
```

- [ ] **Krok 4: Uruchom testy — muszą przechodzić**

```bash
npm test tests/js/stores/auth.test.ts
```

Oczekiwany output: 5 testów PASS.

- [ ] **Krok 5: Commit**

```bash
git add resources/js/stores/auth.ts tests/js/stores/auth.test.ts
git commit -m "feat: add pinia auth store"
```

---

## Task 5: Vue Router z navigation guards + testy

**Files:**
- Create: `resources/js/router/index.ts`
- Create: `tests/js/router/guards.test.ts`

- [ ] **Krok 1: Napisz testy navigation guards**

Utwórz `tests/js/router/guards.test.ts`:

```ts
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'
import { setActivePinia, createPinia } from 'pinia'

// Stub components
const StubComponent = { template: '<div />' }

vi.mock('@/stores/auth', () => ({
    useAuthStore: vi.fn(() => ({
        isAuthenticated: false,
    })),
}))

import { useAuthStore } from '@/stores/auth'
import { applyGuards } from '@/router/index'

function buildRouter(authenticated: boolean) {
    vi.mocked(useAuthStore).mockReturnValue({
        isAuthenticated: authenticated,
    } as ReturnType<typeof useAuthStore>)

    const router = createRouter({
        history: createMemoryHistory(),
        routes: [
            { path: '/login', component: StubComponent, meta: { guestOnly: true } },
            { path: '/dashboard', component: StubComponent, meta: { requiresAuth: true } },
            { path: '/', component: StubComponent },
        ],
    })

    applyGuards(router)
    return router
}

beforeEach(() => {
    setActivePinia(createPinia())
})

describe('requiresAuth guard', () => {
    it('redirects to /login when unauthenticated', async () => {
        const router = buildRouter(false)
        await router.push('/dashboard')
        expect(router.currentRoute.value.path).toBe('/login')
    })

    it('allows access when authenticated', async () => {
        const router = buildRouter(true)
        await router.push('/dashboard')
        expect(router.currentRoute.value.path).toBe('/dashboard')
    })
})

describe('guestOnly guard', () => {
    it('redirects to /dashboard when authenticated', async () => {
        const router = buildRouter(true)
        await router.push('/login')
        expect(router.currentRoute.value.path).toBe('/dashboard')
    })

    it('allows access when unauthenticated', async () => {
        const router = buildRouter(false)
        await router.push('/login')
        expect(router.currentRoute.value.path).toBe('/login')
    })
})
```

- [ ] **Krok 2: Uruchom testy — muszą failować**

```bash
npm test tests/js/router/guards.test.ts
```

- [ ] **Krok 3: Utwórz `resources/js/router/index.ts`**

```ts
import { createRouter, createWebHistory, type Router } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
    { path: '/', component: () => import('@/pages/Welcome.vue') },

    // Auth (guest only)
    { path: '/login', component: () => import('@/pages/auth/Login.vue'), meta: { guestOnly: true } },
    { path: '/register', component: () => import('@/pages/auth/Register.vue'), meta: { guestOnly: true } },
    { path: '/forgot-password', component: () => import('@/pages/auth/ForgotPassword.vue'), meta: { guestOnly: true } },
    { path: '/reset-password', component: () => import('@/pages/auth/ResetPassword.vue'), meta: { guestOnly: true } },
    { path: '/two-factor', component: () => import('@/pages/auth/TwoFactorChallenge.vue'), meta: { guestOnly: true } },
    { path: '/verify-email', component: () => import('@/pages/auth/VerifyEmail.vue') },
    { path: '/confirm-password', component: () => import('@/pages/auth/ConfirmPassword.vue'), meta: { requiresAuth: true } },

    // App
    { path: '/dashboard', component: () => import('@/pages/Dashboard.vue'), meta: { requiresAuth: true } },
    { path: '/todays-tasks', component: () => import('@/pages/TodaysTasks.vue'), meta: { requiresAuth: true } },

    // Morning Hub
    { path: '/morning-hub/routine', component: () => import('@/pages/morning-hub/Routine.vue'), meta: { requiresAuth: true } },
    { path: '/morning-hub/clickup', component: () => import('@/pages/morning-hub/ClickUp.vue'), meta: { requiresAuth: true } },
    { path: '/morning-hub/google-calendar', component: () => import('@/pages/morning-hub/GoogleCalendar.vue'), meta: { requiresAuth: true } },
    { path: '/morning-hub/todays-tasks', component: () => import('@/pages/morning-hub/TodaysTasksConfig.vue'), meta: { requiresAuth: true } },
    { path: '/morning-hub/guide', component: () => import('@/pages/morning-hub/Guide.vue'), meta: { requiresAuth: true } },

    // Settings
    { path: '/settings', redirect: '/settings/profile' },
    { path: '/settings/profile', component: () => import('@/pages/settings/Profile.vue'), meta: { requiresAuth: true } },
    { path: '/settings/password', component: () => import('@/pages/settings/Password.vue'), meta: { requiresAuth: true } },
    { path: '/settings/appearance', component: () => import('@/pages/settings/Appearance.vue'), meta: { requiresAuth: true } },
    { path: '/settings/two-factor', component: () => import('@/pages/settings/TwoFactor.vue'), meta: { requiresAuth: true } },
]

export function applyGuards(router: Router): void {
    router.beforeEach((to) => {
        const auth = useAuthStore()

        if (to.meta.requiresAuth && !auth.isAuthenticated) {
            return '/login'
        }

        if (to.meta.guestOnly && auth.isAuthenticated) {
            return '/dashboard'
        }
    })
}

const router = createRouter({
    history: createWebHistory(),
    routes,
})

applyGuards(router)

export default router
```

- [ ] **Krok 4: Uruchom testy — muszą przechodzić**

```bash
npm test tests/js/router/guards.test.ts
```

Oczekiwany output: 4 testy PASS.

- [ ] **Krok 5: Commit**

```bash
git add resources/js/router/index.ts tests/js/router/guards.test.ts
git commit -m "feat: add vue router with navigation guards"
```

---

## Task 6: Nowy app.ts + app.blade.php

**Files:**
- Modify: `resources/js/app.ts`
- Modify: `resources/views/app.blade.php`

- [ ] **Krok 1: Zastąp `resources/js/app.ts`**

Odczytaj aktualną zawartość `resources/js/app.ts` żeby sprawdzić importy (Sentry itp.), zachowaj je. Nowa zawartość:

```ts
import '../css/app.css'

import { createApp } from 'vue'
import { createPinia } from 'pinia'
import * as Sentry from '@sentry/vue'

import App from './App.vue'
import router from './router/index'
import { useAuthStore } from './stores/auth'

const app = createApp(App)
const pinia = createPinia()

app.use(pinia)
app.use(router)

Sentry.init({
    app,
    dsn: import.meta.env.VITE_SENTRY_DSN_PUBLIC,
    integrations: [
        Sentry.browserTracingIntegration({ router }),
    ],
})

const authStore = useAuthStore()

authStore.initialize().then(() => {
    app.mount('#app')
})
```

- [ ] **Krok 2: Utwórz `resources/js/App.vue`** (root component jeśli nie istnieje)

Sprawdź czy `resources/js/App.vue` istnieje:

```bash
ls resources/js/App.vue 2>/dev/null || echo "not found"
```

Jeśli nie istnieje, utwórz:

```vue
<template>
    <RouterView />
</template>
```

- [ ] **Krok 3: Zastąp `resources/views/app.blade.php`**

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }}</title>
        @vite(['resources/js/app.ts', 'resources/css/app.css'])
    </head>
    <body class="antialiased">
        <div id="app"></div>
    </body>
</html>
```

- [ ] **Krok 4: Sprawdź że build przechodzi**

```bash
npm run build 2>&1 | tail -20
```

Oczekiwany output: błędy TypeScript z brakujących importów Inertia w stronach Vue — to normalne, naprawione w kolejnych taskach.

- [ ] **Krok 5: Commit**

```bash
git add resources/js/app.ts resources/js/App.vue resources/views/app.blade.php
git commit -m "feat: replace createInertiaApp with createApp + vue router + pinia"
```

---

## Task 7: Migracja stron auth

Każda strona auth usuwa `usePage()`, `useForm()`, `<Link>`, `<Form>` z Inertii i zastępuje je wywołaniami Axios przez `useAuthStore` lub bezpośrednimi wywołaniami `axiosInstance`.

**Files:**
- Modify: `resources/js/pages/auth/Login.vue`
- Modify: `resources/js/pages/auth/Register.vue`
- Modify: `resources/js/pages/auth/ForgotPassword.vue`
- Modify: `resources/js/pages/auth/ResetPassword.vue`
- Modify: `resources/js/pages/auth/TwoFactorChallenge.vue`
- Modify: `resources/js/pages/auth/VerifyEmail.vue`
- Modify: `resources/js/pages/auth/ConfirmPassword.vue`

- [ ] **Krok 1: Zaktualizuj `Login.vue`**

Usuń z `<script setup>`:
- `import { useForm, Link } from '@inertiajs/vue3'`
- `const form = useForm({ email: '', password: '' })`
- `form.post(route('login'))`

Dodaj:
```ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const email = ref('')
const password = ref('')
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)
const tempToken = ref<string | null>(null)

async function submit() {
    isLoading.value = true
    errors.value = {}
    try {
        const result = await auth.login({ email: email.value, password: password.value })
        if (result.requires_2fa) {
            tempToken.value = result.temp_token!
            router.push('/two-factor')
            // Zapisz tempToken w sessionStorage żeby TwoFactorChallenge.vue miał do niego dostęp
            sessionStorage.setItem('2fa_temp_token', result.temp_token!)
        } else {
            router.push('/dashboard')
        }
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    } finally {
        isLoading.value = false
    }
}
```

W `<template>` zastąp `<Link>` przez `<RouterLink>` i `<Form @submit>` przez `<form @submit.prevent="submit">`. Wyświetlanie błędów: `errors['email']?.[0]`.

- [ ] **Krok 2: Zaktualizuj `Register.vue`**

```ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const form = ref({ name: '', email: '', password: '', password_confirmation: '' })
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

async function submit() {
    isLoading.value = true
    errors.value = {}
    try {
        await auth.register(form.value)
        router.push('/dashboard')
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    } finally {
        isLoading.value = false
    }
}
```

- [ ] **Krok 3: Zaktualizuj `ForgotPassword.vue`**

```ts
import { ref } from 'vue'
import axiosInstance from '@/lib/axios'

const email = ref('')
const status = ref<string | null>(null)
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

async function submit() {
    isLoading.value = true
    errors.value = {}
    try {
        await axiosInstance.post('/auth/forgot-password', { email: email.value })
        status.value = 'Password reset link sent.'
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    } finally {
        isLoading.value = false
    }
}
```

- [ ] **Krok 4: Zaktualizuj `ResetPassword.vue`**

Token i email są przekazywane przez URL query params (`/reset-password?token=...&email=...`):

```ts
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import axiosInstance from '@/lib/axios'

const route = useRoute()
const router = useRouter()
const token = route.query.token as string
const email = ref((route.query.email as string) ?? '')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

async function submit() {
    isLoading.value = true
    errors.value = {}
    try {
        await axiosInstance.post('/auth/reset-password', {
            token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        })
        router.push('/login')
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    } finally {
        isLoading.value = false
    }
}
```

**Uwaga:** Upewnij się że konfiguracja Laravel w `config/app.php` lub `AppServiceProvider` ustawia URL resetowania hasła na `/reset-password` (adres SPA), nie na klasyczny route Laravel.

Dodaj do `AppServiceProvider::boot()`:
```php
\Illuminate\Auth\Notifications\ResetPassword::createUrlUsing(function ($notifiable, $token) {
    return config('app.url') . '/reset-password?token=' . $token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
});
```

- [ ] **Krok 5: Zaktualizuj `TwoFactorChallenge.vue`**

```ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const code = ref('')
const errors = ref<Record<string, string[]>>({})
const isLoading = ref(false)

async function submit() {
    isLoading.value = true
    errors.value = {}
    const tempToken = sessionStorage.getItem('2fa_temp_token') ?? ''
    try {
        await auth.loginWithTwoFactor(tempToken, code.value)
        sessionStorage.removeItem('2fa_temp_token')
        router.push('/dashboard')
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    } finally {
        isLoading.value = false
    }
}
```

- [ ] **Krok 6: Zaktualizuj `VerifyEmail.vue` i `ConfirmPassword.vue`**

`VerifyEmail.vue` — usuń Inertia props, dodaj:
```ts
import axiosInstance from '@/lib/axios'
const status = ref<string | null>(null)

async function resend() {
    await axiosInstance.post('/email/verification-notification')
    status.value = 'Verification link sent.'
}
```

`ConfirmPassword.vue` — usuń Inertia form, dodaj:
```ts
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axiosInstance from '@/lib/axios'

const router = useRouter()
const password = ref('')
const errors = ref<Record<string, string[]>>({})

async function submit() {
    try {
        await axiosInstance.post('/user/confirm-password', { password: password.value })
        router.back()
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors
        }
    }
}
```

- [ ] **Krok 7: Zamień wszystkie `<Link>` na `<RouterLink>` w szablonach**

```bash
grep -rn "from '@inertiajs/vue3'" resources/js/pages/auth/
```

Żadnych pozostałości `@inertiajs/vue3` w folderze `auth/`.

- [ ] **Krok 8: Commit**

```bash
git add resources/js/pages/auth/ app/Providers/AppServiceProvider.php
git commit -m "feat: migrate auth pages from inertia to vue router + axios"
```

---

## Task 8: Migracja stron Dashboard, TodaysTasks, MorningHub

**Files:**
- Modify: `resources/js/pages/Dashboard.vue`
- Modify: `resources/js/pages/TodaysTasks.vue`
- Modify: `resources/js/pages/morning-hub/Routine.vue`
- Modify: `resources/js/pages/morning-hub/ClickUp.vue`
- Modify: `resources/js/pages/morning-hub/GoogleCalendar.vue`
- Modify: `resources/js/pages/morning-hub/TodaysTasksConfig.vue`
- Modify: `resources/js/pages/morning-hub/Guide.vue`

Ogólny wzorzec dla każdej strony:

1. Usuń `import { usePage } from '@inertiajs/vue3'` i `const page = usePage()`
2. Usuń `import { useForm } from '@inertiajs/vue3'` i `const form = useForm(...)`
3. Zastąp `page.props.X` przez `ref(null)` + `onMounted(() => axiosInstance.get('/api-endpoint'))`
4. Zastąp `form.post(route('X'))` przez `axiosInstance.post('/api-endpoint', formData.value)`
5. Usuń `<Link>` → `<RouterLink>`, `<Form>` → `<form @submit.prevent>`
6. Zastąp Inertia redirect (po submit) przez `router.push('/route')`

- [ ] **Krok 1: Zaktualizuj `Dashboard.vue`**

Usuń `usePage`, `Inertia::defer` props. Dane bloków ładowane przez API:

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'

const blocks = ref([])
const isLoading = ref(true)

onMounted(async () => {
    try {
        const { data } = await axiosInstance.get('/dashboard')
        blocks.value = data.blocks
    } finally {
        isLoading.value = false
    }
})
```

- [ ] **Krok 2: Zaktualizuj `TodaysTasks.vue`**

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'

const config = ref(null)
const connections = ref([])

onMounted(async () => {
    const { data } = await axiosInstance.get('/todays-tasks')
    config.value = data.config
    connections.value = data.connections
})
```

- [ ] **Krok 3: Zaktualizuj `morning-hub/Routine.vue`**

```ts
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axiosInstance from '@/lib/axios'

const router = useRouter()
const blocks = ref([])
const connections = ref([])

onMounted(async () => {
    const { data } = await axiosInstance.get('/morning-hub/routine')
    blocks.value = data.blocks
    connections.value = data.connections
})

async function createBlock(formData: object) {
    await axiosInstance.post('/morning-hub/routine/blocks', formData)
    const { data } = await axiosInstance.get('/morning-hub/routine')
    blocks.value = data.blocks
}

async function updateBlock(id: number, formData: object) {
    await axiosInstance.put(`/morning-hub/routine/blocks/${id}`, formData)
    const { data } = await axiosInstance.get('/morning-hub/routine')
    blocks.value = data.blocks
}

async function deleteBlock(id: number) {
    await axiosInstance.delete(`/morning-hub/routine/blocks/${id}`)
    blocks.value = blocks.value.filter((b: any) => b.id !== id)
}

async function reorderBlocks(orderedIds: number[]) {
    await axiosInstance.patch('/morning-hub/routine/blocks/reorder', { blocks: orderedIds })
}
```

- [ ] **Krok 4: Zaktualizuj `morning-hub/ClickUp.vue`**

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'

const connections = ref([])

onMounted(async () => {
    const { data } = await axiosInstance.get('/morning-hub/clickup')
    connections.value = data.connections
})

async function createConnection(formData: object) {
    await axiosInstance.post('/morning-hub/clickup/connections', formData)
    const { data } = await axiosInstance.get('/morning-hub/clickup')
    connections.value = data.connections
}
```

- [ ] **Krok 5: Zaktualizuj pozostałe strony MorningHub**

Powtórz wzorzec z kroków 3–4 dla:
- `GoogleCalendar.vue` → `GET /api/morning-hub/google-calendar`, `PUT /api/morning-hub/google-calendar`
- `TodaysTasksConfig.vue` → `GET /api/morning-hub/todays-tasks`, `PUT /api/morning-hub/todays-tasks`
- `Guide.vue` → brak API, usuń tylko Inertia imports

- [ ] **Krok 6: Sprawdź brak pozostałości Inertia**

```bash
grep -rn "from '@inertiajs/vue3'" resources/js/pages/morning-hub/ resources/js/pages/Dashboard.vue resources/js/pages/TodaysTasks.vue
```

Oczekiwany output: brak wyników.

- [ ] **Krok 7: Commit**

```bash
git add resources/js/pages/Dashboard.vue resources/js/pages/TodaysTasks.vue resources/js/pages/morning-hub/
git commit -m "feat: migrate dashboard and morning-hub pages to api"
```

---

## Task 9: Migracja stron Settings + Welcome

**Files:**
- Modify: `resources/js/pages/settings/Profile.vue`
- Modify: `resources/js/pages/settings/Password.vue`
- Modify: `resources/js/pages/settings/Appearance.vue`
- Modify: `resources/js/pages/settings/TwoFactor.vue`
- Modify: `resources/js/pages/Welcome.vue`

- [ ] **Krok 1: Zaktualizuj `settings/Profile.vue`**

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const name = ref(auth.user?.name ?? '')
const email = ref(auth.user?.email ?? '')
const errors = ref<Record<string, string[]>>({})

async function updateProfile() {
    errors.value = {}
    try {
        await axiosInstance.patch('/settings/profile', { name: name.value, email: email.value })
        await auth.initialize()
    } catch (error: any) {
        if (error.response?.status === 422) errors.value = error.response.data.errors
    }
}

async function deleteAccount() {
    await axiosInstance.delete('/settings/profile', { data: { password: deletePassword.value } })
    localStorage.removeItem('token')
    location.href = '/'
}
```

- [ ] **Krok 2: Zaktualizuj `settings/Password.vue`**

```ts
import { ref } from 'vue'
import axiosInstance from '@/lib/axios'

const currentPassword = ref('')
const password = ref('')
const passwordConfirmation = ref('')
const errors = ref<Record<string, string[]>>({})
const success = ref(false)

async function updatePassword() {
    errors.value = {}
    success.value = false
    try {
        await axiosInstance.put('/settings/password', {
            current_password: currentPassword.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        })
        success.value = true
        currentPassword.value = ''
        password.value = ''
        passwordConfirmation.value = ''
    } catch (error: any) {
        if (error.response?.status === 422) errors.value = error.response.data.errors
    }
}
```

- [ ] **Krok 3: Zaktualizuj `settings/TwoFactor.vue`**

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'

const twoFactorEnabled = ref(false)
const requiresConfirmation = ref(false)

onMounted(async () => {
    const { data } = await axiosInstance.get('/settings/two-factor')
    twoFactorEnabled.value = data.twoFactorEnabled
    requiresConfirmation.value = data.requiresConfirmation
})
```

- [ ] **Krok 4: Zaktualizuj `settings/Appearance.vue`**

Strona zarządza motywem przez cookie — logika `useAppearance.ts` nie używa Inertii. Usuń tylko Inertia imports jeśli są obecne.

- [ ] **Krok 5: Zaktualizuj `Welcome.vue`**

Usuń `import { usePage } from '@inertiajs/vue3'` i `page.props.canRegister`. Zastąp:

```ts
import { ref, onMounted } from 'vue'
import axiosInstance from '@/lib/axios'

const canRegister = ref(true)
```

Lub wczytaj przez `GET /api/config` jeśli potrzeba — albo wpisz na sztywno `true` (rejestracja jest dostępna gdy Fortify ją włącza, co jest konfiguracją stałą).

- [ ] **Krok 6: Sprawdź brak pozostałości Inertia**

```bash
grep -rn "from '@inertiajs/vue3'" resources/js/pages/
```

Oczekiwany output: brak wyników.

- [ ] **Krok 7: Commit**

```bash
git add resources/js/pages/settings/ resources/js/pages/Welcome.vue
git commit -m "feat: migrate settings and welcome pages to api"
```

---

## Task 10: Usunięcie useClickUpApi.ts + cleanup composables

**Files:**
- Delete: `resources/js/composables/useClickUpApi.ts`
- Modify: pliki które importują `useClickUpApi`

- [ ] **Krok 1: Znajdź wszystkie użycia `useClickUpApi`**

```bash
grep -rn "useClickUpApi" resources/js/
```

- [ ] **Krok 2: Zastąp `useClickUpApi` przez `axiosInstance`**

W każdym pliku który importuje `useClickUpApi`, zastąp:
```ts
// Przed
import { useClickUpApi } from '@/composables/useClickUpApi'
const { fetchJson } = useClickUpApi()
const data = await fetchJson('/morning-hub/clickup/...')

// Po
import axiosInstance from '@/lib/axios'
const { data } = await axiosInstance.get('/morning-hub/clickup/...')
```

- [ ] **Krok 3: Usuń `useClickUpApi.ts`**

```bash
rm resources/js/composables/useClickUpApi.ts
```

- [ ] **Krok 4: Sprawdź TypeScript — zero błędów**

```bash
npm run types:check
```

Napraw wszystkie błędy TypeScript przed przejściem do kolejnego kroku.

- [ ] **Krok 5: Commit**

```bash
git add -A
git commit -m "refactor: replace useClickUpApi composable with axios instance"
```

---

## Task 11: Build + testy E2E

**Files:**
- Create: `tests/e2e/auth.spec.ts`

- [ ] **Krok 1: Uruchom pełny build**

```bash
npm run build
```

Oczekiwany output: build sukces, zero błędów TypeScript.

- [ ] **Krok 2: Uruchom testy jednostkowe**

```bash
npm test
```

Oczekiwany output: wszystkie testy PASS.

- [ ] **Krok 3: Uruchom serwer deweloperski**

W osobnym terminalu:
```bash
php artisan serve
```

- [ ] **Krok 4: Napisz testy E2E**

Utwórz `tests/e2e/auth.spec.ts`:

```ts
import { test, expect } from '@playwright/test'

test.describe('authentication', () => {
    test('unauthenticated user is redirected to /login when visiting /dashboard', async ({ page }) => {
        await page.goto('/dashboard')
        await expect(page).toHaveURL(/.*login/)
    })

    test('user can login with valid credentials', async ({ page }) => {
        // Wymagane: użytkownik z tymi danymi musi istnieć w testowej bazie
        await page.goto('/login')
        await page.fill('input[type="email"]', 'test@example.com')
        await page.fill('input[type="password"]', 'password')
        await page.click('button[type="submit"]')
        await expect(page).toHaveURL(/.*dashboard/)
    })

    test('authenticated user is redirected from /login to /dashboard', async ({ page }) => {
        // Zaloguj się najpierw przez API
        const response = await page.request.post('/api/auth/login', {
            data: { email: 'test@example.com', password: 'password' },
        })
        const { token } = await response.json()
        await page.evaluate((t) => localStorage.setItem('token', t), token)

        await page.goto('/login')
        await expect(page).toHaveURL(/.*dashboard/)
    })
})
```

- [ ] **Krok 5: Uruchom testy E2E**

```bash
npm run test:e2e
```

Jeśli brak użytkownika testowego, utwórz przez seeder lub `php artisan tinker`.

- [ ] **Krok 6: Commit końcowy Etapu 2**

```bash
git add -A
git commit -m "feat: complete frontend spa migration (etap 2)"
```

---

## Task 12: Weryfikacja końcowa

- [ ] **Krok 1: Pełne testy PHP**

```bash
php artisan test --compact
```

Oczekiwany output: wszystkie testy PASS.

- [ ] **Krok 2: Pełne testy JS**

```bash
npm test
```

Oczekiwany output: wszystkie testy PASS.

- [ ] **Krok 3: TypeScript check**

```bash
npm run types:check
```

Zero błędów.

- [ ] **Krok 4: Lint**

```bash
npm run lint:check
```

Zero błędów.

- [ ] **Krok 5: Production build**

```bash
npm run build
```

Sukces bez błędów.

- [ ] **Krok 6: Sprawdź że @inertiajs/vue3 nie jest nigdzie importowany**

```bash
grep -rn "@inertiajs/vue3" resources/js/ --include="*.ts" --include="*.vue"
```

Oczekiwany output: brak wyników.

- [ ] **Krok 7: Commit końcowy**

```bash
git add -A
git commit -m "chore: final verification — inertia fully removed"
```

---

## Notatki implementacyjne

**`useClickUpApi.ts` używa już `fetch` z CSRF tokenem** — Axios z Bearer tokenem zastępuje ten mechanizm. CSRF nie jest potrzebny przy auth przez nagłówek Bearer.

**`useTwoFactorAuth.ts`** — composable który fetchuje QR kod dla 2FA setup. Używa `fetch` bezpośrednio. Zastąp wywołania przez `axiosInstance`.

**Fortify 2FA management routes** (enable, disable, confirm) — Fortify rejestruje je jako web routes. Frontend w `settings/TwoFactor.vue` może je nadal wywoływać przez Axios (jako web requests z cookie session) lub można je przepisać na API. Na tym etapie zostaw jak jest — Fortify routes działają.

**Password reset email link** — konfiguracja w `AppServiceProvider` (Task 7, Krok 4) jest krytyczna. Bez niej link w emailu będzie prowadził do starego Laravel route zamiast do SPA.

**Email verification** — Laravel wysyła link do `GET /email/verify/{id}/{hash}` który jest web route Fortify. Po weryfikacji Fortify robi redirect do `config('fortify.home')` = `/dashboard`. SPA obsługuje ten redirect przez catch-all route.

**Google account linking** (`/auth/google/link`) — pozostaje jako server-side redirect. Przycisk w `Profile.vue` powinien być zwykłym `<a href="/auth/google/link">` nie `<RouterLink>` — bo to web route, nie SPA route.
