<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type { BreadcrumbItem, ClickUpConnection } from '@/types';

const { t } = useTranslations();

type TodaysTasksConfig = {
    id: number;
    connection_ids: number[] | null;
} | null;

const config = ref<TodaysTasksConfig>(null);
const connections = ref<ClickUpConnection[]>([]);
const selectedConnectionIds = ref<number[]>([]);
const processing = ref(false);
const recentlySuccessful = ref(false);

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('Zadania na dziś — Konfiguracja'),
        href: '/morning-hub/todays-tasks',
    },
]);

async function loadConfig() {
    const { data } = await axiosInstance.get('/morning-hub/todays-tasks');
    config.value = data.config ?? null;
    connections.value = data.connections ?? [];
    selectedConnectionIds.value = (config.value?.connection_ids ?? []).map(
        Number,
    );
}

onMounted(loadConfig);

function toggleConnectionId(id: number) {
    const index = selectedConnectionIds.value.indexOf(id);
    if (index === -1) {
        selectedConnectionIds.value.push(id);
    } else {
        selectedConnectionIds.value.splice(index, 1);
    }
}

async function submit() {
    processing.value = true;
    recentlySuccessful.value = false;

    try {
        await axiosInstance.put('/morning-hub/todays-tasks', {
            connection_ids: selectedConnectionIds.value,
        });
        recentlySuccessful.value = true;
        setTimeout(() => {
            recentlySuccessful.value = false;
        }, 3000);
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <Heading
                :title="t('Zadania na dziś — Konfiguracja')"
                :description="
                    t(
                        'Wybierz połączenia ClickUp, z których mają być pobierane zadania.',
                    )
                "
            />

            <form @submit.prevent="submit">
                <div class="max-w-xl space-y-6">
                    <div class="grid gap-2">
                        <Label>{{ t('Połączenia ClickUp') }}</Label>
                        <p
                            v-if="!connections.length"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('Brak dostępnych połączeń ClickUp.') }}
                            <RouterLink
                                to="/morning-hub/clickup"
                                class="underline"
                                >{{ t('Dodaj połączenie') }}</RouterLink
                            >,
                            {{ t('aby rozpocząć.') }}
                        </p>
                        <div v-else class="space-y-2">
                            <label
                                v-for="conn in connections"
                                :key="conn.id"
                                class="flex cursor-pointer items-center gap-2"
                                @click="toggleConnectionId(conn.id)"
                            >
                                <Checkbox
                                    :model-value="
                                        selectedConnectionIds.includes(conn.id)
                                    "
                                />
                                <span class="text-sm">{{ conn.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="processing">
                            {{ processing ? t('Zapisuję...') : t('Zapisz') }}
                        </Button>
                        <p
                            v-if="recentlySuccessful"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('Zapisano.') }}
                        </p>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
