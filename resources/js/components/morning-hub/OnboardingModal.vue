<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Rocket, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
import { useTranslations } from '@/composables/useTranslations';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { index as routineIndex } from '@/routes/morning-hub/routine';

const { t } = useTranslations();

const STORAGE_KEY = 'morning-hub-onboarded';

const isOpen = ref(false);
const currentStep = ref(0);

onMounted(() => {
    if (!localStorage.getItem(STORAGE_KEY)) {
        isOpen.value = true;
    }
});

const steps = computed(() => [
    {
        icon: Sparkles,
        title: t('Witaj w Morning Hub'),
        description: t('Twój osobisty panel porannej rutyny. Organizuj zadania, śledź nawyki i zachowaj skupienie — zanim zaczniesz dzień.'),
    },
    {
        icon: LayoutGrid,
        title: t('Jak to działa'),
        items: [
            t('Skonfiguruj bloki w ustawieniach Rutyny — timery, zadania, nawyki, notatki.'),
            t('Połącz ClickUp, aby automatycznie pobierać priorytetowe zadania.'),
            t('Uruchom timer i pracuj przez kolejne bloki.'),
        ],
    },
    {
        icon: Rocket,
        title: t('Gotowy do startu'),
        description: t('Skonfiguruj pierwszą rutynę lub przejdź od razu do panelu.'),
    },
]);

const isLastStep = computed(() => currentStep.value === steps.value.length - 1);

function next() {
    if (isLastStep.value) {
        complete();
    } else {
        currentStep.value++;
    }
}

function complete() {
    localStorage.setItem(STORAGE_KEY, 'true');
    isOpen.value = false;
}
</script>

<template>
    <Dialog v-model:open="isOpen">
        <DialogContent class="sm:max-w-md" @escape-key-down.prevent @pointer-down-outside.prevent>
            <!-- Step dots -->
            <div class="flex justify-center gap-1.5 pt-2">
                <span
                    v-for="(_, i) in steps"
                    :key="i"
                    class="h-1.5 w-1.5 rounded-full transition-colors"
                    :class="i === currentStep ? 'bg-primary' : 'bg-muted-foreground/30'"
                />
            </div>

            <DialogHeader class="items-center text-center">
                <component
                    :is="steps[currentStep].icon"
                    class="mx-auto mb-2 h-10 w-10 text-primary"
                />
                <DialogTitle>{{ steps[currentStep].title }}</DialogTitle>
                <DialogDescription v-if="steps[currentStep].description">
                    {{ steps[currentStep].description }}
                </DialogDescription>
            </DialogHeader>

            <!-- Step 2: list items -->
            <ul v-if="steps[currentStep].items" class="space-y-3 px-4">

                <li
                    v-for="(item, i) in steps[currentStep].items ?? []"
                    :key="i"
                    class="flex items-start gap-3 text-sm text-muted-foreground"
                >
                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-medium text-primary">
                        {{ i + 1 }}
                    </span>
                    {{ item }}
                </li>
            </ul>

            <DialogFooter class="flex-row justify-between gap-2 sm:justify-between">
                <Button v-if="!isLastStep" variant="ghost" size="sm" @click="complete">
                    {{ t('Pomiń') }}
                </Button>
                <span v-else />

                <div v-if="isLastStep" class="flex gap-2">
                    <Link :href="routineIndex.url()" @click="complete">
                        <Button variant="outline" size="sm">{{ t('Ustawienia rutyny') }}</Button>
                    </Link>
                    <Button size="sm" @click="complete">{{ t('Przejdź do panelu') }}</Button>
                </div>
                <Button v-else size="sm" @click="next">{{ t('Dalej') }}</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
