<script setup lang="ts">
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
</script>

<template>
    <div class="flex items-center gap-4">
        <Separator class="flex-1" />
        <span class="text-xs text-muted-foreground uppercase">{{ t('lub') }}</span>
        <Separator class="flex-1" />
    </div>
</template>
