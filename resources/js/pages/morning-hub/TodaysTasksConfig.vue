<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { index as clickupIndex } from '@/routes/morning-hub/clickup';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import { update } from '@/routes/morning-hub/todays-tasks';
import type { BreadcrumbItem, ClickUpConnection } from '@/types';

const { t } = useTranslations();

type TodaysTasksConfig = {
    id: number;
    connection_ids: number[] | null;
} | null;

const props = defineProps<{
    config: TodaysTasksConfig;
    connections: ClickUpConnection[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: t('Zadania na dziś — Konfiguracja'),
        href: todaysTasksConfigIndex(),
    },
]);

const selectedConnectionIds = ref<number[]>(
    (props.config?.connection_ids ?? []).map(Number),
);

function toggleConnectionId(id: number) {
    const index = selectedConnectionIds.value.indexOf(id);
    if (index === -1) {
        selectedConnectionIds.value.push(id);
    } else {
        selectedConnectionIds.value.splice(index, 1);
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="t('Zadania na dziś — Konfiguracja')" />

        <div class="space-y-6 p-6">
            <Heading
                :title="t('Zadania na dziś — Konfiguracja')"
                :description="
                    t(
                        'Wybierz połączenia ClickUp, z których mają być pobierane zadania.',
                    )
                "
            />

            <Form
                :action="update()"
                method="put"
                #default="{ errors, processing, recentlySuccessful }"
            >
                <div class="max-w-xl space-y-6">
                    <div class="grid gap-2">
                        <Label>{{ t('Połączenia ClickUp') }}</Label>
                        <p
                            v-if="!connections.length"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('Brak dostępnych połączeń ClickUp.') }}
                            <Link :href="clickupIndex()" class="underline">{{
                                t('Dodaj połączenie')
                            }}</Link
                            >,
                            {{ t('aby rozpocząć.') }}
                        </p>
                        <div v-else class="space-y-2">
                            <template
                                v-for="id in selectedConnectionIds"
                                :key="`hidden-${id}`"
                            >
                                <input
                                    type="hidden"
                                    name="connection_ids[]"
                                    :value="id"
                                />
                            </template>
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
                        <p
                            v-if="errors['connection_ids']"
                            class="text-sm text-destructive"
                        >
                            {{ errors['connection_ids'] }}
                        </p>
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
            </Form>
        </div>
    </AppLayout>
</template>
