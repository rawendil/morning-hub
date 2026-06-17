<script setup lang="ts">
import { Check } from 'lucide-vue-next';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
</script>

<template>
    <span
        role="img"
        :aria-label="t('Ukończono')"
        :title="t('Ukończono')"
        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
    >
        <Check class="h-3 w-3" :stroke-width="3" />
    </span>
</template>
