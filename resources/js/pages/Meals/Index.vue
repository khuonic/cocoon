<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import TagFilter from '@/components/meals/TagFilter.vue';
import MealIdeaCard from '@/components/meals/MealIdeaCard.vue';
import MealIdeaFormDialog from '@/components/meals/MealIdeaFormDialog.vue';
import RecipeCard from '@/components/meals/RecipeCard.vue';
import { Button } from '@/components/ui/button';
import { Lightbulb, BookOpen, Plus } from 'lucide-vue-next';
import type { MealIdea, Recipe, TagOption, MealTag } from '@/types/meal';
import { create } from '@/actions/App/Http/Controllers/RecipeController';

const props = defineProps<{
    ideas: MealIdea[];
    recipes: Recipe[];
    availableTags: TagOption[];
}>();

const activeTab = ref<'ideas' | 'recipes'>('ideas');
const selectedTags = ref<MealTag[]>([]);
const dialogOpen = ref(false);
const editingIdea = ref<MealIdea | undefined>(undefined);

const filteredIdeas = computed(() => {
    if (selectedTags.value.length === 0) return props.ideas;
    return props.ideas.filter((idea) =>
        idea.tags && selectedTags.value.some((tag) => idea.tags!.includes(tag))
    );
});

const filteredRecipes = computed(() => {
    if (selectedTags.value.length === 0) return props.recipes;
    return props.recipes.filter((recipe) =>
        recipe.tags && selectedTags.value.some((tag) => recipe.tags!.includes(tag))
    );
});

function openCreate(): void {
    editingIdea.value = undefined;
    dialogOpen.value = true;
}

function openEdit(idea: MealIdea): void {
    editingIdea.value = idea;
    dialogOpen.value = true;
}
</script>

<template>
    <AppLayout title="Repas">
        <template #header-right>
            <Button v-if="activeTab === 'ideas'" variant="ghost" size="icon" @click="openCreate">
                <Plus :size="22" />
            </Button>
            <Link v-else :href="create.url()">
                <Button variant="ghost" size="icon">
                    <Plus :size="22" />
                </Button>
            </Link>
        </template>

        <Head title="Repas" />

        <div class="space-y-4 p-4">
            <!-- Tab switcher -->
            <div class="flex rounded-lg bg-muted p-1">
                <button
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'ideas' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="activeTab = 'ideas'"
                >
                    <Lightbulb :size="16" />
                    Idées
                </button>
                <button
                    class="flex flex-1 items-center justify-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="activeTab === 'recipes' ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground'"
                    @click="activeTab = 'recipes'"
                >
                    <BookOpen :size="16" />
                    Recettes
                </button>
            </div>

            <!-- Tag filter -->
            <TagFilter
                :available-tags="availableTags"
                v-model:selected-tags="selectedTags"
            />

            <!-- Ideas tab -->
            <template v-if="activeTab === 'ideas'">
                <div v-if="filteredIdeas.length > 0" class="space-y-2">
                    <MealIdeaCard
                        v-for="idea in filteredIdeas"
                        :key="idea.id"
                        :idea="idea"
                        @edit="openEdit"
                    />
                </div>
                <EmptyState
                    v-else
                    title="Aucune idée repas"
                    description="Ajoute des idées pour ne plus se poser la question !"
                    :icon="Lightbulb"
                >
                    <template #action>
                        <Button @click="openCreate">Ajouter une idée</Button>
                    </template>
                </EmptyState>
            </template>

            <!-- Recipes tab -->
            <template v-if="activeTab === 'recipes'">
                <div v-if="filteredRecipes.length > 0" class="space-y-2">
                    <RecipeCard
                        v-for="recipe in filteredRecipes"
                        :key="recipe.id"
                        :recipe="recipe"
                    />
                </div>
                <EmptyState
                    v-else
                    title="Aucune recette"
                    description="Ajoute tes recettes préférées pour les retrouver facilement."
                    :icon="BookOpen"
                >
                    <template #action>
                        <Link :href="create.url()">
                            <Button>Ajouter une recette</Button>
                        </Link>
                    </template>
                </EmptyState>
            </template>
        </div>

        <MealIdeaFormDialog
            v-model:open="dialogOpen"
            :idea="editingIdea"
            :available-tags="availableTags"
        />
    </AppLayout>
</template>
