<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { store, update } from '@/actions/App/Http/Controllers/MorningHub/ClickUpConnectionController';
import type { ClickUpConnection } from '@/types';

const props = defineProps<{
    connection?: ClickUpConnection;
}>();

const isOpen = defineModel<boolean>('open', { default: false });
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <Form
                v-bind="connection ? update.form(connection) : store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="isOpen = false"
            >
                <DialogHeader>
                    <DialogTitle>{{ connection ? 'Edytuj połączenie' : 'Dodaj połączenie' }}</DialogTitle>
                    <DialogDescription>
                        {{ connection ? 'Zaktualizuj połączenie ClickUp.' : 'Połącz workspace ClickUp za pomocą osobistego tokena API.' }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="conn-name">Nazwa</Label>
                        <Input
                            id="conn-name"
                            name="name"
                            :default-value="connection?.name"
                            required
                            placeholder="np. Praca, Osobiste"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="conn-api-token">Token API</Label>
                        <Input
                            id="conn-api-token"
                            name="api_token"
                            type="password"
                            :required="!connection"
                            :placeholder="connection ? 'Pozostaw puste, aby zachować obecny' : 'pk_...'"
                        />
                        <InputError :message="errors.api_token" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Anuluj</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ connection ? 'Zapisz' : 'Dodaj połączenie' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
