<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { BookOpen, Calendar, CalendarCheck, LayoutGrid, ListChecks, Palette, Plug } from 'lucide-vue-next';
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
import { useTranslations } from '@/composables/useTranslations';
import { dashboard, todaysTasks } from '@/routes';
import { index as clickupIndex } from '@/routes/morning-hub/clickup';
import { index as googleCalendarIndex } from '@/routes/morning-hub/google-calendar';
import { guide, themeShowcase } from '@/routes/morning-hub';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import type { NavItem } from '@/types';

const { t } = useTranslations();

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
        title: t('Połączenia ClickUp'),
        href: clickupIndex(),
        icon: Plug,
    },
    {
        title: t('Google Calendar'),
        href: googleCalendarIndex(),
        icon: Calendar,
    },
    {
        title: t('Przewodnik'),
        href: guide(),
        icon: BookOpen,
    },
    {
        title: 'Theme Showcase',
        href: themeShowcase(),
        icon: Palette,
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
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
