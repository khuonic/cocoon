<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
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
import { Label } from '@/components/ui/label';
import type { NoteColor } from '@/types/note';
import { store } from '@/actions/App/Http/Controllers/NoteController';

const isOpen = defineModel<boolean>('open');

const form = useForm({
    title: '',
    color: null as NoteColor | null,
});

function submit(): void {
    form.post(store.url(), {
        onSuccess: () => {
            isOpen.value = false;
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Nouvelle note</DialogTitle>
                <DialogDescription>Donne un titre à ta note et choisis une couleur.</DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="note-title">Titre</Label>
                    <Input
                        id="note-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Ex: Idées vacances"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <div class="space-y-2">
                    <Label>Couleur</Label>
                    <ColorPicker v-model="form.color" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="isOpen = false">
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        Créer
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
