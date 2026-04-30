<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import axiosInstance from '@/lib/axios';

const { t } = useTranslations();
const router = useRouter();
const password = ref('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        await axiosInstance.post('/user/confirm-password', {
            password: password.value,
        });
        router.back();
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
        :title="t('Potwierdź hasło')"
        :description="
            t(
                'To jest bezpieczna strefa aplikacji. Potwierdź swoje hasło, aby kontynuować.',
            )
        "
    >
        <form @submit.prevent="submit">
            <div class="space-y-6">
                <div class="grid gap-2">
                    <Label htmlFor="password">{{ t('Hasło') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                        v-model="password"
                    />

                    <InputError :message="errors['password']?.[0]" />
                </div>

                <div class="flex items-center">
                    <Button
                        class="w-full"
                        :disabled="isLoading"
                        data-test="confirm-password-button"
                    >
                        <Spinner v-if="isLoading" />
                        {{ t('Potwierdź hasło') }}
                    </Button>
                </div>
            </div>
        </form>
    </AuthLayout>
</template>
