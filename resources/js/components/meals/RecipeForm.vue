<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import IngredientListInput from '@/components/meals/IngredientListInput.vue';
import StepListInput from '@/components/meals/StepListInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import type { TagOption, MealTag } from '@/types/meal';

defineProps<{
    availableTags: TagOption[];
}>();

const form = defineModel<{
    title: string;
    description: string | null;
    url: string | null;
    prep_time: number | null;
    cook_time: number | null;
    servings: number | null;
    tags: MealTag[];
    ingredients: { name: string; quantity: string | null; unit: string | null }[];
    steps: { instruction: string }[];
    errors: Record<string, string>;
    processing: boolean;
}>('form', { required: true });

function toggleTag(tag: MealTag): void {
    const index = form.value.tags.indexOf(tag);
    if (index === -1) {
        form.value.tags.push(tag);
    } else {
        form.value.tags.splice(index, 1);
    }
}
</script>

<template>
    <div class="space-y-5">
        <div class="space-y-2">
            <Label for="recipe-title">Titre</Label>
            <Input
                id="recipe-title"
                v-model="form.title"
                type="text"
                placeholder="Ex: Risotto aux champignons"
                required
            />
            <InputError :message="form.errors.title" />
        </div>

        <div class="space-y-2">
            <Label for="recipe-description">Description</Label>
            <Textarea
                id="recipe-description"
                v-model="form.description"
                placeholder="Décris la recette..."
                rows="3"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="space-y-2">
            <Label for="recipe-url">Lien</Label>
            <Input
                id="recipe-url"
                v-model="form.url"
                type="url"
                placeholder="https://..."
            />
            <InputError :message="form.errors.url" />
        </div>

        <div class="grid grid-cols-3 gap-3">
            <div class="space-y-2">
                <Label for="recipe-prep-time">Prépa (min)</Label>
                <Input
                    id="recipe-prep-time"
                    v-model.number="form.prep_time"
                    type="number"
                    min="0"
                    placeholder="15"
                />
                <InputError :message="form.errors.prep_time" />
            </div>
            <div class="space-y-2">
                <Label for="recipe-cook-time">Cuisson (min)</Label>
                <Input
                    id="recipe-cook-time"
                    v-model.number="form.cook_time"
                    type="number"
                    min="0"
                    placeholder="30"
                />
                <InputError :message="form.errors.cook_time" />
            </div>
            <div class="space-y-2">
                <Label for="recipe-servings">Portions</Label>
                <Input
                    id="recipe-servings"
                    v-model.number="form.servings"
                    type="number"
                    min="1"
                    placeholder="2"
                />
                <InputError :message="form.errors.servings" />
            </div>
        </div>

        <div class="space-y-2">
            <Label>Tags</Label>
            <div class="flex flex-wrap gap-2">
                <Button
                    v-for="tag in availableTags"
                    :key="tag.value"
                    type="button"
                    size="sm"
                    :variant="form.tags.includes(tag.value) ? 'default' : 'outline'"
                    @click="toggleTag(tag.value)"
                >
                    {{ tag.label }}
                </Button>
            </div>
            <InputError :message="form.errors.tags" />
        </div>

        <div class="space-y-2">
            <Label>Ingrédients</Label>
            <IngredientListInput v-model="form.ingredients" />
            <InputError :message="form.errors['ingredients.0.name']" />
        </div>

        <div class="space-y-2">
            <Label>Étapes</Label>
            <StepListInput v-model="form.steps" />
            <InputError :message="form.errors['steps.0.instruction']" />
        </div>
    </div>
</template>
