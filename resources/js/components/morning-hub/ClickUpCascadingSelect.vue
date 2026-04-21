<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import { useTranslations } from '@/composables/useTranslations';
import {
    update,
    workspaces,
    spaces,
    allLists,
    me,
    statuses as statusesRoute,
} from '@/routes/morning-hub/clickup';
import type {
    ClickUpConnection,
    ClickUpConnectionFilters,
    ClickUpWorkspace,
    ClickUpSpace,
    ClickUpAllListsResponse,
    ClickUpStatus,
} from '@/types';

const { t } = useTranslations();

const props = defineProps<{
    connection: ClickUpConnection;
}>();

const workspaceList = ref<ClickUpWorkspace[]>([]);
const spaceList = ref<ClickUpSpace[]>([]);
const allListsData = ref<ClickUpAllListsResponse | null>(null);
const selectedListIds = ref<string[]>(props.connection.default_list_ids ?? []);

const selectedWorkspace = ref<string>(props.connection.workspace_id ?? '');
const selectedSpace = ref<string>(props.connection.default_space_id ?? '');

const filters = props.connection.default_filters;
const onlyMyTasks = ref(
    Array.isArray(filters?.assignees) &&
        (filters.assignees as number[]).length > 0,
);
const loadingMe = ref(false);

const loadingWorkspaces = ref(false);
const loadingSpaces = ref(false);
const loadingLists = ref(false);

const availableStatuses = ref<ClickUpStatus[]>([]);
const selectedStatusNames = ref<string[]>(
    props.connection.default_filters?.statuses ?? [],
);
const loadingStatuses = ref(false);

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchJson<T>(url: string): Promise<T[]> {
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
    });
    if (!response.ok) return [];
    const json = await response.json();
    return json.data ?? [];
}

async function fetchAllLists(
    spaceId: string,
): Promise<ClickUpAllListsResponse | null> {
    const url = allLists.url(props.connection, {
        query: { space_id: spaceId },
    });
    const response = await fetch(url, {
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
    });
    if (!response.ok) return null;
    const json = await response.json();
    return json.data ?? null;
}

function buildFilters(overrides: {
    assignees?: number[] | null;
    statuses?: string[] | null;
}): ClickUpConnectionFilters | null {
    const assignees =
        'assignees' in overrides
            ? overrides.assignees
            : (props.connection.default_filters?.assignees ?? null);
    const statuses =
        'statuses' in overrides
            ? overrides.statuses
            : selectedStatusNames.value;

    const result: ClickUpConnectionFilters = {};
    if (assignees && assignees.length > 0) result.assignees = assignees;
    if (statuses && statuses.length > 0) result.statuses = statuses;

    return Object.keys(result).length > 0 ? result : null;
}

function saveDefaults(data: Record<string, unknown>, onSuccess?: () => void) {
    router.put(
        update.url(props.connection),
        {
            name: props.connection.name,
            workspace_id: selectedWorkspace.value || null,
            default_space_id: selectedSpace.value || null,
            default_list_ids:
                selectedListIds.value.length > 0 ? selectedListIds.value : null,
            ...data,
        },
        { preserveScroll: true, onSuccess },
    );
}

async function loadWorkspaces() {
    loadingWorkspaces.value = true;
    workspaceList.value = await fetchJson(workspaces.url(props.connection));
    loadingWorkspaces.value = false;
}

async function loadSpaces(workspaceId: string) {
    loadingSpaces.value = true;
    spaceList.value = await fetchJson(
        spaces.url(props.connection, { query: { workspace_id: workspaceId } }),
    );
    loadingSpaces.value = false;
}

async function loadAllLists(spaceId: string) {
    loadingLists.value = true;
    allListsData.value = await fetchAllLists(spaceId);
    loadingLists.value = false;
}

async function loadStatuses() {
    if (selectedListIds.value.length === 0) {
        availableStatuses.value = [];
        return;
    }
    loadingStatuses.value = true;
    try {
        availableStatuses.value = await fetchJson<ClickUpStatus>(
            statusesRoute.url(props.connection),
        );
    } finally {
        loadingStatuses.value = false;
    }
}

function toggleList(listId: string) {
    if (selectedListIds.value.includes(listId)) {
        selectedListIds.value = selectedListIds.value.filter(
            (id) => id !== listId,
        );
    } else {
        selectedListIds.value = [...selectedListIds.value, listId];
    }
    selectedStatusNames.value = [];
    const newListIds =
        selectedListIds.value.length > 0 ? selectedListIds.value : null;
    saveDefaults(
        {
            default_list_ids: newListIds,
            default_filters: buildFilters({ statuses: null }),
        },
        async () => {
            await loadStatuses();
        },
    );
}

function toggleStatus(statusName: string) {
    if (selectedStatusNames.value.includes(statusName)) {
        selectedStatusNames.value = selectedStatusNames.value.filter(
            (s) => s !== statusName,
        );
    } else {
        selectedStatusNames.value = [...selectedStatusNames.value, statusName];
    }
    saveDefaults({ default_filters: buildFilters({}) });
}

async function toggleOnlyMyTasks(value: boolean) {
    onlyMyTasks.value = value;
    if (value) {
        loadingMe.value = true;
        try {
            const response = await fetch(me.url(props.connection), {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });
            if (response.ok) {
                const json = await response.json();
                const userId = json.data?.id;
                if (userId) {
                    saveDefaults({
                        default_filters: buildFilters({ assignees: [userId] }),
                    });
                }
            }
        } finally {
            loadingMe.value = false;
        }
    } else {
        saveDefaults({ default_filters: buildFilters({ assignees: null }) });
    }
}

