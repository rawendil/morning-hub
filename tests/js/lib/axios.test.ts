import MockAdapter from 'axios-mock-adapter'
import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'

const mockPush = vi.hoisted(() => vi.fn())
vi.mock('@/router/index', () => ({
    default: { push: mockPush },
}))

import axiosInstance from '@/lib/axios'
const mock = new MockAdapter(axiosInstance)

beforeEach(() => {
    localStorage.clear()
    mock.reset()
    mockPush.mockClear()
})

afterEach(() => {
    mock.reset()
})

describe('axios request interceptor', () => {
    it('attaches Authorization header when token exists in localStorage', async () => {
        localStorage.setItem('token', 'my-test-token')
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
