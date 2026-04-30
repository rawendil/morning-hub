<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import TextLink from '@/components/TextLink.vue'
import { Button } from '@/components/ui/button'
import { Spinner } from '@/components/ui/spinner'
import { useTranslations } from '@/composables/useTranslations'
import AuthLayout from '@/layouts/AuthLayout.vue'
import axiosInstance from '@/lib/axios'
import { useAuthStore } from '@/stores/auth'

const { t } = useTranslations()
const router = useRouter()
const auth = useAuthStore()
const status = ref<string | null>(null)
const isLoading = ref(false)

async function resend() {
    isLoading.value = true
    try {
        await axiosInstance.post('/email/verification-notification')
        status.value = 'verification-link-sent'
    } finally {
        isLoading.value = false
    }
}

async function handleLogout() {
    await auth.logout()
    router.push('/login')
}
</script>

<template>
    <AuthLayout
        :title="t('Weryfikacja e-mail')"
        :description="
            t(
                'Zweryfikuj swój adres e-mail, klikając w link, który wysłaliśmy na Twój adres.',
            )
        "
    >
        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{
                t(
                    'Nowy link weryfikacyjny został wysłany na adres e-mail podany podczas rejestracji.',
                )
            }}
        </div>

        <form @submit.prevent="resend" class="space-y-6 text-center">
            <Button :disabled="isLoading" variant="secondary">
                <Spinner v-if="isLoading" />
                {{ t('Wyślij ponownie e-mail weryfikacyjny') }}
            </Button>

            <TextLink
                href="/login"
                as="button"
                class="mx-auto block text-sm"
                @click.prevent="handleLogout"
            >
                {{ t('Wyloguj się') }}
            </TextLink>
        </form>
    </AuthLayout>
</template>
