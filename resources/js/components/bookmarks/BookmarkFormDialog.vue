<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/BookmarkController';
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
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { mobilePut } from '@/lib/form-helpers';
import type { Bookmark, BookmarkCategory, BookmarkCategoryOption } from '@/types/bookmark';

const props = defineProps<{
    bookmark?: Bookmark;
    categories: BookmarkCategoryOption[];
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.bookmark;

const form = useForm({
    url: '',
    title: '',
    description: '' as string | null,
    category: null as BookmarkCategory | null,
    is_favorite: false,
    show_on_dashboard: false,
});

function resetForm(): void {
    if (props.bookmark) {
        form.url = props.bookmark.url;
        form.title = props.bookmark.title;
        form.description = props.bookmark.description ?? '';
        form.category = props.bookmark.category;
        form.is_favorite = props.bookmark.is_favorite;
        form.show_on_dashboard = props.bookmark.show_on_dashboard;
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

watch(() => props.bookmark, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function selectCategory(value: BookmarkCategory): void {
    form.category = form.category === value ? null : value;
}

function submit(): void {
    if (isEditMode() && props.bookmark) {
        mobilePut(form, update.url(props.bookmark.id), {
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
                <DialogTitle>{{ isEditMode() ? 'Modifier le bookmark' : 'Nouveau bookmark' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails du bookmark.' : 'Ajoute un nouveau bookmark.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="bookmark-url">URL</Label>
                    <Input
                        id="bookmark-url"
                        v-model="form.url"
                        type="url"
                        placeholder="https://..."
                        required
                    />
                    <InputError :message="form.errors.url" />
                </div>

                <div class="space-y-2">
                    <Label for="bookmark-title">Titre</Label>
                    <Input
                        id="bookmark-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Ex: Super resto italien"
                        required
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="bookmark-description">Description</Label>
                    <Textarea
                        id="bookmark-description"
                        v-model="form.description"
                        placeholder="Détails optionnels..."
                        rows="2"
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="space-y-2">
                    <Label>Catégorie</Label>
                    <div class="flex flex-wrap gap-2">
                        <Button
                            v-for="cat in categories"
                            :key="cat.value"
                            type="button"
                            size="sm"
                            :variant="form.category === cat.value ? 'default' : 'outline'"
                            @click="selectCategory(cat.value)"
                        >
                            {{ cat.label }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.category" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="bookmark-favorite">Favori</Label>
                    <Switch
                        id="bookmark-favorite"
                        :checked="form.is_favorite"
                        @update:checked="(val: boolean) => form.is_favorite = val"
                    />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="bookmark-dashboard">Afficher sur l'accueil</Label>
                    <Switch
                        id="bookmark-dashboard"
                        :checked="form.show_on_dashboard"
                        @update:checked="(val: boolean) => form.show_on_dashboard = val"
                    />
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
