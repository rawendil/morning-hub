<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    Calendar,
    CalendarCheck,
    Cookie,
    Github,
    LayoutGrid,
    ListChecks,
    Plug,
} from 'lucide-vue-next';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCookieConsent } from '@/composables/useCookieConsent';
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, todaysTasks } from '@/routes';
import { guide } from '@/routes/morning-hub';
import { index as clickupIndex } from '@/routes/morning-hub/clickup';
import { index as googleCalendarIndex } from '@/routes/morning-hub/google-calendar';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import type { NavItem } from '@/types';

const { t } = useTranslations();
const { openSettings } = useCookieConsent();

const morningHubNavItems = computed<NavItem[]>(() => [
    {
        title: t('Poranna rutyna'),
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: t('Zadania na dziś'),
        href: todaysTasks(),
        icon: CalendarCheck,
    },
]);

const configNavItems = computed<NavItem[]>(() => [
    {
        title: t('Bloki rutyny'),
        href: routineIndex(),
        icon: ListChecks,
    },
    {
        title: t('Źródła zadań'),
        href: todaysTasksConfigIndex(),
        icon: CalendarCheck,
    },
    {
        title: t('Przewodnik'),
        href: guide(),
        icon: BookOpen,
    },
]);

const integrationNavItems = computed<NavItem[]>(() => [
    {
        title: 'ClickUp',
        href: clickupIndex(),
        icon: Plug,
    },
    {
        title: t('Google Calendar'),
        href: googleCalendarIndex(),
        icon: Calendar,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="morningHubNavItems" label="Morning Hub" />
            <NavMain :items="configNavItems" :label="t('Konfiguracja')" />
            <NavMain :items="integrationNavItems" :label="t('Integracje')" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton @click="openSettings">
                        <Cookie />
                        <span>{{ t('Ustawienia cookies') }}</span>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a
                            href="https://github.com/rawendil/morning-hub"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Github />
                            <span>GitHub</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem>
                    <SidebarMenuButton as-child>
                        <a
                            href="https://rawcodestudio.net/"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <img
                                src="https://rawcodestudio.net/favicon.ico"
                                class="h-4 w-4 shrink-0"
                                alt=""
                            />
                            <span>RawCode Studio</span>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
