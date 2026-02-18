<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Camera, X } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import RecipeForm from '@/components/meals/RecipeForm.vue';
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

function handleImageChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
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

        <Head title="Nouvelle recette" />

        <div class="p-4">
            <form @submit.prevent="submit" class="space-y-5">
                <!-- Image -->
                <div class="space-y-2">
                    <Label>Photo</Label>
                    <div v-if="imagePreview" class="relative">
                        <img
                            :src="imagePreview"
                            class="h-48 w-full rounded-xl object-cover"
                            alt=""
                        />
                        <button
                            type="button"
                            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-white"
                            @click="removeImage"
                        >
                            <X :size="14" />
                        </button>
                    </div>
                    <label
                        v-else
                        class="flex h-32 cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/30 bg-muted/50"
                    >
                        <input
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleImageChange"
                        />
                        <div class="text-center">
                            <Camera :size="24" class="mx-auto mb-1 text-muted-foreground" />
                            <span class="text-sm text-muted-foreground">Ajouter une photo</span>
                        </div>
                    </label>
                </div>

                <RecipeForm v-model:form="form" :available-tags="availableTags" />

                <Button type="submit" class="w-full" size="lg" :disabled="form.processing">
                    Créer la recette
                </Button>
            </form>
        </div>
    </AppLayout>
</template>
