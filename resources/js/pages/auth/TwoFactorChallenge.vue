<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { useTranslations } from '@/composables/useTranslations';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';
import type { TwoFactorConfigContent } from '@/types';

const { t } = useTranslations();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const code = ref<string>('');
const recoveryCode = ref<string>('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);
const showRecoveryInput = ref<boolean>(false);

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: t('Kod odzyskiwania'),
            description: t(
                'Potwierdź dostęp do swojego konta, wprowadzając jeden z kodów odzyskiwania.',
            ),
            buttonText: t('zaloguj się kodem uwierzytelniającym'),
        };
    }
    return {
        title: t('Kod uwierzytelniający'),
        description: t(
            'Wprowadź kod uwierzytelniający z aplikacji authenticator.',
        ),
        buttonText: t('zaloguj się kodem odzyskiwania'),
    };
});

onMounted(() => {
    const tempToken = route.query.temp_token as string | undefined;
    if (tempToken) {
        sessionStorage.setItem('2fa_temp_token', tempToken);
        router.replace({ path: '/two-factor', query: {} });
    }
});

function toggleRecoveryMode() {
    showRecoveryInput.value = !showRecoveryInput.value;
    errors.value = {};
    code.value = '';
    recoveryCode.value = '';
}

async function submit() {
    isLoading.value = true;
    errors.value = {};
    const tempToken = sessionStorage.getItem('2fa_temp_token') ?? '';
    const codeToSubmit = showRecoveryInput.value
        ? recoveryCode.value
        : code.value;
    try {
        await auth.loginWithTwoFactor(tempToken, codeToSubmit);
        sessionStorage.removeItem('2fa_temp_token');
        router.push('/dashboard');
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
        :title="authConfigContent.title"
        :description="authConfigContent.description"
    >
        <div class="space-y-6">
            <template v-if="!showRecoveryInput">
                <form @submit.prevent="submit" class="space-y-4">
                    <div
                        class="flex flex-col items-center justify-center space-y-3 text-center"
                    >
                        <div class="flex w-full items-center justify-center">
                            <InputOTP
                                id="otp"
                                v-model="code"
                                :maxlength="6"
                                :disabled="isLoading"
                                autofocus
                            >
                                <InputOTPGroup>
                                    <InputOTPSlot
                                        v-for="index in 6"
                                        :key="index"
                                        :index="index - 1"
                                    />
                                </InputOTPGroup>
                            </InputOTP>
                        </div>
                        <InputError :message="errors['code']?.[0]" />
                    </div>
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="isLoading"
                        >{{ t('Kontynuuj') }}</Button
                    >
                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ t('lub możesz') }} </span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </form>
            </template>

            <template v-else>
                <form @submit.prevent="submit" class="space-y-4">
                    <Input
                        type="text"
                        :placeholder="t('Wprowadź kod odzyskiwania')"
                        :autofocus="showRecoveryInput"
                        required
                        v-model="recoveryCode"
                    />
                    <InputError :message="errors['recovery_code']?.[0]" />
                    <Button
                        type="submit"
                        class="w-full"
                        :disabled="isLoading"
                        >{{ t('Kontynuuj') }}</Button
                    >

                    <div class="text-center text-sm text-muted-foreground">
                        <span>{{ t('lub możesz') }} </span>
                        <button
                            type="button"
                            class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            @click="toggleRecoveryMode"
                        >
                            {{ authConfigContent.buttonText }}
                        </button>
                    </div>
                </form>
            </template>
        </div>
    </AuthLayout>
</template>
