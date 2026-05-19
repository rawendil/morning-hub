<script setup lang="ts">
import { CheckCircle, Info, Plug, Trash2, XCircle } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import { toast } from 'vue-sonner';
import Heading from '@/components/Heading.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import axiosInstance from '@/lib/axios';
import type {
    BreadcrumbItem,
    GoogleCalendarConnection,
    GoogleCalendarListItem,
} from '@/types';

const { t } = useTranslations();

const connection = ref<GoogleCalendarConnection | null>(null);
const hasGoogleAccount = ref(false);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('Google Calendar'), href: '/morning-hub/google-calendar' },
]);

const connecting = ref(false);
const disconnectOpen = ref(false);

async function startConnect() {
    connecting.value = true;
    try {
        const { data } = await axiosInstance.post(
            '/morning-hub/google-calendar/connect',
        );
        window.location.href = data.url;
    } catch {
        toast.error(t('Blad polaczenia. Sprobuj ponownie.'));
        connecting.value = false;
    }
}
const testing = ref(false);
const testResult = ref<{ success: boolean; message: string } | null>(null);
const loadingCalendars = ref(false);
const availableCalendars = ref<GoogleCalendarListItem[]>([]);
const selectedCalendarIds = ref<string[]>([]);

async function loadConnection() {
    const { data } = await axiosInstance.get('/morning-hub/google-calendar');
    connection.value = data.connection ?? null;
    hasGoogleAccount.value = data.hasGoogleAccount ?? false;
    selectedCalendarIds.value = connection.value?.calendar_ids ?? [];
}

async function testConnection() {
    testing.value = true;
    testResult.value = null;

    try {
        const { data } = await axiosInstance.post(
            '/morning-hub/google-calendar/test',
        );
        testResult.value = data;
    } catch {
        testResult.value = { success: false, message: t('Blad sieci.') };
    }

    testing.value = false;
}

async function fetchCalendars() {
    loadingCalendars.value = true;

    try {
        const { data } = await axiosInstance.get(
            '/morning-hub/google-calendar/calendars',
        );
        availableCalendars.value = data.calendars ?? [];
    } catch {
        toast.error(t('Nie udalo sie pobrac listy kalendarzy.'));
    }

    loadingCalendars.value = false;
}

async function saveCalendars() {
    await axiosInstance.put('/morning-hub/google-calendar', {
        calendar_ids: selectedCalendarIds.value,
    });
    toast.success(t('Kalendarze zostaly zapisane.'));
}

async function disconnectCalendar() {
    await axiosInstance.delete('/morning-hub/google-calendar');
    disconnectOpen.value = false;
    await loadConnection();
}

function toggleCalendar(calendarId: string, checked: boolean) {
    if (checked) {
        selectedCalendarIds.value = [...selectedCalendarIds.value, calendarId];
    } else {
        selectedCalendarIds.value = selectedCalendarIds.value.filter(
            (id) => id !== calendarId,
        );
    }
}

