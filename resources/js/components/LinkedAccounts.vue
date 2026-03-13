<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { linkRedirect, unlink } from '@/actions/App/Http/Controllers/Auth/GoogleAuthController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();

const page = usePage();
const hasGoogle = computed(() => page.props.auth.hasGoogle);
const hasPassword = computed(() => page.props.auth.hasPassword);
</script>

<template>
    <Separator />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            :title="t('Powiązane konta')"
            :description="t('Zarządzaj zewnętrznymi kontami powiązanymi z Twoim profilem')"
        />

        <div class="flex items-center justify-between rounded-lg border p-4">
            <div class="flex items-center gap-3">
                <svg class="size-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path
                        d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z"
                        fill="#4285F4"
                    />
                    <path
                        d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                        fill="#34A853"
                    />
                    <path
                        d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                        fill="#FBBC05"
                    />
                    <path
                        d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                        fill="#EA4335"
                    />
                </svg>
                <span class="font-medium">Google</span>
                <Badge v-if="hasGoogle" variant="default">{{ t('Powiązane') }}</Badge>
                <Badge v-else variant="secondary">{{ t('Niepowiązane') }}</Badge>
            </div>

            <div>
                <template v-if="hasGoogle">
                    <Link
                        :href="unlink.url()"
                        method="delete"
                        as="button"
                        :disabled="!hasPassword"
                    >
                        <Button
                            variant="outline"
                            size="sm"
                            :disabled="!hasPassword"
                            :title="!hasPassword ? t('Najpierw ustaw hasło') : ''"
                        >
                            {{ t('Odłącz') }}
                        </Button>
                    </Link>
                </template>
                <template v-else>
                    <a :href="linkRedirect.url()">
                        <Button variant="outline" size="sm">
                            {{ t('Połącz') }}
                        </Button>
                    </a>
                </template>
            </div>
        </div>

        <p v-if="hasGoogle && !hasPassword" class="text-sm text-muted-foreground">
            {{ t('Aby odłączyć konto Google, najpierw ustaw hasło w ustawieniach hasła.') }}
        </p>
    </div>
</template>
