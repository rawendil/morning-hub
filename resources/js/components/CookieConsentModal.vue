<script setup lang="ts">
import { Cookie } from 'lucide-vue-next';
import { onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useCookieConsent } from '@/composables/useCookieConsent';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const { showModal, init, acceptConsent, rejectConsent } = useCookieConsent();

onMounted(() => {
    init();
});
</script>

<template>
    <Dialog v-model:open="showModal">
        <DialogContent
            class="sm:max-w-md"
            :show-close-button="false"
            @escape-key-down.prevent
            @pointer-down-outside.prevent
        >
            <DialogHeader class="items-center text-center">
                <Cookie class="mx-auto mb-2 size-10 text-primary" />
                <DialogTitle>{{ t('Pliki cookies') }}</DialogTitle>
                <DialogDescription>
                    {{
                        t(
                            'Używamy plików cookies do analizy ruchu na stronie (Google Analytics). Dane są anonimowe i pomagają nam ulepszać aplikację. Możesz zmienić swoją decyzję w każdej chwili.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="flex-row justify-end gap-2 sm:justify-end">
                <Button variant="outline" size="sm" @click="rejectConsent">
                    {{ t('Odrzuć') }}
                </Button>
                <Button size="sm" @click="acceptConsent">
                    {{ t('Akceptuj') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
