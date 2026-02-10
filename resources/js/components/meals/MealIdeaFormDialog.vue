<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { mobilePut } from '@/lib/form-helpers';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import type { MealIdea, TagOption, MealTag } from '@/types/meal';
import { store, update } from '@/actions/App/Http/Controllers/MealIdeaController';

const props = defineProps<{
    idea?: MealIdea;
    availableTags: TagOption[];
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.idea;

const form = useForm({
    name: '',
    description: '' as string | null,
    url: '' as string | null,
    tags: [] as MealTag[],
});

function resetForm(): void {
    if (props.idea) {
        form.name = props.idea.name;
        form.description = props.idea.description ?? '';
        form.url = props.idea.url ?? '';
        form.tags = props.idea.tags ?? [];
    } else {
        form.reset();
        form.clearErrors();
    }
}

watch(isOpen, (open) => {
    if (open) {
        resetForm();
    }
});

watch(() => props.idea, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function toggleTag(tag: MealTag): void {
    const index = form.tags.indexOf(tag);
    if (index === -1) {
        form.tags.push(tag);
    } else {
        form.tags.splice(index, 1);
    }
}

function submit(): void {
    if (isEditMode() && props.idea) {
        mobilePut(form, update.url(props.idea.id), {
            preserveScroll: true,
            onSuccess: () => { isOpen.value = false; },
        });
    } else {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => { isOpen.value = false; },
        });
    }
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ isEditMode() ? 'Modifier l\'idée' : 'Nouvelle idée' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails de l\'idée repas.' : 'Ajoute une nouvelle idée repas.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="idea-name">Nom</Label>
                    <Input
                        id="idea-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Ex: Pâtes carbo"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="idea-description">Description</Label>
                    <Textarea
                        id="idea-description"
                        v-model="form.description"
                        placeholder="Détails optionnels..."
                        rows="2"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="space-y-2">
                    <Label for="idea-url">Lien</Label>
                    <Input
                        id="idea-url"
                        v-model="form.url"
                        type="url"
                        placeholder="https://..."
                    />
                    <InputError :message="form.errors.url" />
                </div>

                <div class="space-y-2">
                    <Label>Tags</Label>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="tag in availableTags"
                            :key="tag.value"
                            type="button"
                            size="sm"
                            :variant="form.tags.includes(tag.value) ? 'default' : 'outline'"
                            @click="toggleTag(tag.value)"
                        >
                            {{ tag.label }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.tags" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isOpen = false">
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditMode() ? 'Enregistrer' : 'Ajouter' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