onMounted(async () => {
    await loadConnection();
    if (connection.value) {
        fetchCalendars();
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <div class="space-y-6 p-6">
            <Heading
                :title="t('Google Calendar')"
                :description="
                    t(
                        'Polacz swoj Google Calendar, aby wyswietlac wydarzenia w rutynie.',
                    )
                "
            />

            <!-- State 1: No Google account linked -->
            <Alert v-if="!hasGoogleAccount">
                <Info class="h-4 w-4" />
                <AlertTitle>{{
                    t('Konto Google nie jest polaczone')
                }}</AlertTitle>
                <AlertDescription>
                    {{
                        t(
                            'Aby korzystac z Google Calendar, najpierw polacz swoje konto Google w ustawieniach profilu.',
                        )
                    }}
                    <RouterLink
                        to="/settings/profile"
                        class="font-medium underline underline-offset-4"
                    >
                        {{ t('Przejdz do ustawien profilu') }}
                    </RouterLink>
                </AlertDescription>
            </Alert>

            <!-- State 2: Google account linked but no calendar connection -->
            <Card v-else-if="!connection">
                <CardHeader>
                    <CardTitle>{{ t('Polacz Google Calendar') }}</CardTitle>
                    <CardDescription>
                        {{
                            t(
                                'Twoje konto Google jest polaczone. Autoryzuj dostep do kalendarza, aby wyswietlac wydarzenia.',
                            )
                        }}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Button :disabled="connecting" @click="startConnect">
                        {{ t('Polacz Google Calendar') }}
                    </Button>
                </CardContent>
            </Card>

            <!-- State 3: Connected -->
            <template v-else>
                <Card>
                    <CardHeader
                        class="flex flex-row items-center justify-between space-y-0 pb-2"
                    >
                        <div>
                            <CardTitle class="text-base">{{
                                connection.name
                            }}</CardTitle>
                            <CardDescription>{{
                                t('Polaczono z Google Calendar')
                            }}</CardDescription>
                        </div>
                        <div class="flex items-center gap-1">
                            <Button
                                variant="ghost"
                                size="icon"
                                :disabled="testing"
                                @click="testConnection"
                            >
                                <Plug class="h-4 w-4" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                @click="disconnectOpen = true"
                            >
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent>
                        <div
                            v-if="testResult"
                            class="mb-4 flex items-center gap-2 text-sm"
                        >
                            <CheckCircle
                                v-if="testResult.success"
                                class="h-4 w-4 text-green-600"
                            />
                            <XCircle v-else class="h-4 w-4 text-red-600" />
                            <span
                                :class="
                                    testResult.success
                                        ? 'text-green-600'
                                        : 'text-red-600'
                                "
                            >
                                {{ testResult.message }}
                            </span>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{{ t('Wybierz kalendarze') }}</CardTitle>
                        <CardDescription>
                            {{
                                t(
                                    'Wybierz, ktore kalendarze chcesz wyswietlac w blokach rutyny.',
                                )
                            }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div
                            v-if="loadingCalendars"
                            class="flex items-center gap-2 py-4"
                        >
                            <Spinner class="h-4 w-4" />
                            <span class="text-sm text-muted-foreground">{{
                                t('Ladowanie kalendarzy...')
                            }}</span>
                        </div>

                        <div
                            v-else-if="availableCalendars.length === 0"
                            class="py-4 text-sm text-muted-foreground"
                        >
                            {{ t('Nie znaleziono kalendarzy.') }}
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                v-for="calendar in availableCalendars"
                                :key="calendar.id"
                                class="flex items-center gap-3"
                            >
                                <Checkbox
                                    :id="`cal-${calendar.id}`"
                                    :model-value="
                                        selectedCalendarIds.includes(
                                            calendar.id,
                                        )
                                    "
                                    @update:model-value="
                                        (checked: boolean | 'indeterminate') =>
                                            toggleCalendar(
                                                calendar.id,
                                                checked === true,
                                            )
                                    "
                                />
                                <span
                                    class="h-3 w-3 shrink-0 rounded-full"
                                    :style="{ backgroundColor: calendar.color }"
                                />
                                <Label
                                    :for="`cal-${calendar.id}`"
                                    class="cursor-pointer"
                                >
                                    {{ calendar.name }}
                                </Label>
                            </div>

                            <Button class="mt-4" @click="saveCalendars">
                                {{ t('Zapisz kalendarze') }}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </template>
        </div>

        <Dialog v-model:open="disconnectOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{
                        t('Rozlacz Google Calendar')
                    }}</DialogTitle>
                    <DialogDescription>
                        {{
                            t(
                                'Czy na pewno chcesz rozlaczyc Google Calendar? Bloki rutyny korzystajace z tego polaczenia zostana odlaczone.',
                            )
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">{{ t('Anuluj') }}</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="disconnectCalendar">
                        {{ t('Rozlacz') }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
