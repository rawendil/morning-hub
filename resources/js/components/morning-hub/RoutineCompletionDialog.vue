<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const props = defineProps<{
    open: boolean;
    completedMinutes: number;
    totalBlocks: number;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();
</script>

<template>
    <Dialog :open="props.open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-md text-center">
            <DialogHeader>
                <DialogTitle class="text-2xl">
                    {{ t('Rutyna ukończona!') }} 🎉
                </DialogTitle>
                <DialogDescription class="text-base">
                    {{ t('Świetna robota! Ukończyłeś całą poranną rutynę.') }}
                </DialogDescription>
            </DialogHeader>

            <div class="flex flex-col items-center gap-2 py-4">
                <p class="text-sm text-muted-foreground">
                    {{ t('Ukończone bloki') }}: <span class="font-semibold text-foreground">{{ totalBlocks }}</span>
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ t('Czas spędzony') }}: <span class="font-semibold text-foreground">{{ completedMinutes }} min</span>
                </p>
            </div>

            <DialogFooter class="justify-center">
                <Button @click="emit('update:open', false)">
                    {{ t('Dziękuję!') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
