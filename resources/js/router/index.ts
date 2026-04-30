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
