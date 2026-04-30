<script setup lang="ts">
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
import { RouterLink } from 'vue-router';
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
import type { NavItem } from '@/types';

const { t } = useTranslations();
const { openSettings } = useCookieConsent();

const morningHubNavItems = computed<NavItem[]>(() => [
    {
        title: t('Poranna rutyna'),
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: t('Zadania na dziś'),
        href: '/todays-tasks',
        icon: CalendarCheck,
    },
]);

const configNavItems = computed<NavItem[]>(() => [
    {
        title: t('Bloki rutyny'),
        href: '/morning-hub/routine',
        icon: ListChecks,
    },
    {
        title: t('Źródła zadań'),
        href: '/morning-hub/todays-tasks',
        icon: CalendarCheck,
    },
    {
        title: t('Przewodnik'),
        href: '/morning-hub/guide',
        icon: BookOpen,
    },
]);

const integrationNavItems = computed<NavItem[]>(() => [
    {
        title: 'ClickUp',
        href: '/morning-hub/clickup',
        icon: Plug,
    },
    {
        title: t('Google Calendar'),
        href: '/morning-hub/google-calendar',
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
                        <RouterLink to="/dashboard">
                            <AppLogo />
                        </RouterLink>
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
