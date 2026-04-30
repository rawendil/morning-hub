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
