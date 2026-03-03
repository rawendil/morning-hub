<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 42"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Leaf outline (clock face) — teardrop: narrow top, wide bottom -->
        <path
            d="M20 2 C16 7 -3 30 20 40 C43 30 24 7 20 2Z"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
        />
        <!-- Hour markers: 12, 3, 6, 9 -->
        <circle cx="20" cy="8" r="1.3" fill="currentColor" />
        <circle cx="31" cy="24" r="1" fill="currentColor" />
        <circle cx="20" cy="36" r="1" fill="currentColor" />
        <circle cx="9" cy="24" r="1" fill="currentColor" />
        <!-- Minute hand (pointing to 12) -->
        <line x1="20" y1="23" x2="20" y2="11" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        <!-- Hour hand (pointing to ~7 o'clock — morning) -->
        <line x1="20" y1="23" x2="15" y2="30" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
        <!-- Center dot -->
        <circle cx="20" cy="23" r="2" fill="currentColor" />
    </svg>
</template>
