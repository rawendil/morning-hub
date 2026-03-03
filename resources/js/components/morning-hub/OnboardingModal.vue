<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, Rocket, Sparkles } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';
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

const STORAGE_KEY = 'morning-hub-onboarded';

const isOpen = ref(false);
const currentStep = ref(0);

onMounted(() => {
    if (!localStorage.getItem(STORAGE_KEY)) {
        isOpen.value = true;
    }
});

const steps = [
    {
        icon: Sparkles,
        title: 'Welcome to Morning Hub',
        description:
            'Your personal morning routine dashboard. Organize tasks, track habits, and stay focused — all before you start your day.',
    },
    {
        icon: LayoutGrid,
        title: 'How it works',
        items: [
            'Configure blocks in Routine settings — timers, tasks, habits, notes.',
            'Connect ClickUp to pull your priority tasks automatically.',
            'Start your timer and work through each block.',
        ],
    },
    {
        icon: Rocket,
        title: 'Ready to start',
        description: 'Set up your first routine or jump straight into the dashboard.',
    },
];

const isLastStep = computed(() => currentStep.value === steps.length - 1);

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
                    v-for="(item, i) in steps[currentStep].items"
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
                    Skip
                </Button>
                <span v-else />

                <div v-if="isLastStep" class="flex gap-2">
                    <Link :href="routineIndex.url()" @click="complete">
                        <Button variant="outline" size="sm">Routine Settings</Button>
                    </Link>
                    <Button size="sm" @click="complete">Start Using Dashboard</Button>
                </div>
                <Button v-else size="sm" @click="next">Next</Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
