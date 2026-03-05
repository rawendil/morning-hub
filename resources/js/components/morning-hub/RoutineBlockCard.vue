<script setup lang="ts">
import { computed } from 'vue';
import { ArrowDown, ArrowUp, Clock, Pencil, Plug, Trash2 } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import type { RoutineBlock } from '@/types';

defineProps<{
    block: RoutineBlock;
    isFirst: boolean;
    isLast: boolean;
}>();

const emit = defineEmits<{
    moveUp: [];
    moveDown: [];
    edit: [];
    delete: [];
}>();

const { t } = useTranslations();

const typeLabels = computed<Record<string, string>>(() => ({
    clickup: 'ClickUp',
    braindump: t('Zrzut myśli'),
    habits: t('Codzienne nawyki'),
    feed: t('Kanał RSS'),
    notes: t('Notatki'),
    plan: t('Plan'),
    custom: t('Własny'),
}));
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 py-3">
            <div class="flex items-center gap-3">
                <div class="flex flex-col gap-0.5">
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="isFirst"
                        @click="emit('moveUp')"
                    >
                        <ArrowUp class="h-3 w-3" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="h-6 w-6"
                        :disabled="isLast"
                        @click="emit('moveDown')"
                    >
                        <ArrowDown class="h-3 w-3" />
                    </Button>
                </div>
                <div>
                    <CardTitle class="text-base">{{ block.name }}</CardTitle>
                    <div class="mt-1 flex items-center gap-2">
                        <Badge variant="secondary">{{ typeLabels[block.type] ?? block.type }}</Badge>
                        <Badge v-if="block.timer_minutes" variant="outline" class="gap-1">
                            <Clock class="h-3 w-3" />
                            {{ block.timer_minutes }}m
                        </Badge>
                        <Badge v-if="block.clickup_connection" variant="outline" class="gap-1">
                            <Plug class="h-3 w-3" />
                            {{ block.clickup_connection.name }}
                        </Badge>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-1">
                <Button variant="ghost" size="icon" @click="emit('edit')">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="emit('delete')">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>
    </Card>
</template>
