<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout
        title="Weryfikacja e-mail"
        description="Zweryfikuj swój adres e-mail, klikając w link, który wysłaliśmy na Twój adres."
    >
        <Head title="Weryfikacja e-mail" />

        <div
            v-if="status === 'verification-link-sent'"
            class="mb-4 text-center text-sm font-medium text-green-600"
        >
            Nowy link weryfikacyjny został wysłany na adres e-mail podany
            podczas rejestracji.
        </div>

        <Form
            v-bind="send.form()"
            class="space-y-6 text-center"
            v-slot="{ processing }"
        >
            <Button :disabled="processing" variant="secondary">
                <Spinner v-if="processing" />
                Wyślij ponownie e-mail weryfikacyjny
            </Button>

            <TextLink
                :href="logout()"
                as="button"
                class="mx-auto block text-sm"
            >
                Wyloguj się
            </TextLink>
        </Form>
    </AuthLayout>
</template>
