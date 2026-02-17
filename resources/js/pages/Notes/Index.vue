<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import NoteCard from '@/components/notes/NoteCard.vue';
import NoteFormDialog from '@/components/notes/NoteFormDialog.vue';
import { Button } from '@/components/ui/button';
import { StickyNote } from 'lucide-vue-next';
import type { Note } from '@/types/note';

const props = defineProps<{
    notes: Note[];
}>();

const showDialog = ref(false);
const editingNote = ref<Note | undefined>();

function openCreate(): void {
    editingNote.value = undefined;
    showDialog.value = true;
}

function openEdit(note: Note): void {
    editingNote.value = note;
    showDialog.value = true;
}
</script>

<template>
    <Head title="Notes" />

    <AppLayout title="Notes">
        <div class="p-4">
            <EmptyState
                v-if="props.notes.length === 0"
                title="Aucune note"
                description="Crée des notes partagées pour garder une trace de tout."
                :icon="StickyNote"
            >
                <template #action>
                    <Button @click="openCreate">Ajouter une note</Button>
                </template>
            </EmptyState>

            <div v-else class="grid grid-cols-2 gap-3">
                <NoteCard
                    v-for="note in props.notes"
                    :key="note.id"
                    :note="note"
                    @edit="openEdit"
                />
            </div>
        </div>

        <FloatingActionButton @click="openCreate" />

        <NoteFormDialog
            v-model:open="showDialog"
            :note="editingNote"
        />
    </AppLayout>
</template>
