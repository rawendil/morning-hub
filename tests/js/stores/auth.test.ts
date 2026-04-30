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
