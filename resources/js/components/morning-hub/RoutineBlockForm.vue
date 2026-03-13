<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/MorningHub/RoutineBlockController';
import InputError from '@/components/InputError.vue';
import BlockIconPicker from '@/components/morning-hub/BlockIconPicker.vue';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/composables/useTranslations';
import { getDefaultIconName } from '@/lib/block-icons';
import { index as googleCalendarIndex } from '@/actions/App/Http/Controllers/MorningHub/GoogleCalendarConnectionController';
import type { BlockType, ClickUpConnection, RoutineBlock } from '@/types';

const { t } = useTranslations();

const props = defineProps<{
    block?: RoutineBlock;
    connections: ClickUpConnection[];
    googleCalendarConnectionId: number | null;
}>();

const isOpen = defineModel<boolean>('open', { default: false });

const blockTypes = computed<{ value: BlockType; label: string }[]>(() => [
    { value: 'clickup', label: 'ClickUp' },
    { value: 'braindump', label: t('Zrzut myśli') },
    { value: 'habits', label: t('Codzienne nawyki') },
    { value: 'feed', label: t('Kanał RSS') },
    { value: 'notes', label: t('Notatki') },
    { value: 'plan', label: t('Plan') },
    { value: 'google_calendar', label: 'Google Calendar' },
    { value: 'custom', label: t('Własny') },
]);

const selectedType = ref<string>(props.block?.type ?? '');
const selectedIcon = ref<string>(
    (props.block?.config?.icon as string) ||
        (props.block?.type ? getDefaultIconName(props.block.type) : ''),
);
const habits = ref<string[]>((props.block?.config?.habits as string[]) ?? ['']);
const feedSources = ref<{ name: string; url: string }[]>(
    (props.block?.config?.sources as { name: string; url: string }[]) ?? [
        { name: '', url: '' },
    ],
);
const feedDays = ref<number>((props.block?.config?.days as number) ?? 5);
const placeholderText = ref<string>(
    (props.block?.config?.placeholder_text as string) ?? '',
);
const placeholderUrl = ref<string>(
    (props.block?.config?.placeholder_url as string) ?? '',
);

watch(
    () => props.block,
    (newBlock) => {
        selectedType.value = newBlock?.type ?? '';
        selectedIcon.value =
            (newBlock?.config?.icon as string) ||
            (newBlock?.type ? getDefaultIconName(newBlock.type) : '');
        habits.value = (newBlock?.config?.habits as string[]) ?? [''];
        feedSources.value = (newBlock?.config?.sources as {
            name: string;
            url: string;
        }[]) ?? [{ name: '', url: '' }];
        feedDays.value = (newBlock?.config?.days as number) ?? 5;
        placeholderText.value =
            (newBlock?.config?.placeholder_text as string) ?? '';
        placeholderUrl.value =
            (newBlock?.config?.placeholder_url as string) ?? '';
    },
);

watch(selectedType, (newType) => {
    if (newType) {
        selectedIcon.value = getDefaultIconName(newType as BlockType);
    }
});

