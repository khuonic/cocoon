<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { mobilePut } from '@/lib/form-helpers';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import RecipeForm from '@/components/meals/RecipeForm.vue';
import ImagePickerButton from '@/components/meals/ImagePickerButton.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
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
    image: null as File | null,
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

const imagePreview = ref<string | null>(
    props.recipe.image_path ? `/storage/${props.recipe.image_path}` : null,
);

function handleImageChange(file: File): void {
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
}

function removeImage(): void {
    form.image = null;
    imagePreview.value = null;
}

function submit(): void {
    mobilePut(form, update.url(props.recipe.id));
}
</script>

<template>
    <AppLayout title="Modifier la recette">
        <template #header-left>
            <BackButton :href="`/recipes/${recipe.id}`" />
        </template>

        <template #header-right>
            <Button size="sm" :disabled="form.processing" @click="submit">
                Enregistrer
            </Button>
        </template>

        <Head title="Modifier la recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Image -->
                <div class="space-y-2">
                    <Label>Photo</Label>
                    <ImagePickerButton
                        :preview="imagePreview"
                        label="Changer la photo"
                        @change="handleImageChange"
                        @remove="removeImage"
                    />
                </div>

                <RecipeForm v-model:form="form" :available-tags="availableTags" />

            </form>
        </div>
    </AppLayout>
</template>
