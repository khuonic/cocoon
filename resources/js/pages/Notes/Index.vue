<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { StickyNote, CheckSquare, Pin, Trash2, ListTodo, MoreVertical, Pencil } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import NoteFormDialog from '@/components/notes/NoteFormDialog.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { Note, NoteColor } from '@/types/note';
import type { TodoList } from '@/types/todo';
import { mobilePatch } from '@/lib/form-helpers';
import { show as showNote, togglePin, destroy as destroyNote } from '@/actions/App/Http/Controllers/NoteController';
import { show as showTodoList, store as storeTodoList, update as updateTodoList, destroy as destroyTodoList } from '@/actions/App/Http/Controllers/TodoListController';

const props = defineProps<{
    notes: Note[];
    todoLists: TodoList[];
    tab: string;
}>();

const activeTab = computed(() => props.tab === 'todos' ? 'todos' : 'notes');

function setTab(tab: 'notes' | 'todos'): void {
    router.get('/notes', { tab }, { preserveState: true, replace: true });
}

// Notes
const showNoteDialog = ref(false);

const colorClasses: Record<string, string> = {
    default: 'bg-card',
    yellow: 'bg-yellow-100',
    green: 'bg-green-100',
    blue: 'bg-blue-100',
    pink: 'bg-pink-100',
    purple: 'bg-purple-100',
};

function getBgClass(color: NoteColor | null): string {
    return colorClasses[color ?? 'default'] ?? 'bg-card';
}

function handleTogglePin(note: Note): void {
    mobilePatch(togglePin.url(note.id), {}, { preserveScroll: true });
}

function handleDeleteNote(note: Note): void {
    router.delete(destroyNote.url(note.id), { preserveScroll: true });
}

// TodoLists
const showTodoListDialog = ref(false);
const editingTodoList = ref<TodoList | undefined>();

const todoListForm = useForm({
    title: '',
    is_personal: false,
});

function openCreateTodoList(): void {
    editingTodoList.value = undefined;
    todoListForm.reset();
    showTodoListDialog.value = true;
}

function openEditTodoList(list: TodoList): void {
    editingTodoList.value = list;
    todoListForm.title = list.title;
    showTodoListDialog.value = true;
}

function submitTodoList(): void {
    if (editingTodoList.value) {
        todoListForm.patch(updateTodoList.url(editingTodoList.value.id), {
            onSuccess: () => { showTodoListDialog.value = false; },
        });
    } else {
        todoListForm.post(storeTodoList.url(), {
            onSuccess: () => { showTodoListDialog.value = false; },
        });
    }
}

function handleDeleteTodoList(list: TodoList): void {
    router.delete(destroyTodoList.url(list.id));
}

function openFab(): void {
    if (activeTab.value === 'notes') {
        showNoteDialog.value = true;
    } else {
        openCreateTodoList();
    }
}
</script>

