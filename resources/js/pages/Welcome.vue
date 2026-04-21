<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ClipboardList, Repeat, Lightbulb, Github } from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const { t } = useTranslations();

const features = computed(() => [
    {
        icon: ClipboardList,
        title: t('Organizacja zadań'),
        description: t(
            'Zbieraj zadania z ClickUp, planuj dzień i skup się na priorytetach — bez przeskakiwania między narzędziami.',
        ),
    },
    {
        icon: Repeat,
        title: t('Nawyki i rutyna'),
        description: t(
            'Buduj poranne rytuały z timerem i checklistą nawyków, które resetują się każdego ranka.',
        ),
    },
    {
        icon: Lightbulb,
        title: t('Inspiracja i notatki'),
        description: t(
            'Przeglądaj artykuły RSS, zapisuj myśli w zrzucie i zacznij dzień z czystą głową.',
        ),
    },
]);
</script>

<template>
    <Head title="Morning Hub" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Nav -->
        <header class="flex items-center justify-between px-6 py-4 lg:px-10">
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
                <template v-if="$page.props.auth.user">
                    <Link :href="dashboard()">
                        <Button size="sm">{{ t('Panel') }}</Button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="login.url()">
                        <Button variant="ghost" size="sm">{{
                            t('Zaloguj się')
                        }}</Button>
                    </Link>
                    <Link v-if="canRegister" :href="register.url()">
                        <Button size="sm">{{ t('Rejestracja') }}</Button>
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main
            class="flex flex-1 flex-col items-center justify-center px-6 text-center"
        >
            <div class="max-w-2xl space-y-6">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    {{ t('Twoja poranna rutyna,') }}<br />
                    <span class="text-primary">{{ t('uporządkowana.') }}</span>
                </h1>
                <p class="mx-auto max-w-lg text-lg text-muted-foreground">
                    {{
                        t(
                            'Organizuj zadania, śledź nawyki i zachowaj skupienie — wszystko w jednym spokojnym panelu bez rozpraszaczy.',
                        )
                    }}
                </p>
                <div>
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button size="lg">{{ t('Przejdź do panelu') }}</Button>
                    </Link>
                    <Link v-else-if="canRegister" :href="register.url()">
                        <Button size="lg">{{ t('Rozpocznij') }}</Button>
                    </Link>
                    <Link v-else :href="login.url()">
                        <Button size="lg">{{ t('Zaloguj się') }}</Button>
                    </Link>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="mt-16 grid w-full max-w-4xl gap-4 sm:grid-cols-3">
                <Card
                    v-for="feature in features"
                    :key="feature.title"
                    class="text-left"
                >
                    <CardHeader>
                        <component
                            :is="feature.icon"
                            class="mb-1 h-6 w-6 text-primary"
                        />
                        <CardTitle class="text-base">{{
                            feature.title
                        }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">
                            {{ feature.description }}
                        </p>
                    </CardContent>
                </Card>
            </div>
        </main>

        <!-- Author -->
        <div class="py-6 text-center">
            <p class="text-sm text-muted-foreground">
                Stworzone przez
                <a
                    href="https://rawcodestudio.net/"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="font-medium text-foreground underline decoration-muted-foreground/50 underline-offset-4 transition-colors hover:decoration-foreground"
                >
                    Raw Code Studio
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
