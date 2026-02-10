<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Plus, X } from 'lucide-vue-next';

type Step = {
    instruction: string;
};

const steps = defineModel<Step[]>({ default: [] });

function add(): void {
    steps.value.push({ instruction: '' });
}

function remove(index: number): void {
    steps.value.splice(index, 1);
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(step, index) in steps"
            :key="index"
            class="flex items-start gap-2"
        >
            <span class="mt-2 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-muted text-xs font-medium text-muted-foreground">
                {{ index + 1 }}
            </span>
            <Textarea
                v-model="step.instruction"
                placeholder="Décris cette étape..."
                rows="2"
                class="flex-1"
                required
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="mt-1 h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
                @click="remove(index)"
            >
                <X :size="16" />
            </Button>
        </div>
        <Button type="button" variant="outline" size="sm" @click="add">
            <Plus :size="16" class="mr-1" />
            Ajouter une étape
        </Button>
    </div>
</template>
