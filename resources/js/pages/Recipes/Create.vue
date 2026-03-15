<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import RecipeForm from '@/components/meals/RecipeForm.vue';
import ImagePickerButton from '@/components/meals/ImagePickerButton.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { TagOption, MealTag } from '@/types/meal';
import { store } from '@/actions/App/Http/Controllers/RecipeController';

defineProps<{
    availableTags: TagOption[];
}>();

const form = useForm({
    title: '',
    description: null as string | null,
    url: null as string | null,
    image: null as File | null,
    prep_time: null as number | null,
    cook_time: null as number | null,
    servings: null as number | null,
    tags: [] as MealTag[],
    ingredients: [] as { name: string; quantity: string | null; unit: string | null }[],
    steps: [] as { instruction: string }[],
});

const imagePreview = ref<string | null>(null);

function handleImageChange(file: File): void {
    form.image = file;
    imagePreview.value = URL.createObjectURL(file);
}

function removeImage(): void {
    form.image = null;
    imagePreview.value = null;
}

function submit(): void {
    form.post(store.url());
}
</script>

<template>
    <AppLayout title="Nouvelle recette">
        <template #header-left>
            <BackButton href="/recipes" />
        </template>

        <template #header-right>
            <Button size="sm" :disabled="form.processing" @click="submit">
                Créer
            </Button>
        </template>

        <Head title="Nouvelle recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Image -->
                <div class="space-y-2">
                    <Label>Photo</Label>
                    <ImagePickerButton
                        :preview="imagePreview"
                        label="Ajouter une photo"
                        @change="handleImageChange"
                        @remove="removeImage"
                    />
                </div>

                <RecipeForm v-model:form="form" :available-tags="availableTags" />

            </form>
        </div>
    </AppLayout>
</template>
