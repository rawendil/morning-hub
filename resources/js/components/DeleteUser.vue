<script setup lang="ts">
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useTemplateRef } from 'vue';
import axiosInstance from '@/lib/axios';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/composables/useTranslations';

const { t } = useTranslations();
const router = useRouter();
const passwordInput = useTemplateRef('passwordInput');

const password = ref('');
const errors = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function submit() {
    isLoading.value = true;
    errors.value = {};
    try {
        await axiosInstance.delete('/settings/profile', { data: { password: password.value } });
        localStorage.removeItem('token');
        router.push('/');
    } catch (error: any) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors;
        }
        passwordInput.value?.$el?.focus();
    } finally {
        isLoading.value = false;
    }
}

function reset() {
    password.value = '';
    errors.value = {};
}
</script>

<template>
    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('Usunięcie konta')"
            :description="t('Usuń swoje konto i wszystkie powiązane dane')"
        />
        <div
            class="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10"
        >
            <div class="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p class="font-medium">{{ t('Uwaga') }}</p>
                <p class="text-sm">
                    {{
                        t('Zachowaj ostrożność, tej operacji nie można cofnąć.')
                    }}
                </p>
            </div>
            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                        >{{ t('Usuń konto') }}</Button
                    >
                </DialogTrigger>
                <DialogContent>
                    <form @submit.prevent="submit" class="space-y-6">
                        <DialogHeader class="space-y-3">
                            <DialogTitle>{{
                                t('Czy na pewno chcesz usunąć swoje konto?')
                            }}</DialogTitle>
                            <DialogDescription>
                                {{
                                    t(
                                        'Po usunięciu konta wszystkie powiązane dane zostaną trwale usunięte. Wprowadź hasło, aby potwierdzić trwałe usunięcie konta.',
                                    )
                                }}
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">{{
                                t('Hasło')
                            }}</Label>
                            <Input
                                id="password"
                                type="password"
                                ref="passwordInput"
                                v-model="password"
                                :placeholder="t('Hasło')"
                            />
                            <InputError :message="errors['password']?.[0]" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    type="button"
                                    @click="reset"
                                >
                                    {{ t('Anuluj') }}
                                </Button>
                            </DialogClose>

                            <Button
                                type="submit"
                                variant="destructive"
                                :disabled="isLoading"
                                data-test="confirm-delete-user-button"
                            >
                                {{ t('Usuń konto') }}
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </div>
    </div>
</template>
