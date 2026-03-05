<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { guide } from '@/routes/morning-hub';
import type { BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    { title: 'Przewodnik', href: guide() },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Przewodnik" />

        <div class="mx-auto max-w-3xl space-y-10 p-6">
            <Heading title="Przewodnik" description="Porady i dobre praktyki, dzięki którym wyciągniesz więcej z narzędzi projektu." />

            <!-- Zadania na dziś -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">Blok "Zadania na dziś"</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        Ten blok pobiera z ClickUp zadania przypisane do Ciebie z terminem na dziś.
                        Żeby działał skutecznie:
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Ustaw <strong>due date</strong> na zadaniach — bez daty zadanie nie pojawi się w bloku.</li>
                        <li>Upewnij się, że jesteś <strong>przypisany</strong> (assignee) do zadań, które chcesz widzieć.</li>
                        <li>W konfiguracji bloku wybierz połączenia ClickUp, z których chcesz zbierać zadania.</li>
                    </ul>
                </div>
            </section>

            <!-- ClickUp — statusy -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">ClickUp — spójne statusy</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        Bloki ClickUp pobierają statusy z list. Jeśli w różnych listach masz różne nazwy statusów
                        (np. "Do zrobienia" vs "To Do"), utrudnia to szybkie orientowanie się w zadaniach.
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Staraj się używać <strong>tych samych nazw statusów</strong> we wszystkich listach.</li>
                        <li>Zalecany zestaw: <strong>to do</strong>, <strong>in progress</strong>, <strong>review</strong>, <strong>done</strong>.</li>
                        <li>Zmianę statusów w ClickUp znajdziesz w ustawieniach Space → Statuses.</li>
                    </ul>
                </div>
            </section>

            <!-- Blok ClickUp -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">Blok ClickUp</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        Blok ClickUp wyświetla zadania z wybranych list. Żeby skonfigurować go optymalnie:
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Na stronie <strong>ClickUp</strong> dodaj połączenie i skonfiguruj domyślne listy.</li>
                        <li>Możesz ustawić <strong>filtry domyślne</strong> w połączeniu — np. wykluczyć zamknięte zadania.</li>
                        <li>Blok umożliwia szybką zmianę statusu i dodawanie nowych zadań bezpośrednio z panelu.</li>
                    </ul>
                </div>
            </section>

            <!-- Rutyna poranna -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">Rutyna poranna</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        Panel (Dashboard) wyświetla bloki w kolejności ustalonej na stronie Rutyna.
                    </p>
                    <ul class="list-disc space-y-1 pl-5">
                        <li>Możesz ustawić <strong>timer</strong> dla każdego bloku — pomaga trzymać się ram czasowych.</li>
                        <li>Przeciągnij bloki, żeby zmienić kolejność i dopasować je do swojego poranka.</li>
                        <li>Postęp rutyny (ukończone bloki, czas) jest zapisywany lokalnie na dany dzień.</li>
                    </ul>
                </div>
            </section>

            <!-- Priorytety -->
            <section class="space-y-3">
                <h3 class="text-lg font-semibold tracking-tight">Priorytety w ClickUp</h3>
                <div class="text-sm leading-relaxed text-muted-foreground space-y-2">
                    <p>
                        Karty zadań wyświetlają kolorowy badge priorytetu. Żeby działał poprawnie, ustaw priorytety
                        bezpośrednio w ClickUp (Urgent, High, Normal, Low).
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
