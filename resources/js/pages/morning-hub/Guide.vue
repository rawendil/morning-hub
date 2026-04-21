<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Card, CardContent } from '@/components/ui/card';
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

        <div class="mx-auto max-w-3xl space-y-6 p-6">
            <Heading
                :title="t('Przewodnik')"
                :description="
                    t(
                        'Porady i dobre praktyki, dzięki którym wyciągniesz więcej z narzędzi projektu.',
                    )
                "
            />

            <Card>
                <CardContent class="space-y-10 py-6">
                    <!-- Zadania na dziś -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Zadania na dziś') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Strona "Zadania na dziś" pobiera z ClickUp zadania przypisane do Ciebie z terminem na dziś. Żeby działała skutecznie:',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Ustaw <strong>due date</strong> na zadaniach — bez daty zadanie nie pojawi się na liście.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Upewnij się, że jesteś <strong>przypisany</strong> (assignee) do zadań, które chcesz widzieć.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'W <strong>Źródłach zadań</strong> (Konfiguracja) wybierz połączenia ClickUp, z których chcesz zbierać zadania.',
                                        )
                                    "
                                />
                            </ul>
                        </div>
                    </section>

                    <!-- ClickUp — statusy -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('ClickUp — spójne statusy') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Bloki ClickUp pobierają statusy z list. Jeśli w różnych listach masz różne nazwy statusów (np. "Do zrobienia" vs "To Do"), utrudnia to szybkie orientowanie się w zadaniach.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Staraj się używać <strong>tych samych nazw statusów</strong> we wszystkich listach.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Zalecany zestaw: <strong>to do</strong>, <strong>in progress</strong>, <strong>review</strong>, <strong>done</strong>.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Zmianę statusów w ClickUp znajdziesz w ustawieniach Space → Statuses.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Blok ClickUp -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Blok ClickUp') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Blok ClickUp wyświetla zadania z wybranych list. Żeby skonfigurować go optymalnie:',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'W <strong>Połączeniach ClickUp</strong> (Konfiguracja) dodaj połączenie i skonfiguruj domyślne listy.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Możesz ustawić <strong>filtry domyślne</strong> w połączeniu — np. wykluczyć zamknięte zadania.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Blok umożliwia szybką zmianę statusu i dodawanie nowych zadań bezpośrednio z panelu.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Rutyna poranna -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Rutyna poranna') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p
                                v-html="
                                    t(
                                        'Poranna rutyna wyświetla bloki w kolejności ustalonej w <strong>Blokach rutyny</strong> (Konfiguracja).',
                                    )
                                "
                            />
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Możesz ustawić <strong>timer</strong> dla każdego bloku — pomaga trzymać się ram czasowych.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Przeciągnij bloki, żeby zmienić kolejność i dopasować je do swojego poranka.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Postęp rutyny (ukończone bloki, czas) jest zapisywany lokalnie na dany dzień.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Priorytety -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Priorytety w ClickUp') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Karty zadań wyświetlają kolorowy badge priorytetu. Żeby działał poprawnie, ustaw priorytety bezpośrednio w ClickUp (Urgent, High, Normal, Low).',
                                    )
                                }}
                            </p>
                        </div>
                    </section>

                    <!-- Nawyki -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Blok nawyków') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Blok nawyków to codzienna checklista, która resetuje się automatycznie każdego ranka.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Dodaj nawyki w <strong>Blokach rutyny</strong> (Konfiguracja) — np. medytacja, ćwiczenia, woda.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Postęp nawyków jest zapisywany lokalnie i resetuje się o północy.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Na karcie bloku widać pasek postępu (np. 3/5 ukończonych).',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Kanał RSS -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Blok kanału RSS') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Blok kanału RSS wyświetla artykuły z wybranych źródeł RSS/Atom. Skonfiguruj go w Blokach rutyny:',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Dodaj <strong>źródła RSS</strong> — podaj nazwę i adres URL kanału.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Ustaw <strong>liczbę dni</strong> — ile dni wstecz mają być pobierane artykuły (domyślnie 5).',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Przeczytane artykuły są oznaczane ikoną oka — możesz je ukryć przełącznikiem.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Status przeczytanych artykułów jest zapisywany lokalnie przez 30 dni.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Zrzut myśli -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Blok zrzutu myśli') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Zrzut myśli pozwala szybko zapisać myśl jako zadanie w ClickUp — bez opuszczania panelu rutyny.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Wpisz myśl i naciśnij <strong>Ctrl+Enter</strong> (lub przycisk), aby utworzyć zadanie.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Zadanie trafia na <strong>domyślną listę</strong> połączenia ClickUp przypisanego do bloku.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Ostatnio utworzone zadania wyświetlają się pod polem tekstowym.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Google Calendar -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Blok Google Calendar') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Blok Google Calendar wyświetla wydarzenia z wybranych kalendarzy Google. Wymaga kilku kroków konfiguracji:',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Najpierw <strong>połącz konto Google</strong> w ustawieniach Profilu (sekcja Integracje).',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Następnie przejdź do <strong>Google Calendar</strong> (Konfiguracja) i połącz kalendarz.',
                                        )
                                    "
                                />
                                <li
                                    v-html="
                                        t(
                                            'Wybierz <strong>kalendarze</strong>, z których chcesz widzieć wydarzenia.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Wydarzenia pojawiają się również na stronie „Zadania na dziś" w formie timeline.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Timer -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Timer rutyny') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Każdy blok rutyny może mieć opcjonalny timer, który pomaga trzymać się ram czasowych poranka.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li
                                    v-html="
                                        t(
                                            'Ustaw <strong>czas w minutach</strong> przy tworzeniu lub edycji bloku.',
                                        )
                                    "
                                />
                                <li>
                                    {{
                                        t(
                                            'Timer obsługuje start, pauzę, wznowienie i pominięcie bloku.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Po upływie czasu blok jest oznaczany jako ukończony — przejdź do następnego ręcznie lub użyj przycisku pominięcia.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Stan timera jest zapisywany lokalnie — możesz zamknąć przeglądarkę i wrócić.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Zmiana statusu zadań -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Zmiana statusu zadań') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Możesz zmieniać status zadań ClickUp bezpośrednio z panelu — bez otwierania ClickUp.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li>
                                    {{
                                        t(
                                            'Kliknij badge statusu na karcie zadania, aby zobaczyć dostępne statusy.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Zmiana jest natychmiastowa — status aktualizuje się w ClickUp od razu.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Kliknij kartę zadania, aby otworzyć modal ze szczegółami, opisem i podzadaniami.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>

                    <!-- Dane lokalne -->
                    <section class="space-y-3">
                        <h3 class="text-lg font-semibold tracking-tight">
                            {{ t('Dane lokalne') }}
                        </h3>
                        <div
                            class="space-y-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            <p>
                                {{
                                    t(
                                        'Część danych (postęp rutyny, nawyki, przeczytane artykuły) jest przechowywana lokalnie w przeglądarce, a nie na serwerze.',
                                    )
                                }}
                            </p>
                            <ul class="list-disc space-y-1 pl-5">
                                <li>
                                    {{
                                        t(
                                            'Wyczyszczenie danych przeglądarki resetuje te dane.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Dane dzienne (timer, nawyki) resetują się automatycznie każdego ranka.',
                                        )
                                    }}
                                </li>
                                <li>
                                    {{
                                        t(
                                            'Konfiguracja bloków i połączeń jest zapisana na serwerze i działa na każdym urządzeniu.',
                                        )
                                    }}
                                </li>
                            </ul>
                        </div>
                    </section>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
