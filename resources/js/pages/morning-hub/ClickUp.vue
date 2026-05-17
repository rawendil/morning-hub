<script setup lang="ts">
import { Plus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import ClickUpConnectionCard from '@/components/morning-hub/ClickUpConnectionCard.vue';
import ClickUpConnectionForm from '@/components/morning-hub/ClickUpConnectionForm.vue';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type { BreadcrumbItem, ClickUpConnection } from '@/types';

const { t } = useTranslations();
const route = useRoute();
const router = useRouter();

const connections = ref<ClickUpConnection[]>([]);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('Połączenia ClickUp'), href: '/morning-hub/clickup' },
]);

const addOpen = ref(false);

async function loadConnections() {
    const { data } = await axiosInstance.get('/morning-hub/clickup');
    connections.value = data.connections ?? [];
}

onMounted(async () => {
    try {
        await loadConnections();
    } finally {
        const connected = route.query.connected as string | undefined;
        const error = route.query.error as string | undefined;

        if (connected) {
            toast.success(t('Połączono z ClickUp.'));
        } else if (error === 'no_code') {
            toast.error(t('Anulowano autoryzację ClickUp.'));
        } else if (error === 'invalid_state') {
            toast.error(t('Błąd bezpieczeństwa. Spróbuj ponownie.'));
        } else if (error === 'auth_failed') {
            toast.error(t('Autoryzacja ClickUp nie powiodła się.'));
        }

        if (connected || error) {
            router.replace({ query: {} });
        }
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="space-y-6 p-6">
            <div class="flex items-center justify-between">
                <Heading
                    :title="t('Połączenia ClickUp')"
                    :description="
                        t(
                            'Zarządzaj połączeniami API ClickUp i domyślnymi ustawieniami.',
                        )
                    "
                />
                <Button class="gap-2" @click="addOpen = true">
                    <Plus class="h-4 w-4" />
                    {{ t('Dodaj połączenie') }}
                </Button>
            </div>

            <div
                v-if="connections.length === 0"
                class="rounded-lg border border-dashed p-8 text-center"
            >
                <p class="text-muted-foreground">
                    {{
                        t(
                            'Brak połączeń. Dodaj pierwsze połączenie ClickUp, aby rozpocząć.',
                        )
                    }}
                </p>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2">
                <ClickUpConnectionCard
                    v-for="connection in connections"
                    :key="connection.id"
                    :connection="connection"
                    @deleted="loadConnections"
                />
            </div>
        </div>

        <ClickUpConnectionForm
            v-model:open="addOpen"
            @success="loadConnections"
        />
    </AppLayout>
</template>
