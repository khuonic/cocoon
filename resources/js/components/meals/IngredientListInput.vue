<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus, X } from 'lucide-vue-next';

type Ingredient = {
    name: string;
    quantity: string | null;
    unit: string | null;
};

const ingredients = defineModel<Ingredient[]>({ default: [] });

function add(): void {
    ingredients.value.push({ name: '', quantity: null, unit: null });
}

function remove(index: number): void {
    ingredients.value.splice(index, 1);
}
</script>

<template>
    <div class="space-y-2">
        <div
            v-for="(ingredient, index) in ingredients"
            :key="index"
            class="flex items-center gap-2"
        >
            <Input
                v-model="ingredient.name"
                placeholder="Ingrédient"
                class="flex-1"
                required
            />
            <Input
                v-model="ingredient.quantity"
                placeholder="Qté"
                class="w-16"
            />
            <Input
                v-model="ingredient.unit"
                placeholder="Unité"
                class="w-16"
            />
            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
                @click="remove(index)"
            >
                <X :size="16" />
            </Button>
        </div>
        <Button type="button" variant="outline" size="sm" @click="add">
            <Plus :size="16" class="mr-1" />
            Ajouter un ingrédient
        </Button>
    </div>
</template>
