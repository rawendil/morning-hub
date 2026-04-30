<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import InputError from '@/components/InputError.vue';
import SocialLoginButton from '@/components/SocialLoginButton.vue';
import SocialLoginSeparator from '@/components/SocialLoginSeparator.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AuthBase from '@/layouts/AuthLayout.vue';
import { useAuthStore } from '@/stores/auth';

const { t } = useTranslations();
const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const email = ref('');
const password = ref('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

onMounted(async () => {
    const googleToken = route.query.google_token as string | undefined;
    if (googleToken) {
        localStorage.setItem('token', googleToken);
        await auth.initialize();
        router.replace('/dashboard');
    }
});

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        const result = await auth.login({
            email: email.value,
            password: password.value,
        });
        if (result.requires_2fa) {
            sessionStorage.setItem('2fa_temp_token', result.temp_token!);
            router.push('/two-factor');
        } else {
            router.push('/dashboard');
        }
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
        :title="t('Zaloguj się na swoje konto')"
        :description="t('Wprowadź adres e-mail i hasło, aby się zalogować')"
    >
        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">{{ t('Adres e-mail') }}</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        v-model="email"
                    />
                    <InputError :message="errors['email']?.[0]" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label for="password">{{ t('Hasło') }}</Label>
                        <TextLink
                            href="/forgot-password"
                            class="text-sm"
                            :tabindex="5"
                        >
                            {{ t('Zapomniałeś hasła?') }}
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        :placeholder="t('Hasło')"
                        v-model="password"
                    />
                    <InputError :message="errors['password']?.[0]" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="remember" class="flex items-center space-x-3">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span>{{ t('Zapamiętaj mnie') }}</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-4 w-full"
                    :tabindex="4"
                    :disabled="isLoading"
                    data-test="login-button"
                >
                    <Spinner v-if="isLoading" />
                    {{ t('Zaloguj się') }}
                </Button>
            </div>

            <SocialLoginSeparator />
            <SocialLoginButton />

            <div class="text-center text-sm text-muted-foreground">
                {{ t('Nie masz konta?') }}
                <TextLink href="/register" :tabindex="5">{{
                    t('Zarejestruj się')
                }}</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
