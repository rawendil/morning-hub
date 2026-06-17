<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import RoutineBlockCard from '@/components/morning-hub/RoutineBlockCard.vue';
import RoutineBlockForm from '@/components/morning-hub/RoutineBlockForm.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useTimerSound } from '@/composables/useTimerSound';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type { BreadcrumbItem, ClickUpConnection, RoutineBlock } from '@/types';

const { t } = useTranslations();
const { enabled: timerSoundEnabled, setEnabled: setTimerSoundEnabled } =
    useTimerSound();

const blocks = ref<RoutineBlock[]>([]);
const connections = ref<ClickUpConnection[]>([]);
const googleCalendarConnectionId = ref<number | null>(null);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('Poranna rutyna'), href: '/morning-hub/routine' },
]);

const addOpen = ref(false);
const editingBlock = ref<RoutineBlock | undefined>();
const editOpen = ref(false);
const deleteBlock = ref<RoutineBlock | undefined>();
const deleteOpen = ref(false);

async function loadBlocks() {
    const { data } = await axiosInstance.get('/morning-hub/routine');
    blocks.value = data.blocks ?? [];
    connections.value = data.connections ?? [];
    googleCalendarConnectionId.value = data.googleCalendarConnectionId ?? null;
}

onMounted(loadBlocks);

function openEdit(block: RoutineBlock) {
    editingBlock.value = block;
    editOpen.value = true;
}

function openDelete(block: RoutineBlock) {
    deleteBlock.value = block;
    deleteOpen.value = true;
}

async function confirmDelete() {
    if (!deleteBlock.value) return;

    await axiosInstance.delete(
        `/morning-hub/routine/blocks/${deleteBlock.value.id}`,
    );
    deleteOpen.value = false;
    await loadBlocks();
}

async function moveBlock(blockIndex: number, direction: -1 | 1) {
    const ordered = [...blocks.value];
    const targetIndex = blockIndex + direction;
    if (targetIndex < 0 || targetIndex >= ordered.length) return;

    [ordered[blockIndex], ordered[targetIndex]] = [
        ordered[targetIndex],
        ordered[blockIndex],
    ];

    await axiosInstance.patch('/morning-hub/routine/blocks/reorder', {
        blocks: ordered.map((b) => b.id),
    });
    await loadBlocks();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading
                    :title="t('Poranna rutyna')"
                    :description="t('Konfiguruj bloki swojej porannej rutyny.')"
                />
                <Button class="gap-2" @click="addOpen = true">
                    <Plus class="h-4 w-4" />
                    {{ t('Dodaj blok') }}
                </Button>
            </div>

            <div
                class="flex items-center justify-between gap-4 rounded-lg border p-4"
            >
                <div class="space-y-0.5">
                    <Label for="timer-sound-toggle" class="text-base">
                        {{ t('Dźwięk po zakończeniu bloku') }}
                    </Label>
                    <p class="text-sm text-muted-foreground">
                        {{
                            t(
                                'Odtwarzaj dźwięk, gdy upłynie czas bloku rutyny.',
                            )
                        }}
                    </p>
                </div>
                <Switch
                    id="timer-sound-toggle"
                    :model-value="timerSoundEnabled"
                    @update:model-value="setTimerSoundEnabled"
                />
            </div>

            <div
                v-if="blocks.length === 0"
                class="rounded-lg border border-dashed p-8 text-center"
            >
                <p class="text-muted-foreground">
                    {{
                        t(
                            'Brak bloków. Dodaj pierwszy blok rutyny, aby rozpocząć.',
                        )
                    }}
                </p>
            </div>

            <div v-else class="grid gap-3">
                <RoutineBlockCard
                    v-for="(block, idx) in blocks"
                    :key="block.id"
                    :block="block"
                    :is-first="idx === 0"
                    :is-last="idx === blocks.length - 1"
                    @move-up="moveBlock(idx, -1)"
                    @move-down="moveBlock(idx, 1)"
                    @edit="openEdit(block)"
                    @delete="openDelete(block)"
                />
            </div>
        </div>

        <RoutineBlockForm
            v-model:open="addOpen"
            :connections="connections"
            :google-calendar-connection-id="googleCalendarConnectionId"
            @success="loadBlocks"
        />
        <RoutineBlockForm
            v-model:open="editOpen"
            :block="editingBlock"
            :connections="connections"
            :google-calendar-connection-id="googleCalendarConnectionId"
            @success="loadBlocks"
        />

        <Dialog v-model:open="deleteOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ t('Usuń blok') }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t('Czy na pewno chcesz usunąć ":name"?', {
                                name: deleteBlock?.name ?? '',
                            })
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">
                        {{ t('Usuń') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
