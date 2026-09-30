<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
</script>

<template>
    <a
        href="/terms-of-service"
        target="_blank"
        rel="noopener noreferrer"
        class="underline underline-offset-4 hover:text-foreground"
        data-testid="terms-link"
        >{{ t('Regulamin') }}</a
    >
    {{ t('oraz') }}
    <a
        href="/privacy-policy"
        target="_blank"
        rel="noopener noreferrer"
        class="underline underline-offset-4 hover:text-foreground"
        data-testid="privacy-link"
        >{{ t('Politykę prywatności') }}</a
    >
</template>
