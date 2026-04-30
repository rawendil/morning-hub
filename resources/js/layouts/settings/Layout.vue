<script setup lang="ts">
import { RouterLink } from 'vue-router';
import { computed } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { useTranslations } from '@/composables/useTranslations';
import type { NavItem } from '@/types';

const { t } = useTranslations();

const sidebarNavItems = computed<NavItem[]>(() => [
    {
        title: t('Profil'),
        href: '/settings/profile',
    },
    {
        title: t('Hasło'),
        href: '/settings/password',
    },
    {
        title: t('Dwuskładnikowe'),
        href: '/settings/two-factor',
    },
    {
        title: t('Wygląd'),
        href: '/settings/appearance',
    },
]);

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <Heading
            :title="t('Ustawienia')"
            :description="t('Zarządzaj ustawieniami profilu i konta')"
        />

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Settings"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <RouterLink :to="item.href">
                            <component :is="item.icon" class="h-4 w-4" />
                            {{ item.title }}
                        </RouterLink>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
