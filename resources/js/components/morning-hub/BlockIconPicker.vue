<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/composables/useTranslations';
import { AVAILABLE_ICONS, ICON_MAP } from '@/lib/block-icons';

const { t } = useTranslations();

const model = defineModel<string>({ required: true });

const search = ref('');
const isOpen = ref(false);

const filteredIcons = computed(() => {
    if (!search.value) return AVAILABLE_ICONS;
    const q = search.value.toLowerCase();
    return AVAILABLE_ICONS.filter((i) => i.name.toLowerCase().includes(q));
});

const currentIcon = computed(() => ICON_MAP[model.value]);

function select(name: string) {
    model.value = name;
    isOpen.value = false;
    search.value = '';
}
</script>

<template>
    <div class="space-y-2">
        <Button
            type="button"
            variant="outline"
            class="flex h-9 w-full items-center justify-start gap-2"
            @click="isOpen = !isOpen"
        >
            <component :is="currentIcon" v-if="currentIcon" class="h-4 w-4 text-muted-foreground" />
            <span class="text-sm">{{ model || t('Wybierz ikonę...') }}</span>
        </Button>

        <div v-if="isOpen" class="space-y-2 rounded-md border p-3">
            <Input
                v-model="search"
                :placeholder="t('Szukaj ikony...')"
                class="h-8 text-sm"
            />
            <div class="grid max-h-40 grid-cols-8 gap-1 overflow-y-auto">
                <button
                    v-for="icon in filteredIcons"
                    :key="icon.name"
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-md transition-colors hover:bg-accent"
                    :class="{ 'bg-accent ring-1 ring-primary': model === icon.name }"
                    :title="icon.name"
                    @click="select(icon.name)"
                >
                    <component :is="icon.component" class="h-4 w-4" />
                </button>
            </div>
            <p v-if="filteredIcons.length === 0" class="text-center text-xs text-muted-foreground">
                {{ t('Brak wyników') }}
            </p>
        </div>
    </div>
</template>