const needsConnection = computed(
    () =>
        selectedType.value === 'clickup' || selectedType.value === 'braindump',
);
const usesPlaceholder = computed(() =>
    ['notes', 'plan', 'custom'].includes(selectedType.value),
);
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent>
            <Form
                v-bind="block ? update.form(block) : store.form()"
                class="space-y-6"
                v-slot="{ errors, processing }"
                @success="isOpen = false"
            >
                <DialogHeader>
                    <DialogTitle>{{
                        block ? t('Edytuj blok') : t('Dodaj blok')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            block
                                ? t('Zaktualizuj ten blok rutyny.')
                                : t('Dodaj nowy blok do porannej rutyny.')
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>{{ t('Typ') }}</Label>
                        <input
                            type="hidden"
                            name="type"
                            :value="selectedType"
                        />
                        <Select v-model="selectedType">
                            <SelectTrigger>
                                <SelectValue
                                    :placeholder="t('Wybierz typ bloku...')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="bt in blockTypes"
                                    :key="bt.value"
                                    :value="bt.value"
                                >
                                    {{ bt.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="block-name">{{ t('Nazwa') }}</Label>
                        <Input
                            id="block-name"
                            name="name"
                            :default-value="block?.name"
                            required
                            :placeholder="
                                t('np. Przegląd zadań, Szybkie notatki')
                            "
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div v-if="selectedType" class="grid gap-2">
                        <Label>{{ t('Ikona') }}</Label>
                        <input
                            type="hidden"
                            name="config[icon]"
                            :value="selectedIcon"
                        />
                        <BlockIconPicker v-model="selectedIcon" />
                        <InputError :message="errors['config.icon']" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="block-timer">{{
                            t('Timer (minuty)')
                        }}</Label>
                        <Input
                            id="block-timer"
                            name="timer_minutes"
                            type="number"
                            min="1"
                            max="120"
                            :default-value="block?.timer_minutes?.toString()"
                            :placeholder="t('Opcjonalnie')"
                        />
                        <InputError :message="errors.timer_minutes" />
                    </div>

                    <div v-if="needsConnection" class="grid gap-2">
                        <Label>{{ t('Połączenie ClickUp') }}</Label>
                        <input
                            v-if="!connections.length"
                            type="hidden"
                            name="clickup_connection_id"
                            value=""
                        />
                        <Select
                            v-else
                            name="clickup_connection_id"
                            :default-value="
                                block?.clickup_connection_id?.toString()
                            "
                        >
                            <SelectTrigger>
                                <SelectValue
                                    :placeholder="t('Wybierz połączenie...')"
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="conn in connections"
                                    :key="conn.id"
                                    :value="conn.id.toString()"
                                >
                                    {{ conn.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="!connections.length"
                            class="text-sm text-muted-foreground"
                        >
                            {{
                                t(
                                    'Brak dostępnych połączeń ClickUp. Najpierw dodaj połączenie.',
                                )
                            }}
                        </p>
                        <InputError :message="errors.clickup_connection_id" />
                    </div>

                    <div
                        v-if="selectedType === 'google_calendar'"
                        class="grid gap-2"
                    >
                        <Label>{{ t('Połączenie Google Calendar') }}</Label>
                        <input
                            v-if="googleCalendarConnectionId"
                            type="hidden"
                            name="google_calendar_connection_id"
                            :value="googleCalendarConnectionId"
                        />
                        <p
                            v-if="googleCalendarConnectionId"
                            class="text-sm text-muted-foreground"
                        >
                            {{ t('Połączenie Google Calendar jest aktywne.') }}
                            <a
                                :href="googleCalendarIndex.url()"
                                class="underline"
                            >
                                {{ t('Konfiguruj kalendarze') }}
                            </a>
                        </p>
                        <p v-else class="text-sm text-muted-foreground">
                            {{ t('Brak połączenia Google Calendar.') }}
                            <a
                                :href="googleCalendarIndex.url()"
                                class="underline"
                            >
                                {{ t('Połącz Google Calendar') }}
                            </a>
                        </p>
                        <InputError
                            :message="errors.google_calendar_connection_id"
                        />
                    </div>

                    <div v-if="selectedType === 'habits'" class="grid gap-2">
                        <Label>{{ t('Codzienne nawyki') }}</Label>
                        <div
                            v-for="(_, index) in habits"
                            :key="index"
                            class="flex items-center gap-2"
                        >
                            <input
                                type="hidden"
                                :name="`config[habits][${index}]`"
                                :value="habits[index]"
                            />
                            <Input
                                v-model="habits[index]"
                                :placeholder="
                                    t('np. Obejrzeć film na Laracasts')
                                "
                            />
                            <Button
                                v-if="habits.length > 1"
                                type="button"
                                variant="ghost"
                                size="icon"
                                class="h-8 w-8 shrink-0"
                                @click="habits.splice(index, 1)"
                            >
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="habits.push('')"
                        >
                            <Plus class="h-4 w-4" />
                            {{ t('Dodaj nawyk') }}
                        </Button>
                        <InputError :message="errors['config.habits']" />
                    </div>

                    <div v-if="selectedType === 'feed'" class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="feed-days">{{ t('Liczba dni') }}</Label>
                            <input
                                type="hidden"
                                name="config[days]"
                                :value="feedDays"
                            />
                            <Input
                                id="feed-days"
                                v-model.number="feedDays"
                                type="number"
                                min="1"
                                max="30"
                                placeholder="5"
                            />
                            <InputError :message="errors['config.days']" />
                        </div>

                        <div class="grid gap-2">
                            <Label>{{ t('Źródła RSS/Atom') }}</Label>
                            <div
                                v-for="(_, index) in feedSources"
                                :key="index"
                                class="flex items-start gap-2"
                            >
                                <input
                                    type="hidden"
                                    :name="`config[sources][${index}][name]`"
                                    :value="feedSources[index].name"
                                />
                                <input
                                    type="hidden"
                                    :name="`config[sources][${index}][url]`"
                                    :value="feedSources[index].url"
                                />
                                <div class="grid flex-1 gap-1">
                                    <Input
                                        v-model="feedSources[index].name"
                                        :placeholder="t('Nazwa źródła')"
                                    />
                                    <Input
                                        v-model="feedSources[index].url"
                                        placeholder="https://example.com/feed"
                                    />
                                </div>
                                <Button
                                    v-if="feedSources.length > 1"
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    class="mt-1 h-8 w-8 shrink-0"
                                    @click="feedSources.splice(index, 1)"
                                >
                                    <X class="h-4 w-4" />
                                </Button>
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                @click="feedSources.push({ name: '', url: '' })"
                            >
                                <Plus class="h-4 w-4" />
                                {{ t('Dodaj źródło') }}
                            </Button>
                            <InputError :message="errors['config.sources']" />
                        </div>
                    </div>

                    <div v-if="usesPlaceholder" class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="placeholder-text">{{
                                t('Treść bloku')
                            }}</Label>
                            <input
                                type="hidden"
                                name="config[placeholder_text]"
                                :value="placeholderText"
                            />
                            <Input
                                id="placeholder-text"
                                v-model="placeholderText"
                                :placeholder="t('np. Pracuj nad ...')"
                            />
                            <InputError
                                :message="errors['config.placeholder_text']"
                            />
                        </div>
                        <div class="grid gap-2">
                            <Label for="placeholder-url">{{
                                t('Link (opcjonalnie)')
                            }}</Label>
                            <input
                                type="hidden"
                                name="config[placeholder_url]"
                                :value="placeholderUrl"
                            />
                            <Input
                                id="placeholder-url"
                                v-model="placeholderUrl"
                                type="url"
                                placeholder="https://example.com"
                            />
                            <InputError
                                :message="errors['config.placeholder_url']"
                            />
                        </div>
                    </div>
                </div>

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button type="submit" :disabled="processing">
                        {{ block ? t('Zapisz') : t('Dodaj blok') }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
