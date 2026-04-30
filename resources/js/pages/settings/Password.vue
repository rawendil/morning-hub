<script setup lang="ts">
import { computed, ref } from 'vue';
import axiosInstance from '@/lib/axios';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

const { t } = useTranslations();

const hasPassword = true;

const currentPassword = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);
const recentlySuccessful = ref(false);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: t('Ustawienia hasła'),
        href: '/settings/password',
    },
]);

async function updatePassword() {
    isLoading.value = true;
    errors.value = {};
    recentlySuccessful.value = false;
    try {
        await axiosInstance.put('/settings/password', {
            current_password: currentPassword.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });
        recentlySuccessful.value = true;
        currentPassword.value = '';
        password.value = '';
        passwordConfirmation.value = '';
        setTimeout(() => { recentlySuccessful.value = false; }, 2000);
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
    <AppLayout :breadcrumbs="breadcrumbItems">
        <h1 class="sr-only">{{ t('Ustawienia hasła') }}</h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="hasPassword ? t('Zmiana hasła') : t('Ustaw hasło')"
                    :description="
                        hasPassword
                            ? t(
                                  'Upewnij się, że Twoje konto używa długiego, losowego hasła',
                              )
                            : t('Ustaw hasło, aby móc logować się tradycyjnie')
                    "
                />

                <form
                    @submit.prevent="updatePassword"
                    class="space-y-6"
                >
                    <div v-if="hasPassword" class="grid gap-2">
                        <Label for="current_password">{{
                            t('Aktualne hasło')
                        }}</Label>
                        <Input
                            id="current_password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="currentPassword"
                            autocomplete="current-password"
                            :placeholder="t('Aktualne hasło')"
                        />
                        <InputError :message="errors['current_password']?.[0]" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="password">{{
                            hasPassword ? t('Nowe hasło') : t('Hasło')
                        }}</Label>
                        <Input
                            id="password"
                            type="password"
                            class="mt-1 block w-full"
                            v-model="password"
                            autocomplete="new-password"
                            :placeholder="t('Nowe hasło')"
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
                            class="mt-1 block w-full"
                            v-model="passwordConfirmation"
                            autocomplete="new-password"
                            :placeholder="t('Potwierdź hasło')"
                        />
                        <InputError :message="errors['password_confirmation']?.[0]" />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="isLoading"
                            data-test="update-password-button"
                            >{{
                                hasPassword
                                    ? t('Zapisz hasło')
                                    : t('Ustaw hasło')
                            }}</Button
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
        </SettingsLayout>
    </AppLayout>
</template>
