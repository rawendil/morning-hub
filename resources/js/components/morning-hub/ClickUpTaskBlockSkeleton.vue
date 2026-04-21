<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-3"
        >
            <Skeleton class="h-5 w-40" />
            <Skeleton class="h-8 w-8 rounded-md" />
        </CardHeader>
        <CardContent class="space-y-2 pt-0">
            <Skeleton class="h-10 w-full rounded-md" />
            <Skeleton class="h-10 w-full rounded-md" />
            <Skeleton class="h-10 w-5/6 rounded-md" />
        </CardContent>
    </Card>
</template>
