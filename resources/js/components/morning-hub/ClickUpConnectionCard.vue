<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { CheckCircle, Pencil, Plug, Settings, Trash2, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';
import ClickUpCascadingSelect from '@/components/morning-hub/ClickUpCascadingSelect.vue';
import ClickUpConnectionForm from '@/components/morning-hub/ClickUpConnectionForm.vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { destroy, test } from '@/routes/morning-hub/clickup';
import type { ClickUpConnection } from '@/types';

const props = defineProps<{
    connection: ClickUpConnection;
}>();

const editOpen = ref(false);
const deleteOpen = ref(false);
const configOpen = ref(false);
const testResult = ref<{ success: boolean; message: string } | null>(null);
const testing = ref(false);

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function testConnection() {
    testing.value = true;
    testResult.value = null;
    try {
        const response = await fetch(test.url(props.connection), {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        });
        testResult.value = await response.json();
    } catch {
        testResult.value = { success: false, message: 'Network error.' };
    }
    testing.value = false;
}

function deleteConnection() {
    router.delete(destroy.url(props.connection), {
        preserveScroll: true,
        onSuccess: () => { deleteOpen.value = false; },
    });
}
</script>

<template>
    <Card>
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <div>
                <CardTitle class="text-base">{{ connection.name }}</CardTitle>
                <CardDescription v-if="connection.workspace_id">
                    Workspace configured
                </CardDescription>
                <CardDescription v-else>
                    Not configured yet
                </CardDescription>
            </div>
            <div class="flex items-center gap-1">
                <Button variant="ghost" size="icon" :disabled="testing" @click="testConnection">
                    <Plug class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="editOpen = true">
                    <Pencil class="h-4 w-4" />
                </Button>
                <Button variant="ghost" size="icon" @click="deleteOpen = true">
                    <Trash2 class="h-4 w-4" />
                </Button>
            </div>
        </CardHeader>

        <CardContent>
            <div v-if="testResult" class="mb-4 flex items-center gap-2 text-sm">
                <CheckCircle v-if="testResult.success" class="h-4 w-4 text-green-600" />
                <XCircle v-else class="h-4 w-4 text-red-600" />
                <span :class="testResult.success ? 'text-green-600' : 'text-red-600'">
                    {{ testResult.message }}
                </span>
            </div>

            <Collapsible v-model:open="configOpen">
                <CollapsibleTrigger as-child>
                    <Button variant="outline" size="sm" class="w-full gap-2">
                        <Settings class="h-4 w-4" />
                        {{ configOpen ? 'Hide' : 'Configure' }} workspace defaults
                    </Button>
                </CollapsibleTrigger>
                <CollapsibleContent class="pt-4">
                    <ClickUpCascadingSelect :connection="connection" />
                </CollapsibleContent>
            </Collapsible>
        </CardContent>
    </Card>

    <ClickUpConnectionForm v-model:open="editOpen" :connection="connection" />

    <Dialog v-model:open="deleteOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete connection</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete "{{ connection.name }}"? Any routine blocks using this connection will be unlinked.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Cancel</Button>
                </DialogClose>
                <Button variant="destructive" @click="deleteConnection">
                    Delete
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
