<script setup lang="ts">
import { RouterLink } from 'vue-router';

type Props = {
    href: string;
    tabindex?: number;
    as?: string;
};

defineProps<Props>();
</script>

<template>
    <component
        :is="as === 'button' ? 'button' : RouterLink"
        :to="as === 'button' ? undefined : href"
        :tabindex="tabindex"
        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
    >
        <slot />
    </component>
</template>
