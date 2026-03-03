<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { store, update } from '@/actions/App/Http/Controllers/MorningHub/RoutineBlockController';
import type { BlockType, ClickUpConnection, RoutineBlock } from '@/types';

const props = defineProps<{
    block?: RoutineBlock;
    connections: ClickUpConnection[];
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const blockTypes: { value: BlockType; label: string }[] = [
    { value: 'clickup', label: 'ClickUp' },
    { value: 'braindump', label: 'Brain Dump' },
    { value: 'notes', label: 'Notes' },
    { value: 'plan', label: 'Plan' },
    { value: 'custom', label: 'Custom' },
];

const selectedType = ref<string>(props.block?.type ?? '');

watch(() => props.block, (newBlock) => {
    selectedType.value = newBlock?.type ?? '';
});

const needsConnection = computed(() => selectedType.value === 'clickup' || selectedType.value === 'braindump');
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <Form
                v-bind="block ? update.form(block) : store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="isOpen = false"
            >
                <DialogHeader>
                    <DialogTitle>{{ block ? 'Edit Block' : 'Add Block' }}</DialogTitle>
                    <DialogDescription>
                        {{ block ? 'Update this routine block.' : 'Add a new block to your morning routine.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>Type</Label>
                        <input type="hidden" name="type" :value="selectedType" />
                        <Select v-model="selectedType">
                            <SelectTrigger>
                                <SelectValue placeholder="Select block type..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="bt in blockTypes" :key="bt.value" :value="bt.value">
                                    {{ bt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="block-name">Name</Label>
                        <Input
                            id="block-name"
                            name="name"
                            :default-value="block?.name"
                            required
                            placeholder="e.g. Review tasks, Quick notes"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="block-timer">Timer (minutes)</Label>
                        <Input
                            id="block-timer"
                            name="timer_minutes"
                            type="number"
                            min="1"
                            max="120"
                            :default-value="block?.timer_minutes?.toString()"
                            placeholder="Optional"
                        />
                        <InputError :message="errors.timer_minutes" />
                    </div>

                    <div v-if="needsConnection" class="grid gap-2">
                        <Label>ClickUp Connection</Label>
                        <input
                            v-if="!connections.length"
                            type="hidden"
                            name="clickup_connection_id"
                            value=""
                        />
                        <Select v-else name="clickup_connection_id" :default-value="block?.clickup_connection_id?.toString()">
                            <SelectTrigger>
                                <SelectValue placeholder="Select connection..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="conn in connections" :key="conn.id" :value="conn.id.toString()">
                                    {{ conn.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="!connections.length" class="text-sm text-muted-foreground">
                            No ClickUp connections available. Add one first.
                        </p>
                        <InputError :message="errors.clickup_connection_id" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Cancel</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ block ? 'Save' : 'Add Block' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
