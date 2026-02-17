<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { mobilePut } from '@/lib/form-helpers';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import RecipeForm from '@/components/meals/RecipeForm.vue';
import { Button } from '@/components/ui/button';
import type { Recipe, TagOption, MealTag } from '@/types/meal';
import { update } from '@/actions/App/Http/Controllers/RecipeController';

const props = defineProps<{
    recipe: Recipe;
    availableTags: TagOption[];
}>();

const form = useForm({
    title: props.recipe.title,
    description: props.recipe.description,
    url: props.recipe.url,
    prep_time: props.recipe.prep_time,
    cook_time: props.recipe.cook_time,
    servings: props.recipe.servings,
    tags: (props.recipe.tags ?? []) as MealTag[],
    ingredients: (props.recipe.ingredients ?? []).map((i) => ({
        name: i.name,
        quantity: i.quantity,
        unit: i.unit,
    })),
    steps: (props.recipe.steps ?? []).map((s) => ({
        instruction: s.instruction,
    })),
});

function submit(): void {
    mobilePut(form, update.url(props.recipe.id));
}

function goBack(): void {
    router.visit(`/recipes/${props.recipe.id}`);
}
</script>

<template>
    <AppLayout title="Modifier la recette">
        <template #header-left>
            <BackButton :href="`/recipes/${recipe.id}`" />
        </template>

        <Head title="Modifier la recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <RecipeForm v-model:form="form" :available-tags="availableTags" />

                <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                    Enregistrer
                </Button>
            </form>
        </div>
    </AppLayout>
</template>
