<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, CalendarCheck, LayoutGrid, ListChecks, Plug } from 'lucide-vue-next';
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
import { dashboard, todaysTasks } from '@/routes';
import { index as clickupIndex } from '@/routes/morning-hub/clickup';
import { guide } from '@/routes/morning-hub';
import { index as routineIndex } from '@/routes/morning-hub/routine';
import { index as todaysTasksConfigIndex } from '@/routes/morning-hub/todays-tasks';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Panel',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Zadania na dziś',
        href: todaysTasks(),
        icon: CalendarCheck,
    },
];

const morningHubNavItems: NavItem[] = [
    {
        title: 'Rutyna',
        href: routineIndex(),
        icon: ListChecks,
    },
    {
        title: 'Zadania na dziś',
        href: todaysTasksConfigIndex(),
        icon: CalendarCheck,
    },
    {
        title: 'ClickUp',
        href: clickupIndex(),
        icon: Plug,
    },
    {
        title: 'Przewodnik',
        href: guide(),
        icon: BookOpen,
    },
];

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
            <NavMain :items="mainNavItems" />
            <NavMain :items="morningHubNavItems" label="Morning Hub" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
