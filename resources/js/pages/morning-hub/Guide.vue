<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { useTranslations } from '@/composables/useTranslations';
import AppLayout from '@/layouts/AppLayout.vue';
import { guide } from '@/routes/morning-hub';
import type { BreadcrumbItem } from '@/types';

const { t } = useTranslations();

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    { title: t('Przewodnik'), href: guide() },
]);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="t('Przewodnik')" />

        <div class="mx-auto max-w-3xl space-y-10 p-6">
            <Heading :title="t('Przewodnik')" :description="t('Porady i dobre praktyki, dzięki którym wyciągniesz więcej z narzędzi projektu.')" />

            <!-- Zadania na dziś -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Zadania na dziś') }}</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        {{ t('Strona "Zadania na dziś" pobiera z ClickUp zadania przypisane do Ciebie z terminem na dziś. Żeby działała skutecznie:') }}
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-html="t('Ustaw <strong>due date</strong> na zadaniach — bez daty zadanie nie pojawi się na liście.')" />
                        <li v-html="t('Upewnij się, że jesteś <strong>przypisany</strong> (assignee) do zadań, które chcesz widzieć.')" />
                        <li v-html="t('W <strong>Źródłach zadań</strong> (Konfiguracja) wybierz połączenia ClickUp, z których chcesz zbierać zadania.')" />
                    </ul>
                </div>
            </section>

            <!-- ClickUp — statusy -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('ClickUp — spójne statusy') }}</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        {{ t('Bloki ClickUp pobierają statusy z list. Jeśli w różnych listach masz różne nazwy statusów (np. "Do zrobienia" vs "To Do"), utrudnia to szybkie orientowanie się w zadaniach.') }}
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-html="t('Staraj się używać <strong>tych samych nazw statusów</strong> we wszystkich listach.')" />
                        <li v-html="t('Zalecany zestaw: <strong>to do</strong>, <strong>in progress</strong>, <strong>review</strong>, <strong>done</strong>.')" />
                        <li>{{ t('Zmianę statusów w ClickUp znajdziesz w ustawieniach Space → Statuses.') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Blok ClickUp -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Blok ClickUp') }}</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        {{ t('Blok ClickUp wyświetla zadania z wybranych list. Żeby skonfigurować go optymalnie:') }}
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-html="t('W <strong>Połączeniach ClickUp</strong> (Konfiguracja) dodaj połączenie i skonfiguruj domyślne listy.')" />
                        <li v-html="t('Możesz ustawić <strong>filtry domyślne</strong> w połączeniu — np. wykluczyć zamknięte zadania.')" />
                        <li>{{ t('Blok umożliwia szybką zmianę statusu i dodawanie nowych zadań bezpośrednio z panelu.') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Rutyna poranna -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Rutyna poranna') }}</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p v-html="t('Poranna rutyna wyświetla bloki w kolejności ustalonej w <strong>Blokach rutyny</strong> (Konfiguracja).')" />
                    <ul class="list-disc space-y-1 pl-5">
                        <li v-html="t('Możesz ustawić <strong>timer</strong> dla każdego bloku — pomaga trzymać się ram czasowych.')" />
                        <li>{{ t('Przeciągnij bloki, żeby zmienić kolejność i dopasować je do swojego poranka.') }}</li>
                        <li>{{ t('Postęp rutyny (ukończone bloki, czas) jest zapisywany lokalnie na dany dzień.') }}</li>
                    </ul>
                </div>
            </section>

            <!-- Priorytety -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">{{ t('Priorytety w ClickUp') }}</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        {{ t('Karty zadań wyświetlają kolorowy badge priorytetu. Żeby działał poprawnie, ustaw priorytety bezpośrednio w ClickUp (Urgent, High, Normal, Low).') }}
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
