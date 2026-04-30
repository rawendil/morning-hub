<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import axiosInstance from '@/lib/axios';

const { t } = useTranslations();
const route = useRoute();
const router = useRouter();
const token = route.query.token as string;
const email = ref((route.query.email as string) ?? '');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        await axiosInstance.post('/auth/reset-password', {
            token,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        router.push('/login');
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
        :title="t('Resetowanie hasła')"
        :description="t('Wprowadź nowe hasło poniżej')"
    >
        <form @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('E-mail') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="email"
                        v-model="email"
                        class="mt-1 block w-full"
                        readonly
                    />
                    <InputError :message="errors['email']?.[0]" class="mt-2" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('Hasło') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        autofocus
                        :placeholder="t('Hasło')"
                        v-model="password"
                    />
                    <InputError :message="errors['password']?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        {{ t('Potwierdź hasło') }}
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        autocomplete="new-password"
                        class="mt-1 block w-full"
                        :placeholder="t('Potwierdź hasło')"
                        v-model="passwordConfirmation"
                    />
                    <InputError
                        :message="errors['password_confirmation']?.[0]"
                    />
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :disabled="isLoading"
                    data-test="reset-password-button"
                >
                    <Spinner v-if="isLoading" />
                    {{ t('Zresetuj hasło') }}
                </Button>
            </div>
        </form>
    </AuthLayout>
</template>
