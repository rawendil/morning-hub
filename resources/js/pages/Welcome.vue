<script setup lang="ts">
import { Github, LayoutGrid, RefreshCw, Shield, Zap } from 'lucide-vue-next';
import { computed } from 'vue';
import { RouterLink } from 'vue-router';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { useAuthStore } from '@/stores/auth';

const { t } = useTranslations();
const auth = useAuthStore();

const featureRows = computed(() => [
    {
        title: t('Zaprojektuj swój idealny poranek'),
        description: t(
            'Dodaj bloki rutyny, przypisz czas i ułóż w kolejności. Każdy blok to inny moduł — feed RSS, nawyki, notatki, zadania, własny tekst. Strona resetuje się każdego ranka.',
        ),
        tags: [t('Bloki rutyny'), 'Timer', t('Nawyki'), t('Braindump')],
        image: '/images/morning-rutine-config.png',
        reversed: false,
    },
    {
        title: t('Połącz narzędzia, których już używasz'),
        description: t(
            'Importuj zadania z ClickUp i wydarzenia z Google Calendar. Filtry po statusach i przypisanych osobach — zobaczysz tylko to, co ważne na dziś.',
        ),
        tags: ['ClickUp', 'Google Calendar', t('Filtry zadań')],
        image: '/images/clickup-config.png',
        reversed: true,
    },
]);

