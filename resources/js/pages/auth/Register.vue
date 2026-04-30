<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import SocialLoginButton from '@/components/SocialLoginButton.vue';
import SocialLoginSeparator from '@/components/SocialLoginSeparator.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useTranslations();
const router = useRouter();
const auth = useAuthStore();
const form = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        await auth.register(form.value);
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
    <AuthBase
        :title="t('Utwórz konto')"
        :description="t('Wprowadź swoje dane, aby utworzyć konto')"
    >
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="name">{{ t('Imię') }}</Label>
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        :placeholder="t('Imię i nazwisko')"
                        v-model="form.name"
                    />
                    <InputError :message="errors['name']?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">{{ t('Adres e-mail') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                        v-model="form.email"
                    />
                    <InputError :message="errors['email']?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">{{ t('Hasło') }}</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="3"
                        autocomplete="new-password"
                        name="password"
                        :placeholder="t('Hasło')"
                        v-model="form.password"
                    />
                    <InputError :message="errors['password']?.[0]" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">{{
                        t('Potwierdź hasło')
                    }}</Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        required
                        :tabindex="4"
                        autocomplete="new-password"
                        name="password_confirmation"
                        :placeholder="t('Potwierdź hasło')"
                        v-model="form.password_confirmation"
                    />
                    <InputError
                        :message="errors['password_confirmation']?.[0]"
                    />
                </div>

                <Button
                    type="submit"
                    class="mt-2 w-full"
                    tabindex="5"
                    :disabled="isLoading"
                    data-test="register-user-button"
                >
                    <Spinner v-if="isLoading" />
                    {{ t('Utwórz konto') }}
                </Button>
            </div>

            <SocialLoginSeparator />
            <SocialLoginButton />

            <div class="text-center text-sm text-muted-foreground">
                {{ t('Masz już konto?') }}
                <TextLink
                    href="/login"
                    class="underline underline-offset-4"
                    :tabindex="6"
                    >{{ t('Zaloguj się') }}</TextLink
                >
            </div>
        </form>
    </AuthBase>
</template>
