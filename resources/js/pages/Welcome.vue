<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { CheckCircle2, ListChecks, Timer } from 'lucide-vue-next';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

withDefaults(
    defineProps<{
        canRegister: boolean;
    }>(),
    {
        canRegister: true,
    },
);

const features = [
    {
        icon: Timer,
        title: 'Focused Timer',
        description: 'Time-box each routine block to stay on track and build consistent habits.',
    },
    {
        icon: ListChecks,
        title: 'ClickUp Tasks',
        description: 'Pull your priority tasks directly from ClickUp so nothing slips through.',
    },
    {
        icon: CheckCircle2,
        title: 'Daily Habits',
        description: 'Track recurring habits with a simple checklist that resets each morning.',
    },
];
</script>

<template>
    <Head title="Morning Hub" />

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Nav -->
        <header class="flex items-center justify-between px-6 py-4 lg:px-10">
            <div class="flex items-center gap-2">
                <AppLogoIcon class="h-7 w-7 text-primary" />
                <span class="text-lg font-semibold tracking-tight">Morning Hub</span>
            </div>
            <nav class="flex items-center gap-3">
                <template v-if="$page.props.auth.user">
                    <Link :href="dashboard()">
                        <Button size="sm">Dashboard</Button>
                    </Link>
                </template>
                <template v-else>
                    <Link :href="login.url()">
                        <Button variant="ghost" size="sm">Log in</Button>
                    </Link>
                    <Link v-if="canRegister" :href="register.url()">
                        <Button size="sm">Register</Button>
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Hero -->
        <main class="flex flex-1 flex-col items-center justify-center px-6 text-center">
            <div class="max-w-2xl space-y-6">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">
                    Your morning routine,<br />
                    <span class="text-primary">streamlined.</span>
                </h1>
                <p class="mx-auto max-w-lg text-lg text-muted-foreground">
                    Organize tasks, track habits, and stay focused — all in one calm,
                    distraction-free dashboard.
                </p>
                <div>
                    <Link v-if="$page.props.auth.user" :href="dashboard()">
                        <Button size="lg">Go to Dashboard</Button>
                    </Link>
                    <Link v-else-if="canRegister" :href="register.url()">
                        <Button size="lg">Get Started</Button>
                    </Link>
                    <Link v-else :href="login.url()">
                        <Button size="lg">Log In</Button>
                    </Link>
                </div>
            </div>

            <!-- Feature Cards -->
            <div class="mt-16 grid w-full max-w-4xl gap-4 sm:grid-cols-3">
                <Card v-for="feature in features" :key="feature.title" class="text-left">
                    <CardHeader>
                        <component :is="feature.icon" class="mb-1 h-6 w-6 text-primary" />
                        <CardTitle class="text-base">{{ feature.title }}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ feature.description }}</p>
                    </CardContent>
                </Card>
            </div>
        </main>

        <!-- Footer with forest photo -->
        <footer class="relative mt-auto h-64 sm:h-80 lg:h-96">
            <img
                src="/images/forest.jpg"
                alt=""
                class="absolute inset-0 h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-linear-to-b from-background via-background/60 to-transparent" />
        </footer>
    </div>
</template>
