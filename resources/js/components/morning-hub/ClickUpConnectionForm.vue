<script setup lang="ts">
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
import { useTranslations } from '@/composables/useTranslations';
import axiosInstance from '@/lib/axios';
import type { ClickUpConnection } from '@/types';

const { t } = useTranslations();

const props = defineProps<{
    connection?: ClickUpConnection;
}>();

const emit = defineEmits<{ success: [] }>();

const isOpen = defineModel<boolean>('open', { default: false });

const connName = ref(props.connection?.name ?? '');
const apiToken = ref('');
const processing = ref(false);
const errors = ref<Record<string, string>>({});

async function submit() {
    processing.value = true;
    errors.value = {};

    try {
        if (props.connection) {
            await axiosInstance.put(
                `/morning-hub/clickup/connections/${props.connection.id}`,
                {
                    name: connName.value,
                    ...(apiToken.value ? { api_token: apiToken.value } : {}),
                },
            );
        } else {
            await axiosInstance.post('/morning-hub/clickup/connections', {
                name: connName.value,
                api_token: apiToken.value,
            });
        }

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
        }
    } finally {
        processing.value = false;
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
                                      'Połącz workspace ClickUp za pomocą osobistego tokena API.',
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

                    <div class="grid gap-2">
                        <Label for="conn-api-token">{{ t('Token API') }}</Label>
                        <Input
                            id="conn-api-token"
                            v-model="apiToken"
                            type="password"
                            :required="!connection"
                            :placeholder="
                                connection
                                    ? t('Pozostaw puste, aby zachować obecny')
                                    : 'pk_...'
                            "
                        />
                        <InputError :message="errors.api_token" />
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ connection ? t('Zapisz') : t('Dodaj połączenie') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
