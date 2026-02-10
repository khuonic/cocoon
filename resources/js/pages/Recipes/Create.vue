<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import RecipeForm from '@/components/meals/RecipeForm.vue';
import { Button } from '@/components/ui/button';
import type { TagOption, MealTag } from '@/types/meal';
import { store } from '@/actions/App/Http/Controllers/RecipeController';

defineProps<{
    availableTags: TagOption[];
}>();

const form = useForm({
    title: '',
    description: null as string | null,
    url: null as string | null,
    prep_time: null as number | null,
    cook_time: null as number | null,
    servings: null as number | null,
    tags: [] as MealTag[],
    ingredients: [] as { name: string; quantity: string | null; unit: string | null }[],
    steps: [] as { instruction: string }[],
});

function submit(): void {
    form.post(store.url());
}

function goBack(): void {
    router.visit('/meal-plans');
}
</script>

<template>
    <AppLayout title="Nouvelle recette">
        <Head title="Nouvelle recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <RecipeForm v-model:form="form" :available-tags="availableTags" />

                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" @click="goBack">
                        Annuler
                    </Button>
                    <Button type="submit" class="flex-1" :disabled="form.processing">
                        Créer la recette
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
