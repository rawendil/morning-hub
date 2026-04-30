<script setup lang="ts">
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import axiosInstance from '@/lib/axios';

const { t } = useTranslations();
const emailValue = ref('');
const status = ref<string | null>(null);
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        await axiosInstance.post('/auth/forgot-password', {
            email: emailValue.value,
        });
        status.value = 'Password reset link sent.';
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AuthLayout
        :title="t('Zapomniałeś hasła')"
        :description="
            t('Wprowadź adres e-mail, aby otrzymać link do resetowania hasła')
        "
    >
        <div
            v-if="status"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            {{ status }}
        </div>

        <div class="space-y-6">
            <form @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="email">{{ t('Adres e-mail') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="off"
                        autofocus
                        placeholder="email@example.com"
                        v-model="emailValue"
                    />
                    <InputError :message="errors['email']?.[0]" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button
                        class="w-full"
                        :disabled="isLoading"
                        data-test="email-password-reset-link-button"
                    >
                        <Spinner v-if="isLoading" />
                        {{ t('Wyślij link do resetowania hasła') }}
                    </Button>
                </div>
            </form>

            <div class="space-x-1 text-center text-sm text-muted-foreground">
                <span>{{ t('Lub wróć do') }}</span>
                <TextLink href="/login">{{ t('logowania') }}</TextLink>
            </div>
        </div>
    </AuthLayout>
</template>