const whyCards = computed(() => [
    {
        icon: LayoutGrid,
        title: t('Jeden panel, zero przeskakiwania'),
        description: t(
            'Zadania, kalendarz, feed i notatki w jednym miejscu — bez otwierania 5 kart.',
        ),
    },
    {
        icon: RefreshCw,
        title: t('Codzienny reset, codzienny start'),
        description: t(
            'Nawyki i timer resetują się każdego ranka. Każdy dzień zaczyna się od zera.',
        ),
    },
    {
        icon: Shield,
        title: t('Twoje dane, Twoja kontrola'),
        description: t(
            'Kod w pełni otwarty. Możesz postawić własną instancję i ufać temu, z czego korzystasz.',
        ),
    },
    {
        icon: Zap,
        title: t('Bez rozpraszaczy'),
        description: t(
            'Minimalistyczny interfejs bez powiadomień, reklam i gamifikacji. Tylko to, co skonfigurowałeś.',
        ),
    },
]);
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Nav -->
        <header
            class="sticky top-0 z-10 flex items-center justify-between border-b border-border bg-background/95 px-6 py-4 backdrop-blur-sm lg:px-10"
        >
            <div class="flex items-center gap-2">
                <AppLogoIcon class="h-7 w-7 text-primary" />
                <span class="text-lg font-semibold tracking-tight"
                    >Morning Hub</span
                >
            </div>
            <nav class="flex items-center gap-3">
                <a
                    href="https://github.com/rawendil/morning-hub"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                >
                    <Github class="h-5 w-5" />
                </a>
                <LanguageSwitcher />
                <template v-if="auth.isAuthenticated">
                    <Button size="sm" as-child>
                        <RouterLink to="/dashboard">{{ t('Panel') }}</RouterLink>
                    </Button>
                </template>
                <template v-else>
                    <Button variant="ghost" size="sm" as-child>
                        <RouterLink to="/login">{{ t('Zaloguj się') }}</RouterLink>
                    </Button>
                    <Button size="sm" as-child>
                        <RouterLink to="/register">{{ t('Rejestracja') }}</RouterLink>
                    </Button>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <section class="px-6 pb-14 pt-20 text-center lg:px-10">
            <div class="mx-auto max-w-2xl space-y-6">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3 py-1 text-xs font-medium text-primary"
                >
                    🌿 {{ t('Open source · Bezpłatny') }}
                </span>
                <h1 class="text-5xl font-bold tracking-tight sm:text-6xl">
                    {{ t('Twoja poranna rutyna,') }}<br />
                    <span class="text-primary">{{ t('uporządkowana.') }}</span>
                </h1>
                <p class="mx-auto max-w-lg text-lg text-muted-foreground">
                    {{
                        t(
                            'Jeden panel do zadań, nawyków, artykułów i kalendarza — zacznij dzień skupiony, nie przytłoczony.',
                        )
                    }}
                </p>
                <div class="flex items-center justify-center gap-3">
                    <Button v-if="auth.isAuthenticated" size="lg" as-child>
                        <RouterLink to="/dashboard">{{ t('Przejdź do panelu') }}</RouterLink>
                    </Button>
                    <template v-else>
                        <Button size="lg" as-child>
                            <RouterLink to="/register">{{ t('Rozpocznij za darmo') }}</RouterLink>
                        </Button>
                        <Button variant="outline" size="lg" class="gap-2" as-child>
                            <a
                                href="https://github.com/rawendil/morning-hub"
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <Github class="h-4 w-4" />
                                GitHub
                            </a>
                        </Button>
                    </template>
                </div>
            </div>
        </section>

        <!-- Main Screenshot -->
        <section class="px-6 pb-20 lg:px-10">
            <div
                class="mx-auto max-w-5xl overflow-hidden rounded-xl border border-border shadow-2xl"
            >
                <img
                    src="/images/rutine-dashboard.png"
                    :alt="t('Morning Hub dashboard')"
                    class="w-full"
                />
            </div>
            <p class="mt-4 text-center text-sm text-muted-foreground">
                {{
                    t(
                        'Poranna rutyna z feedem RSS, timerem bloku i nawigacją — wszystko w jednym widoku',
                    )
                }}
            </p>
        </section>

        <!-- Feature Rows -->
        <section class="bg-muted px-6 py-20 lg:px-10">
            <p
                class="mb-14 text-center text-xs font-semibold uppercase tracking-widest text-primary"
            >
                {{ t('Funkcjonalności') }}
            </p>
            <div class="mx-auto max-w-5xl space-y-24">
                <div
                    v-for="feature in featureRows"
                    :key="feature.title"
                    class="grid items-center gap-16 lg:grid-cols-2"
                >
                    <div :class="{ 'lg:order-last': feature.reversed }">
                        <h3 class="mb-4 text-2xl font-bold tracking-tight">
                            {{ feature.title }}
                        </h3>
                        <p class="mb-5 leading-relaxed text-muted-foreground">
                            {{ feature.description }}
                        </p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in feature.tags"
                                :key="tag"
                                class="rounded-full border border-border bg-card px-3 py-0.5 text-xs font-medium text-primary"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>
                    <div
                        :class="{ 'lg:order-first': feature.reversed }"
                        class="overflow-hidden rounded-lg border border-border shadow-md"
                    >
                        <img
                            :src="feature.image"
                            :alt="feature.title"
                            class="w-full"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- Why Morning Hub -->
        <section class="px-6 py-20 lg:px-10">
            <div class="mx-auto max-w-4xl">
                <h2 class="mb-3 text-center text-3xl font-bold tracking-tight">
                    {{ t('Dlaczego Morning Hub?') }}
                </h2>
                <p class="mb-12 text-center text-muted-foreground">
                    {{
                        t(
                            'Zbudowany z jedną myślą — żebyś zaczął dzień skupiony, a nie rozbity.',
                        )
                    }}
                </p>
                <div class="grid gap-5 sm:grid-cols-2">
                    <Card v-for="card in whyCards" :key="card.title">
                        <CardHeader>
                            <component
                                :is="card.icon"
                                class="mb-1 h-5 w-5 text-primary"
                            />
                            <CardTitle class="text-base">{{
                                card.title
                            }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ card.description }}
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Open Source -->
        <section
            class="bg-primary px-6 py-20 text-center text-primary-foreground lg:px-10"
        >
            <h2 class="mb-3 text-3xl font-bold tracking-tight">
                {{ t('Zbudowane otwarcie') }}
            </h2>
            <p class="mx-auto mb-7 max-w-md text-primary-foreground/75">
                {{
                    t(
                        'Morning Hub jest w pełni open source. Postaw własną instancję, zgłoś issue lub dorzuć PR.',
                    )
                }}
            </p>
            <a
                href="https://github.com/rawendil/morning-hub"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-2 rounded-lg bg-primary-foreground px-5 py-2.5 text-sm font-semibold text-primary transition-opacity hover:opacity-90"
            >
                <Github class="h-4 w-4" />
                rawendil/morning-hub
            </a>
            <p class="mt-6 text-xs text-primary-foreground/40">
                Laravel 12 · Vue 3 · Tailwind CSS v4
            </p>
        </section>

        <!-- Final CTA -->
        <section class="px-6 py-20 text-center lg:px-10">
            <h2 class="mb-3 text-3xl font-bold tracking-tight">
                {{ t('Zacznij swój poranek lepiej.') }}
            </h2>
            <p class="mb-7 text-muted-foreground">
                {{
                    t('Bezpłatnie. Bez karty kredytowej. Gotowe w 2 minuty.')
                }}
            </p>
            <Button v-if="auth.isAuthenticated" size="lg" as-child>
                <RouterLink to="/dashboard">{{ t('Panel') }}</RouterLink>
            </Button>
            <Button v-else size="lg" as-child>
                <RouterLink to="/register">{{ t('Utwórz konto') }}</RouterLink>
            </Button>
        </section>

        <!-- Author + links -->
        <div class="py-6 text-center">
            <p class="text-sm text-muted-foreground">
                {{ t('Stworzone przez') }}
                <a
                    href="https://rawcodestudio.net/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-foreground underline decoration-muted-foreground/50 underline-offset-4 transition-colors hover:decoration-foreground"
                >
                    Raw Code Studio
                </a>
                &nbsp;·&nbsp;
                <a
                    href="/privacy-policy"
                    class="text-foreground underline decoration-muted-foreground/50 underline-offset-4 transition-colors hover:decoration-foreground"
                >
                    {{ t('Polityka prywatności') }}
                </a>
                &nbsp;·&nbsp;
                <a
                    href="/terms-of-service"
                    class="text-foreground underline decoration-muted-foreground/50 underline-offset-4 transition-colors hover:decoration-foreground"
                >
                    {{ t('Regulamin') }}
                </a>
            </p>
        </div>

        <!-- Footer with forest photo -->
        <footer class="relative mt-auto h-64 sm:h-80 lg:h-96">
            <img
                src="/images/forest.jpg"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div
                class="absolute inset-0 bg-linear-to-b from-background via-background/60 to-transparent"
            />
        </footer>
    </div>
</template>
