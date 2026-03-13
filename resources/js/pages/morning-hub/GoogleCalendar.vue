<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Heading from '@/components/Heading.vue';
import type { BreadcrumbItem } from '@/types';

defineProps<{
    connection: Record<string, unknown> | null;
    hasGoogleAccount: boolean;
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Morning Hub', href: '/morning-hub' },
    { title: 'Google Calendar', href: '/morning-hub/google-calendar' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Google Calendar" />

        <div class="px-4 py-6">
            <Heading title="Google Calendar" description="Połącz swój Google Calendar." />
        </div>
    </AppLayout>
</template>
