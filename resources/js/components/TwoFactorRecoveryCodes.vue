<script setup lang="ts">
import { Eye, EyeOff, LockKeyhole, RefreshCw } from 'lucide-vue-next';
import { nextTick, onMounted, ref, useTemplateRef } from 'vue';
import axiosInstance from '@/lib/axios';
import AlertError from '@/components/AlertError.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';

const { t } = useTranslations();
const { recoveryCodesList, fetchRecoveryCodes, errors } = useTwoFactorAuth();
const isRecoveryCodesVisible = ref<boolean>(false);
const recoveryCodeSectionRef = useTemplateRef('recoveryCodeSectionRef');
const regenerating = ref(false);

async function handleRegenerateCodes() {
    regenerating.value = true;
    try {
        await axiosInstance.post('/user/two-factor-recovery-codes');
        await fetchRecoveryCodes();
    } finally {
        regenerating.value = false;
    }
}

const toggleRecoveryCodesVisibility = async () => {
    if (!isRecoveryCodesVisible.value && !recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }

    isRecoveryCodesVisible.value = !isRecoveryCodesVisible.value;

    if (isRecoveryCodesVisible.value) {
        await nextTick();
        recoveryCodeSectionRef.value?.scrollIntoView({ behavior: 'smooth' });
    }
};

onMounted(async () => {
    if (!recoveryCodesList.value.length) {
        await fetchRecoveryCodes();
    }
});
</script>

<template>
    <Card class="w-full">
        <CardHeader>
            <CardTitle class="flex gap-3">
                <LockKeyhole class="size-4" />{{ t('Kody odzyskiwania 2FA') }}
            </CardTitle>
            <CardDescription>
                {{
                    t(
                        'Kody odzyskiwania pozwalają odzyskać dostęp w przypadku utraty urządzenia 2FA. Przechowuj je w bezpiecznym menedżerze haseł.',
                    )
                }}
            </CardDescription>
        </CardHeader>
        <CardContent>
            <div
                class="flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between"
            >
                <Button @click="toggleRecoveryCodesVisibility" class="w-fit">
                    <component
                        :is="isRecoveryCodesVisible ? EyeOff : Eye"
                        class="size-4"
                    />
                    {{
                        isRecoveryCodesVisible
                            ? t('Ukryj kody odzyskiwania')
                            : t('Pokaż kody odzyskiwania')
                    }}
                </Button>

                <Button
                    v-if="isRecoveryCodesVisible && recoveryCodesList.length"
                    variant="secondary"
                    type="button"
                    :disabled="regenerating"
                    @click="handleRegenerateCodes"
                >
                    <RefreshCw /> {{ t('Wygeneruj nowe kody') }}
                </Button>
            </div>
            <div
                :class="[
                    'relative overflow-hidden transition-all duration-300',
                    isRecoveryCodesVisible
                        ? 'h-auto opacity-100'
                        : 'h-0 opacity-0',
                ]"
            >
                <div v-if="errors?.length" class="mt-6">
                    <AlertError :errors="errors" />
                </div>
                <div v-else class="mt-3 space-y-3">
                    <div
                        ref="recoveryCodeSectionRef"
                        class="grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm"
                    >
                        <div v-if="!recoveryCodesList.length" class="space-y-2">
                            <div
                                v-for="n in 8"
                                :key="n"
                                class="h-4 animate-pulse rounded bg-muted-foreground/20"
                            ></div>
                        </div>
                        <div
                            v-else
                            v-for="(code, index) in recoveryCodesList"
                            :key="index"
                        >
                            {{ code }}
                        </div>
                    </div>
                    <p class="text-xs text-muted-foreground select-none">
                        {{
                            t(
                                'Każdy kod odzyskiwania może być użyty tylko raz i po wykorzystaniu zostanie usunięty. Jeśli potrzebujesz nowych, kliknij',
                            )
                        }}
                        <span class="font-bold">{{
                            t('Wygeneruj nowe kody')
                        }}</span>
                        {{ t('powyżej.') }}
                    </p>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
