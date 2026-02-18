<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ChefHat, Clock } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import TagBadges from '@/components/meals/TagBadges.vue';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/vue3';
import type { Recipe } from '@/types/meal';
import { create, show } from '@/actions/App/Http/Controllers/RecipeController';

defineProps<{
    recipes: Recipe[];
}>();
</script>

<template>
    <AppLayout title="Recettes">
        <Head title="Recettes" />

        <EmptyState
            v-if="recipes.length === 0"
            title="Aucune recette"
            description="Ajoute ta première recette pour commencer."
            :icon="ChefHat"
        >
            <template #action>
                <Button as-child>
                    <Link :href="create()">Ajouter une recette</Link>
                </Button>
            </template>
        </EmptyState>

        <div v-else class="grid grid-cols-2 gap-3 p-4">
            <div
                v-for="recipe in recipes"
                :key="recipe.id"
                class="cursor-pointer overflow-hidden rounded-xl bg-card shadow-sm active:opacity-80"
                @click="router.visit(show.url(recipe.id))"
            >
                <img
                    v-if="recipe.image_path"
                    :src="`/storage/${recipe.image_path}`"
                    class="h-32 w-full object-cover"
                    alt=""
                />
                <div
                    v-else
                    class="flex h-32 w-full items-center justify-center bg-muted"
                >
                    <ChefHat :size="32" class="text-muted-foreground" />
                </div>

                <div class="p-3">
                    <p class="truncate text-sm font-medium text-foreground">
                        {{ recipe.title }}
                    </p>
                    <div
                        v-if="recipe.prep_time || recipe.cook_time"
                        class="mt-1 flex items-center gap-1 text-xs text-muted-foreground"
                    >
                        <Clock :size="12" />
                        <span>{{ (recipe.prep_time ?? 0) + (recipe.cook_time ?? 0) }} min</span>
                    </div>
                    <div v-if="recipe.tags?.length" class="mt-2">
                        <TagBadges :tags="recipe.tags" />
                    </div>
                </div>
            </div>
        </div>

        <FloatingActionButton @click="router.visit(create())" />
    </AppLayout>
</template>
