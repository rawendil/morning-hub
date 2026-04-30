<script setup lang="ts">
import { useTranslations } from '@/composables/useTranslations';

const { locale } = useTranslations();

const locales = [
    { value: 'pl', label: 'PL' },
    { value: 'en', label: 'EN' },
] as const;

function switchLocale(value: string) {
    if (value === locale.value) return;

    localStorage.setItem('locale', value);
    document.cookie = `locale=${value};path=/;max-age=${365 * 24 * 60 * 60};SameSite=Lax`;
    window.location.reload();
}
</script>

<template>
    <div
        class="inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800"
    >
        <button
            v-for="{ value, label } in locales"
            :key="value"
            @click="switchLocale(value)"
            :class="[
                'rounded-md px-3 py-1 text-sm font-medium transition-colors',
                locale === value
                    ? 'bg-white shadow-xs dark:bg-neutral-700 dark:text-neutral-100'
                    : 'text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60',
            ]"
        >
            {{ label }}
        </button>
    </div>
</template>
