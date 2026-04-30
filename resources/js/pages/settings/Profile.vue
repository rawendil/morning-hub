<script setup lang="ts">
import { computed, ref } from 'vue';
import axiosInstance from '@/lib/axios';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import LinkedAccounts from '@/components/LinkedAccounts.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useAuthStore } from '@/stores/auth';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

const { t } = useTranslations();
const auth = useAuthStore();
const user = computed(() => auth.user);

const name = ref(auth.user?.name ?? '');
const email = ref(auth.user?.email ?? '');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);
const recentlySuccessful = ref(false);
const verificationStatus = ref<string | null>(null);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('Ustawienia profilu'),
        href: '/settings/profile',
    },
]);

async function updateProfile() {
    isLoading.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    try {
        await axiosInstance.patch('/settings/profile', { name: name.value, email: email.value });
        recentlySuccessful.value = true;
        await auth.initialize();
        setTimeout(() => { recentlySuccessful.value = false; }, 2000);
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
    } finally {
        isLoading.value = false;
    }
}

async function resendVerification() {
    await axiosInstance.post('/email/verification-notification');
    verificationStatus.value = 'verification-link-sent';
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <h1 class="sr-only">{{ t('Ustawienia profilu') }}</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    :title="t('Dane profilu')"
                    :description="t('Zaktualizuj swoje imię i adres e-mail')"
                />

                <form @submit.prevent="updateProfile" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">{{ t('Imię') }}</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            v-model="name"
                            required
                            autocomplete="name"
                            :placeholder="t('Imię i nazwisko')"
                        />
                        <InputError class="mt-2" :message="errors['name']?.[0]" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">{{ t('Adres e-mail') }}</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="email"
                            required
                            autocomplete="username"
                            :placeholder="t('Adres e-mail')"
                        />
                        <InputError class="mt-2" :message="errors['email']?.[0]" />
                    </div>

                    <div v-if="user && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            {{ t('Twój adres e-mail nie jest zweryfikowany.') }}
                            <button
                                type="button"
                                @click="resendVerification"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                {{
                                    t(
                                        'Kliknij tutaj, aby ponownie wysłać e-mail weryfikacyjny.',
                                    )
                                }}
                            </button>
                        </p>

                        <div
                            v-if="verificationStatus === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            {{
                                t(
                                    'Nowy link weryfikacyjny został wysłany na Twój adres e-mail.',
                                )
                            }}
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="isLoading"
                            data-test="update-profile-button"
                            >{{ t('Zapisz') }}</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                {{ t('Zapisano.') }}
                            </p>
                        </Transition>
                    </div>
                </form>
            </div>

            <LinkedAccounts />

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
