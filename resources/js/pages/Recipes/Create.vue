<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
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
</script>

<template>
    <AppLayout title="Nouvelle recette">
        <template #header-left>
            <BackButton href="/meal-plans" />
        </template>

        <Head title="Nouvelle recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <RecipeForm v-model:form="form" :available-tags="availableTags" />

                <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                    Créer la recette
                </Button>
            </form>
        </div>
    </AppLayout>
</template>
