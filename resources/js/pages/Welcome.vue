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

        <!-- Footer with forest silhouette -->
        <footer class="mt-auto overflow-hidden">
            <svg
                class="w-full text-primary/10"
                viewBox="0 0 1440 120"
                preserveAspectRatio="none"
                fill="currentColor"
                xmlns="http://www.w3.org/2000/svg"
            >
                <!-- Tree silhouettes -->
                <path d="M0,120 L0,95 L20,95 L30,50 L40,80 L50,30 L60,80 L70,50 L80,95 L100,95 L100,120 Z" />
                <path d="M120,120 L120,90 L135,90 L140,60 L145,75 L150,35 L155,75 L160,60 L165,90 L180,90 L180,120 Z" />
                <path d="M200,120 L200,85 L215,85 L225,40 L235,70 L245,15 L255,70 L265,40 L275,85 L290,85 L290,120 Z" />
                <path d="M310,120 L310,92 L325,92 L332,55 L340,78 L348,25 L356,78 L363,55 L370,92 L385,92 L385,120 Z" />
                <path d="M420,120 L420,88 L440,88 L450,45 L460,72 L470,20 L480,72 L490,45 L500,88 L520,88 L520,120 Z" />
                <path d="M550,120 L550,93 L562,93 L570,58 L578,80 L585,32 L592,80 L600,58 L608,93 L620,93 L620,120 Z" />
                <path d="M660,120 L660,86 L678,86 L688,42 L698,68 L708,10 L718,68 L728,42 L738,86 L756,86 L756,120 Z" />
                <path d="M780,120 L780,90 L795,90 L805,52 L815,76 L825,28 L835,76 L845,52 L855,90 L870,90 L870,120 Z" />
                <path d="M900,120 L900,87 L918,87 L928,48 L938,73 L948,22 L958,73 L968,48 L978,87 L996,87 L996,120 Z" />
                <path d="M1020,120 L1020,92 L1035,92 L1045,56 L1055,79 L1065,30 L1075,79 L1085,56 L1095,92 L1110,92 L1110,120 Z" />
                <path d="M1140,120 L1140,84 L1160,84 L1170,38 L1182,66 L1194,8 L1206,66 L1218,38 L1228,84 L1248,84 L1248,120 Z" />
                <path d="M1270,120 L1270,91 L1285,91 L1295,54 L1305,77 L1315,26 L1325,77 L1335,54 L1345,91 L1360,91 L1360,120 Z" />
                <path d="M1380,120 L1380,88 L1395,88 L1405,50 L1415,74 L1425,24 L1435,74 L1440,60 L1440,120 Z" />
                <!-- Ground fill -->
                <rect x="0" y="95" width="1440" height="25" />
            </svg>
        </footer>
    </div>
</template>
