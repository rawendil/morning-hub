<script setup lang="ts">
import { ref } from 'vue';
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
import InputError from '@/components/InputError.vue';
import { useTranslations } from '@/composables/useTranslations';
import type { ClickUpConnection } from '@/types';
import axiosInstance from '@/lib/axios';

const { t } = useTranslations();

const props = defineProps<{
    connection?: ClickUpConnection;
}>();

const emit = defineEmits<{ success: [] }>();

const isOpen = defineModel<boolean>('open', { default: false });

const connName = ref(props.connection?.name ?? '');
const processing = ref(false);
const errors = ref<Record<string, string>>({});

function connectWithOAuth() {
    const name = connName.value.trim() || 'ClickUp';
    window.location.href = `/clickup/oauth/redirect?name=${encodeURIComponent(name)}`;
}

async function submitUpdate() {
    if (!props.connection) {
        return;
    }

    processing.value = true;
    errors.value = {};

    try {
        await axiosInstance.put(
            `/morning-hub/clickup/connections/${props.connection.id}`,
            { name: connName.value },
        );
        emit('success');
        isOpen.value = false;
    } catch (err: unknown) {
        const axiosErr = err as {
            response?: { data?: { errors?: Record<string, string[]> } };
        };
        if (axiosErr.response?.data?.errors) {
            const rawErrors = axiosErr.response.data.errors;
            errors.value = Object.fromEntries(
                Object.entries(rawErrors).map(([k, v]) => [k, v[0]]),
            );
        } else {
            errors.value = { name: t('Wystąpił nieoczekiwany błąd.') };
        }
    } finally {
        processing.value = false;
    }
}

function submit() {
    if (props.connection) {
        submitUpdate();
    } else {
        connectWithOAuth();
    }
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <form class="space-y-6" @submit.prevent="submit">
                <DialogHeader>
                    <DialogTitle>{{
                        connection
                            ? t('Edytuj połączenie')
                            : t('Dodaj połączenie')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            connection
                                ? t('Zaktualizuj połączenie ClickUp.')
                                : t(
                                      'Połącz workspace ClickUp przez OAuth.',
                                  )
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="conn-name">{{ t('Nazwa') }}</Label>
                        <Input
                            id="conn-name"
                            v-model="connName"
                            required
                            :placeholder="t('np. Praca, Osobiste')"
                        />
                        <InputError :message="errors.name" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{
                            connection
                                ? t('Zapisz')
                                : t('Połącz z ClickUp')
                        }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
