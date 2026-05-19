<script setup lang="ts">
import { RouterLink } from 'vue-router';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <div class="fixed top-4 right-4">
            <LanguageSwitcher />
        </div>
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <RouterLink
                        to="/"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-1 flex h-9 w-9 items-center justify-center rounded-md"
                        >
                            <AppLogoIcon
                                class="size-9 fill-current text-foreground dark:text-white"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </RouterLink>
                    <div class="space-y-2 text-center">
                        <h1 class="text-xl font-medium">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <slot />
            </div>
        </div>
        <p class="text-center text-xs text-muted-foreground">
            <a
                href="/privacy-policy"
                target="_blank"
                class="underline underline-offset-4 hover:text-foreground"
            >
                Privacy Policy
            </a>
            <span class="mx-1">·</span>
            <a
                href="/terms-of-service"
                target="_blank"
                class="underline underline-offset-4 hover:text-foreground"
            >
                Terms of Service
            </a>
        </p>
    </div>
</template>
