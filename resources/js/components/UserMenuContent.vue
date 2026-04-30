<script setup lang="ts">
import { LogOut, Settings } from 'lucide-vue-next';
import { RouterLink, useRouter } from 'vue-router';
import LanguageSwitcher from '@/components/LanguageSwitcher.vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { useTranslations } from '@/composables/useTranslations';
import { useAuthStore } from '@/stores/auth';
import type { User } from '@/types';

defineProps<{ user: User }>();

const { t } = useTranslations();
const router = useRouter();
const auth = useAuthStore();

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <RouterLink
                to="/settings/profile"
                class="block w-full cursor-pointer"
            >
                <Settings class="mr-2 h-4 w-4" />
                {{ t('Ustawienia') }}
            </RouterLink>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <div class="px-2 py-1.5">
        <LanguageSwitcher />
    </div>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <button
            class="block w-full cursor-pointer"
            data-test="logout-button"
            @click="handleLogout"
        >
            <LogOut class="mr-2 h-4 w-4" />
            {{ t('Wyloguj się') }}
        </button>
    </DropdownMenuItem>
</template>
