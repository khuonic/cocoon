<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { mobilePut } from '@/lib/form-helpers';
import InputError from '@/components/InputError.vue';
import ColorPicker from '@/components/notes/ColorPicker.vue';
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
import { Switch } from '@/components/ui/switch';
import type { Note, NoteColor } from '@/types/note';
import { store, update } from '@/actions/App/Http/Controllers/NoteController';

const props = defineProps<{
    note?: Note;
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.note;

const form = useForm({
    title: '',
    content: '',
    is_pinned: false,
    color: null as NoteColor | null,
});

function resetForm(): void {
    if (props.note) {
        form.title = props.note.title;
        form.content = props.note.content;
        form.is_pinned = props.note.is_pinned;
        form.color = props.note.color;
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

watch(() => props.note, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function submit(): void {
    if (isEditMode() && props.note) {
        mobilePut(form, update.url(props.note.id), {
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
                <DialogTitle>{{ isEditMode() ? 'Modifier la note' : 'Nouvelle note' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails de la note.' : 'Ajoute une nouvelle note.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="note-title">Titre</Label>
                    <Input
                        id="note-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Ex: Liste des idées vacances"
                        required
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label for="note-content">Contenu</Label>
                    <Textarea
                        id="note-content"
                        v-model="form.content"
                        placeholder="Écris ta note ici..."
                        rows="6"
                        required
                    />
                    <InputError :message="form.errors.content" />
                </div>

                <div class="space-y-2">
                    <Label>Couleur</Label>
                    <ColorPicker v-model="form.color" />
                    <InputError :message="form.errors.color" />
                </div>

                <div class="flex items-center justify-between">
                    <Label for="note-pinned">Épingler</Label>
                    <Switch
                        id="note-pinned"
                        :checked="form.is_pinned"
                        @update:checked="(val: boolean) => form.is_pinned = val"
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