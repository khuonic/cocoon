<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import TagBadges from '@/components/meals/TagBadges.vue';
import { Button } from '@/components/ui/button';
import { Pencil, Clock, Users, ExternalLink, Trash2 } from 'lucide-vue-next';
import type { Recipe } from '@/types/meal';
import { edit, destroy } from '@/actions/App/Http/Controllers/RecipeController';

const props = defineProps<{
    recipe: Recipe;
}>();

function totalTime(): number | null {
    if (props.recipe.prep_time == null && props.recipe.cook_time == null) return null;
    return (props.recipe.prep_time ?? 0) + (props.recipe.cook_time ?? 0);
}

function handleDelete(): void {
    router.delete(destroy.url(props.recipe.id));
}
</script>

<template>
    <AppLayout :title="recipe.title">
        <template #header-right>
            <Link :href="edit.url(recipe.id)">
                <Button variant="ghost" size="icon">
                    <Pencil :size="18" />
                </Button>
            </Link>
        </template>

        <Head :title="recipe.title" />

        <div class="space-y-6 p-4">
            <!-- Meta -->
            <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                <span v-if="recipe.prep_time" class="flex items-center gap-1">
                    <Clock :size="14" />
                    Prépa {{ recipe.prep_time }} min
                </span>
                <span v-if="recipe.cook_time" class="flex items-center gap-1">
                    <Clock :size="14" />
                    Cuisson {{ recipe.cook_time }} min
                </span>
                <span v-if="totalTime()" class="font-medium text-foreground">
                    Total {{ totalTime() }} min
                </span>
                <span v-if="recipe.servings" class="flex items-center gap-1">
                    <Users :size="14" />
                    {{ recipe.servings }} portions
                </span>
            </div>

            <!-- Description -->
            <p v-if="recipe.description" class="text-sm text-foreground whitespace-pre-line">
                {{ recipe.description }}
            </p>

            <!-- URL -->
            <a
                v-if="recipe.url"
                :href="recipe.url"
                target="_blank"
                class="inline-flex items-center gap-1 text-sm text-primary hover:underline"
            >
                <ExternalLink :size="14" />
                Voir la source
            </a>

            <!-- Tags -->
            <TagBadges :tags="recipe.tags" />

            <!-- Ingredients -->
            <section v-if="recipe.ingredients && recipe.ingredients.length > 0">
                <h2 class="mb-2 text-sm font-semibold text-foreground">Ingrédients</h2>
                <ul class="space-y-1">
                    <li
                        v-for="ingredient in recipe.ingredients"
                        :key="ingredient.id"
                        class="flex items-baseline gap-2 text-sm"
                    >
                        <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-primary" />
                        <span>
                            <span v-if="ingredient.quantity" class="font-medium">{{ ingredient.quantity }}</span>
                            <span v-if="ingredient.unit" class="text-muted-foreground"> {{ ingredient.unit }}</span>
                            {{ ingredient.name }}
                        </span>
                    </li>
                </ul>
            </section>

            <!-- Steps -->
            <section v-if="recipe.steps && recipe.steps.length > 0">
                <h2 class="mb-2 text-sm font-semibold text-foreground">Étapes</h2>
                <ol class="space-y-3">
                    <li
                        v-for="(step, index) in recipe.steps"
                        :key="step.id"
                        class="flex gap-3 text-sm"
                    >
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground">
                            {{ index + 1 }}
                        </span>
                        <p class="pt-0.5 whitespace-pre-line">{{ step.instruction }}</p>
                    </li>
                </ol>
            </section>

            <!-- Delete -->
            <div class="border-t border-border pt-4">
                <Button variant="destructive" class="w-full" @click="handleDelete">
                    <Trash2 :size="16" class="mr-2" />
                    Supprimer la recette
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