watch(selectedWorkspace, async (val) => {
    selectedSpace.value = '';
    selectedListIds.value = [];
    availableStatuses.value = [];
    selectedStatusNames.value = [];
    spaceList.value = [];
    allListsData.value = null;
    if (val) {
        await loadSpaces(val);
        saveDefaults({
            workspace_id: val,
            default_space_id: null,
            default_list_ids: null,
        });
    }
});

watch(selectedSpace, async (val) => {
    selectedListIds.value = [];
    availableStatuses.value = [];
    selectedStatusNames.value = [];
    allListsData.value = null;
    if (val) {
        await loadAllLists(val);
        saveDefaults({ default_space_id: val, default_list_ids: null });
    }
});

onMounted(async () => {
    await loadWorkspaces();
    if (selectedWorkspace.value) {
        await loadSpaces(selectedWorkspace.value);
    }
    if (selectedSpace.value) {
        await loadAllLists(selectedSpace.value);
    }
    if (selectedListIds.value.length > 0) {
        await loadStatuses();
    }
});
</script>

<template>
    <div class="grid gap-4">
        <div class="grid gap-2">
            <Label>Workspace</Label>
            <Skeleton v-if="loadingWorkspaces" class="h-9 w-full" />
            <Select v-else v-model="selectedWorkspace">
                <SelectTrigger>
                    <SelectValue :placeholder="t('Wybierz workspace...')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="ws in workspaceList"
                        :key="ws.id"
                        :value="ws.id"
                    >
                        {{ ws.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="selectedWorkspace" class="grid gap-2">
            <Label>Space</Label>
            <Skeleton v-if="loadingSpaces" class="h-9 w-full" />
            <Select v-else v-model="selectedSpace">
                <SelectTrigger>
                    <SelectValue :placeholder="t('Wybierz space...')" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="s in spaceList"
                        :key="s.id"
                        :value="s.id"
                    >
                        {{ s.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="selectedWorkspace" class="flex items-center justify-between">
            <Label>{{ t('Tylko moje zadania') }}</Label>
            <Switch
                :model-value="onlyMyTasks"
                :disabled="loadingMe"
                @update:model-value="toggleOnlyMyTasks"
            />
        </div>

        <div v-if="selectedSpace" class="grid gap-2">
            <Label
                >{{ t('Listy') }}
                <span
                    v-if="selectedListIds.length > 0"
                    class="text-xs text-muted-foreground"
                    >({{
                        t(':count wybranych', {
                            count: selectedListIds.length.toString(),
                        })
                    }})</span
                ></Label
            >
            <Skeleton v-if="loadingLists" class="h-24 w-full" />
            <div
                v-else-if="allListsData"
                class="max-h-64 space-y-4 overflow-y-auto rounded-md border p-3"
            >
                <div v-for="folder in allListsData.folders" :key="folder.id">
                    <p class="mb-2 text-sm font-medium text-muted-foreground">
                        {{ folder.name }}
                    </p>
                    <div class="space-y-2 pl-2">
                        <div
                            v-for="list in folder.lists"
                            :key="list.id"
                            class="flex cursor-pointer items-center gap-2"
                            @click="toggleList(list.id)"
                        >
                            <Checkbox
                                :model-value="selectedListIds.includes(list.id)"
                            />
                            <span class="text-sm">{{ list.name }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="allListsData.folderless.length > 0">
                    <p class="mb-2 text-sm font-medium text-muted-foreground">
                        {{ t('Bez folderu') }}
                    </p>
                    <div class="space-y-2 pl-2">
                        <div
                            v-for="list in allListsData.folderless"
                            :key="list.id"
                            class="flex cursor-pointer items-center gap-2"
                            @click="toggleList(list.id)"
                        >
                            <Checkbox
                                :model-value="selectedListIds.includes(list.id)"
                            />
                            <span class="text-sm">{{ list.name }}</span>
                        </div>
                    </div>
                </div>

                <p
                    v-if="
                        allListsData.folders.length === 0 &&
                        allListsData.folderless.length === 0
                    "
                    class="text-sm text-muted-foreground"
                >
                    {{ t('Brak list w tym space.') }}
                </p>
            </div>
        </div>

        <div v-if="selectedListIds.length > 0" class="grid gap-2">
            <Label>
                {{ t('Statusy') }}
                <span
                    v-if="selectedStatusNames.length > 0"
                    class="text-xs text-muted-foreground"
                >
                    ({{
                        t(':count wybranych', {
                            count: selectedStatusNames.length.toString(),
                        })
                    }})
                </span>
                <span v-else class="text-xs text-muted-foreground"
                    >({{ t('wszystkie') }})</span
                >
            </Label>
            <Skeleton v-if="loadingStatuses" class="h-24 w-full" />
            <div
                v-else-if="availableStatuses.length > 0"
                class="max-h-48 space-y-2 overflow-y-auto rounded-md border p-3"
            >
                <div
                    v-for="status in availableStatuses"
                    :key="status.status"
                    class="flex cursor-pointer items-center gap-2"
                    @click="toggleStatus(status.status)"
                >
                    <Checkbox
                        :model-value="
                            selectedStatusNames.includes(status.status)
                        "
                    />
                    <span
                        class="inline-block h-2.5 w-2.5 shrink-0 rounded-full"
                        :style="{ backgroundColor: status.color }"
                    />
                    <span class="text-sm">{{ status.status }}</span>
                </div>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                {{ t('Brak dostępnych statusów.') }}
            </p>
        </div>
    </div>
</template>
