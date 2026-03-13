<script setup lang="ts">
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
</script>

<template>
    <Card>
        <CardHeader
            class="flex flex-row items-center justify-between space-y-0 py-3"
        >
            <Skeleton class="h-5 w-36" />
        </CardHeader>
        <CardContent class="space-y-3 pt-0">
            <div v-for="n in 4" :key="n" class="flex items-center gap-3">
                <Skeleton class="h-3 w-3 shrink-0 rounded-full" />
                <Skeleton class="h-4 w-16" />
                <Skeleton class="h-4 w-48" />
            </div>
        </CardContent>
    </Card>
</template>
