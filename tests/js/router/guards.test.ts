import { setActivePinia, createPinia } from 'pinia'
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createRouter, createMemoryHistory } from 'vue-router'

const StubComponent = { template: '<div />' }

vi.mock('@/stores/auth', () => ({
    useAuthStore: vi.fn(() => ({
        isAuthenticated: false,
    })),
}))

import { applyGuards } from '@/router/index'
import { useAuthStore } from '@/stores/auth'

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
