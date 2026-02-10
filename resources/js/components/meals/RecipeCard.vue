<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import TagBadges from '@/components/meals/TagBadges.vue';
import { Clock, Users } from 'lucide-vue-next';
import type { Recipe } from '@/types/meal';
import { show } from '@/actions/App/Http/Controllers/RecipeController';

defineProps<{
    recipe: Recipe;
}>();

function totalTime(recipe: Recipe): number | null {
    if (recipe.prep_time == null && recipe.cook_time == null) return null;
    return (recipe.prep_time ?? 0) + (recipe.cook_time ?? 0);
}
</script>

<template>
    <Link :href="show.url(recipe.id)" class="block rounded-lg border border-border bg-card p-3 active:bg-accent/50">
        <h3 class="truncate text-sm font-medium text-foreground">{{ recipe.title }}</h3>
        <p v-if="recipe.description" class="mt-0.5 truncate text-xs text-muted-foreground">
            {{ recipe.description }}
        </p>
        <div class="mt-2 flex items-center gap-3 text-xs text-muted-foreground">
            <span v-if="totalTime(recipe)" class="flex items-center gap-1">
                <Clock :size="12" />
                {{ totalTime(recipe) }} min
            </span>
            <span v-if="recipe.servings" class="flex items-center gap-1">
                <Users :size="12" />
                {{ recipe.servings }} pers.
            </span>
        </div>
        <div class="mt-1.5">
            <TagBadges :tags="recipe.tags" />
        </div>
    </Link>
</template>
