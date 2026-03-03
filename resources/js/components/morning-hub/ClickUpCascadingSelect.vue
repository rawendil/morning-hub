<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch, onMounted } from 'vue';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { update, workspaces, spaces, folders, lists } from '@/routes/morning-hub/clickup';
import type { ClickUpConnection, ClickUpWorkspace, ClickUpSpace, ClickUpFolder, ClickUpList } from '@/types';

const props = defineProps<{
    connection: ClickUpConnection;
}>();

const workspaceList = ref<ClickUpWorkspace[]>([]);
const spaceList = ref<ClickUpSpace[]>([]);
const folderList = ref<ClickUpFolder[]>([]);
const listList = ref<ClickUpList[]>([]);

const selectedWorkspace = ref<string>(props.connection.workspace_id ?? '');
const selectedSpace = ref<string>(props.connection.default_space_id ?? '');
const selectedFolder = ref<string>(props.connection.default_folder_id ?? '');
const selectedList = ref<string>(props.connection.default_list_id ?? '');

const loadingWorkspaces = ref(false);
const loadingSpaces = ref(false);
const loadingFolders = ref(false);
const loadingLists = ref(false);

function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function fetchJson<T>(url: string): Promise<T[]> {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': getCsrfToken(),
        },
        credentials: 'same-origin',
    });
    if (!response.ok) return [];
    const json = await response.json();
    return json.data ?? [];
}

function saveDefaults(data: Partial<Record<string, string | null>>) {
    router.put(update.url(props.connection), {
        name: props.connection.name,
        workspace_id: selectedWorkspace.value || null,
        default_space_id: selectedSpace.value || null,
        default_folder_id: selectedFolder.value || null,
        default_list_id: selectedList.value || null,
        ...data,
    }, { preserveScroll: true });
}

async function loadWorkspaces() {
    loadingWorkspaces.value = true;
    workspaceList.value = await fetchJson(workspaces.url(props.connection));
    loadingWorkspaces.value = false;
}

async function loadSpaces(workspaceId: string) {
    loadingSpaces.value = true;
    spaceList.value = await fetchJson(spaces.url(props.connection, { query: { workspace_id: workspaceId } }));
    loadingSpaces.value = false;
}

async function loadFolders(spaceId: string) {
    loadingFolders.value = true;
    folderList.value = await fetchJson(folders.url(props.connection, { query: { space_id: spaceId } }));
    loadingFolders.value = false;
}

async function loadLists(folderId: string | null, spaceId: string | null) {
    loadingLists.value = true;
    if (folderId) {
        listList.value = await fetchJson(lists.url(props.connection, { query: { folder_id: folderId } }));
    } else if (spaceId) {
        listList.value = await fetchJson(lists.url(props.connection, { query: { space_id: spaceId } }));
    }
    loadingLists.value = false;
}

watch(selectedWorkspace, (val) => {
    selectedSpace.value = '';
    selectedFolder.value = '';
    selectedList.value = '';
    spaceList.value = [];
    folderList.value = [];
    listList.value = [];
    if (val) {
        loadSpaces(val);
        saveDefaults({ workspace_id: val, default_space_id: null, default_folder_id: null, default_list_id: null });
    }
});

watch(selectedSpace, (val) => {
    selectedFolder.value = '';
    selectedList.value = '';
    folderList.value = [];
    listList.value = [];
    if (val) {
        loadFolders(val);
        saveDefaults({ default_space_id: val, default_folder_id: null, default_list_id: null });
    }
});

watch(selectedFolder, (val) => {
    selectedList.value = '';
    listList.value = [];
    if (val === '__none__') {
        loadLists(null, selectedSpace.value);
        saveDefaults({ default_folder_id: null, default_list_id: null });
    } else if (val) {
        loadLists(val, null);
        saveDefaults({ default_folder_id: val, default_list_id: null });
    }
});

watch(selectedList, (val) => {
    if (val) {
        saveDefaults({ default_list_id: val });
    }
});

onMounted(async () => {
    await loadWorkspaces();
    if (selectedWorkspace.value) {
        await loadSpaces(selectedWorkspace.value);
    }
    if (selectedSpace.value) {
        await loadFolders(selectedSpace.value);
    }
    if (selectedFolder.value) {
        await loadLists(selectedFolder.value, null);
    } else if (selectedSpace.value) {
        await loadLists(null, selectedSpace.value);
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
                    <SelectValue placeholder="Wybierz workspace..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="ws in workspaceList" :key="ws.id" :value="ws.id">
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
                    <SelectValue placeholder="Wybierz space..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="s in spaceList" :key="s.id" :value="s.id">
                        {{ s.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="selectedSpace" class="grid gap-2">
            <Label>Folder</Label>
            <Skeleton v-if="loadingFolders" class="h-9 w-full" />
            <Select v-else v-model="selectedFolder">
                <SelectTrigger>
                    <SelectValue placeholder="Wybierz folder..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="__none__">Bez folderu (listy bez folderu)</SelectItem>
                    <SelectItem v-for="f in folderList" :key="f.id" :value="f.id">
                        {{ f.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <div v-if="selectedFolder" class="grid gap-2">
            <Label>Lista</Label>
            <Skeleton v-if="loadingLists" class="h-9 w-full" />
            <Select v-else v-model="selectedList">
                <SelectTrigger>
                    <SelectValue placeholder="Wybierz listę..." />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem v-for="l in listList" :key="l.id" :value="l.id">
                        {{ l.name }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>
    </div>
</template>
