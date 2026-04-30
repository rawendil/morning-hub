<script setup lang="ts">
import { ShieldBan, ShieldCheck } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import axiosInstance from '@/lib/axios';
import Heading from '@/components/Heading.vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';

const { t } = useTranslations();

const twoFactorEnabled = ref(false);
const requiresConfirmation = ref(false);
const isLoading = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('Uwierzytelnianie dwuskładnikowe'),
        href: '/settings/two-factor',
    },
]);

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onMounted(async () => {
    const { data } = await axiosInstance.get('/settings/two-factor');
    twoFactorEnabled.value = data.twoFactorEnabled;
    requiresConfirmation.value = data.requiresConfirmation;
});

onUnmounted(() => {
    clearTwoFactorAuthData();
});

async function enableTwoFactor() {
    isLoading.value = true;
    try {
        await axiosInstance.post('/user/two-factor-authentication');
        showSetupModal.value = true;
        const { data } = await axiosInstance.get('/settings/two-factor');
        twoFactorEnabled.value = data.twoFactorEnabled;
        requiresConfirmation.value = data.requiresConfirmation;
    } finally {
        isLoading.value = false;
    }
}

async function disableTwoFactor() {
    isLoading.value = true;
    try {
        await axiosInstance.delete('/user/two-factor-authentication');
        twoFactorEnabled.value = false;
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <h1 class="sr-only">
            {{ t('Ustawienia uwierzytelniania dwuskładnikowego') }}
        </h1>

        <SettingsLayout>
            <div class="space-y-6">
                <Heading
                    variant="small"
                    :title="t('Uwierzytelnianie dwuskładnikowe')"
                    :description="
                        t(
                            'Zarządzaj ustawieniami uwierzytelniania dwuskładnikowego',
                        )
                    "
                />

                <div
                    v-if="!twoFactorEnabled"
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="destructive">{{ t('Wyłączone') }}</Badge>

                    <p class="text-muted-foreground">
                        {{
                            t(
                                'Po włączeniu uwierzytelniania dwuskładnikowego podczas logowania będzie wymagany bezpieczny kod PIN. Kod można uzyskać z aplikacji obsługującej TOTP na Twoim telefonie.',
                            )
                        }}
                    </p>

                    <div>
                        <Button
                            v-if="hasSetupData"
                            @click="showSetupModal = true"
                        >
                            <ShieldCheck />{{ t('Kontynuuj konfigurację') }}
                        </Button>
                        <Button
                            v-else
                            type="button"
                            :disabled="isLoading"
                            @click="enableTwoFactor"
                        >
                            <ShieldCheck />{{ t('Włącz 2FA') }}
                        </Button>
                    </div>
                </div>

                <div
                    v-else
                    class="flex flex-col items-start justify-start space-y-4"
                >
                    <Badge variant="default">{{ t('Włączone') }}</Badge>

                    <p class="text-muted-foreground">
                        {{
                            t(
                                'Z włączonym uwierzytelnianiem dwuskładnikowym podczas logowania będzie wymagany bezpieczny kod PIN, który możesz uzyskać z aplikacji obsługującej TOTP na Twoim telefonie.',
                            )
                        }}
                    </p>

                    <TwoFactorRecoveryCodes />

                    <div class="relative inline">
                        <Button
                            variant="destructive"
                            type="button"
                            :disabled="isLoading"
                            @click="disableTwoFactor"
                        >
                            <ShieldBan />
                            {{ t('Wyłącz 2FA') }}
                        </Button>
                    </div>
                </div>

                <TwoFactorSetupModal
                    v-model:isOpen="showSetupModal"
                    :requiresConfirmation="requiresConfirmation"
                    :twoFactorEnabled="twoFactorEnabled"
                />
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
