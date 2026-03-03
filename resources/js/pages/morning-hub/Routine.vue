<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { ref } from 'vue';
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
import AppLayout from '@/layouts/AppLayout.vue';
import { destroy, index, reorder } from '@/routes/morning-hub/routine';
import type { BreadcrumbItem, ClickUpConnection, RoutineBlock } from '@/types';

const props = defineProps<{
    blocks: RoutineBlock[];
    connections: ClickUpConnection[];
}>();

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Poranna rutyna', href: index() },
];

const addOpen = ref(false);
const editingBlock = ref<RoutineBlock | undefined>();
const editOpen = ref(false);
const deleteBlock = ref<RoutineBlock | undefined>();
const deleteOpen = ref(false);

function openEdit(block: RoutineBlock) {
    editingBlock.value = block;
    editOpen.value = true;
}

function openDelete(block: RoutineBlock) {
    deleteBlock.value = block;
    deleteOpen.value = true;
}

function confirmDelete() {
    if (!deleteBlock.value) return;
    router.delete(destroy.url(deleteBlock.value), {
        preserveScroll: true,
        onSuccess: () => { deleteOpen.value = false; },
    });
}

function moveBlock(blockIndex: number, direction: -1 | 1) {
    const ordered = [...props.blocks];
    const targetIndex = blockIndex + direction;
    if (targetIndex < 0 || targetIndex >= ordered.length) return;

    [ordered[blockIndex], ordered[targetIndex]] = [ordered[targetIndex], ordered[blockIndex]];

    router.patch(reorder.url(), {
        blocks: ordered.map((b) => b.id),
    }, { preserveScroll: true });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Poranna rutyna" />

        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading title="Poranna rutyna" description="Konfiguruj bloki swojej porannej rutyny." />
                <Button class="gap-2" @click="addOpen = true">
                    <Plus class="h-4 w-4" />
                    Dodaj blok
                </Button>
            </div>

            <div v-if="blocks.length === 0" class="rounded-lg border border-dashed p-8 text-center">
                <p class="text-muted-foreground">Brak bloków. Dodaj pierwszy blok rutyny, aby rozpocząć.</p>
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

        <RoutineBlockForm v-model:open="addOpen" :connections="connections" />
        <RoutineBlockForm v-model:open="editOpen" :block="editingBlock" :connections="connections" />

        <Dialog v-model:open="deleteOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Usuń blok</DialogTitle>
                    <DialogDescription>
                        Czy na pewno chcesz usunąć "{{ deleteBlock?.name }}"?
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Anuluj</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="confirmDelete">
                        Usuń
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