<template>
    <AppLayout title="Notes">
        <Head title="Notes" />

        <!-- Onglets -->
        <div class="flex gap-2 px-4 pt-4">
            <button
                class="flex-1 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeTab === 'notes'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground'"
                @click="setTab('notes')"
            >
                <span class="flex items-center justify-center gap-1.5">
                    <StickyNote :size="14" />
                    Notes
                </span>
            </button>
            <button
                class="flex-1 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                :class="activeTab === 'todos'
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-muted text-muted-foreground'"
                @click="setTab('todos')"
            >
                <span class="flex items-center justify-center gap-1.5">
                    <CheckSquare :size="14" />
                    Todos
                </span>
            </button>
        </div>

        <!-- Onglet Notes -->
        <div v-if="activeTab === 'notes'" class="p-4">
            <EmptyState
                v-if="notes.length === 0"
                title="Aucune note"
                description="Crée une note pour garder une trace de tout."
                :icon="StickyNote"
            >
                <template #action>
                    <Button @click="showNoteDialog = true">Ajouter une note</Button>
                </template>
            </EmptyState>

            <div v-else class="grid grid-cols-2 gap-3">
                <div
                    v-for="note in notes"
                    :key="note.id"
                    class="relative flex flex-col rounded-lg p-3"
                    :class="getBgClass(note.color)"
                >
                    <!-- Contenu cliquable -->
                    <div
                        class="min-w-0 flex-1 cursor-pointer"
                        @click="router.visit(showNote.url(note.id))"
                    >
                        <div class="flex items-center gap-1.5 pr-6">
                            <Pin v-if="note.is_pinned" :size="12" class="shrink-0 fill-foreground text-foreground" />
                            <h3 class="truncate text-sm font-semibold text-foreground">{{ note.title }}</h3>
                        </div>
                        <p v-if="note.content" class="mt-1 line-clamp-3 text-xs text-muted-foreground">{{ note.content }}</p>
                    </div>

                    <!-- Menu ⋮ -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded text-muted-foreground">
                                <MoreVertical :size="14" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="handleTogglePin(note)">
                                <Pin :size="14" class="mr-2" />
                                {{ note.is_pinned ? 'Désépingler' : 'Épingler' }}
                            </DropdownMenuItem>
                            <DropdownMenuItem class="text-destructive" @click="handleDeleteNote(note)">
                                <Trash2 :size="14" class="mr-2" />
                                Supprimer
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <!-- Onglet Todos -->
        <div v-else class="p-4">
            <EmptyState
                v-if="todoLists.length === 0"
                title="Aucune liste"
                description="Crée une liste de tâches pour t'organiser."
                :icon="ListTodo"
            >
                <template #action>
                    <Button @click="openCreateTodoList">Créer une liste</Button>
                </template>
            </EmptyState>

            <div v-else class="space-y-2">
                <div
                    v-for="list in todoLists"
                    :key="list.id"
                    class="flex items-center gap-3 rounded-xl bg-card p-4 shadow-sm"
                >
                    <div
                        class="min-w-0 flex-1 cursor-pointer"
                        @click="router.visit(showTodoList.url(list.id))"
                    >
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-foreground">{{ list.title }}</p>
                            <span
                                v-if="list.is_personal"
                                class="rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                            >
                                Perso
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ list.todos?.filter(t => !t.is_done).length ?? 0 }} tâche(s) restante(s)
                        </p>
                    </div>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="flex h-8 w-8 items-center justify-center rounded text-muted-foreground">
                                <MoreVertical :size="16" />
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem @click="openEditTodoList(list)">
                                <Pencil :size="14" class="mr-2" />
                                Modifier le titre
                            </DropdownMenuItem>
                            <DropdownMenuItem class="text-destructive" @click="handleDeleteTodoList(list)">
                                <Trash2 :size="14" class="mr-2" />
                                Supprimer
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <FloatingActionButton @click="openFab" />

        <!-- Dialog création note -->
        <NoteFormDialog v-model:open="showNoteDialog" />

        <!-- Dialog création/édition TodoList -->
        <Dialog v-model:open="showTodoListDialog">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>{{ editingTodoList ? 'Modifier la liste' : 'Nouvelle liste' }}</DialogTitle>
                    <DialogDescription>
                        {{ editingTodoList ? 'Modifie le titre de la liste.' : 'Crée une nouvelle liste de tâches.' }}
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitTodoList" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="list-title">Titre</Label>
                        <Input
                            id="list-title"
                            v-model="todoListForm.title"
                            type="text"
                            placeholder="Ex: Courses, Travail..."
                            required
                            autofocus
                        />
                    </div>

                    <div v-if="!editingTodoList" class="flex items-center justify-between">
                        <Label for="list-personal">Liste personnelle</Label>
                        <input
                            id="list-personal"
                            type="checkbox"
                            v-model="todoListForm.is_personal"
                            class="h-4 w-4 rounded"
                        />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showTodoListDialog = false">
                            Annuler
                        </Button>
                        <Button type="submit" :disabled="todoListForm.processing">
                            {{ editingTodoList ? 'Enregistrer' : 'Créer' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
