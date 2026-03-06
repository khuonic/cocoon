<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { StickyNote, ListTodo, Pin, Trash2, Pencil, Plus, ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
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
import type { Todo, TodoList } from '@/types/todo';
import { mobilePatch } from '@/lib/form-helpers';
import { Switch } from '@/components/ui/switch';
import { show as showNote, togglePin, destroy as destroyNote } from '@/actions/App/Http/Controllers/NoteController';
import { show as showTodoList, store as storeTodoList, update as updateTodoList, destroy as destroyTodoList } from '@/actions/App/Http/Controllers/TodoListController';

type NoteItem = Note & { item_type: 'note' };
type TodoListItem = TodoList & { item_type: 'todo_list' };
type Item = NoteItem | TodoListItem;

const props = defineProps<{
    items: Item[];
}>();

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

// Notes
const showNoteDialog = ref(false);

function handleTogglePin(note: NoteItem): void {
    mobilePatch(togglePin.url(note.id), {}, { preserveScroll: true });
}

function handleDeleteNote(note: NoteItem): void {
    router.delete(destroyNote.url(note.id), { preserveScroll: true });
}

// TodoLists
const showTodoListDialog = ref(false);
const editingTodoList = ref<TodoListItem | undefined>();

const todoListForm = useForm({
    title: '',
    is_personal: false,
});

function openCreateTodoList(): void {
    editingTodoList.value = undefined;
    todoListForm.reset();
    showTodoListDialog.value = true;
}

function openEditTodoList(list: TodoListItem): void {
    editingTodoList.value = list;
    todoListForm.title = list.title;
    todoListForm.is_personal = list.is_personal ?? false;
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

function handleDeleteTodoList(list: TodoListItem): void {
    router.delete(destroyTodoList.url(list.id));
}

// FAB speed dial
const fabOpen = ref(false);

function createNote(): void {
    fabOpen.value = false;
    showNoteDialog.value = true;
}

function createTodoList(): void {
    fabOpen.value = false;
    openCreateTodoList();
}

// Navigation
function openItem(item: Item): void {
    if (item.item_type === 'note') {
        router.visit(showNote.url(item.id));
    } else {
        router.visit(showTodoList.url(item.id));
    }
}

function getPreviewTodos(item: TodoListItem): Todo[] {
    return item.todos?.slice(0, 3) ?? [];
}
</script>

<template>
    <AppLayout title="Notes">
        <Head title="Notes" />

        <div class="p-4 pb-28">
            <div v-if="items.length === 0" class="flex flex-col items-center gap-4 py-16 text-center">
                <StickyNote :size="48" class="text-muted-foreground opacity-40" />
                <div>
                    <p class="font-medium text-foreground">Aucune note ni liste</p>
                    <p class="mt-1 text-sm text-muted-foreground">Crée une note ou une liste via le bouton +</p>
                </div>
            </div>

            <div v-else class="grid grid-cols-2 gap-3">
                <!-- Carte Note -->
                <div
                    v-for="item in items"
                    :key="`${item.item_type}-${item.id}`"
                    class="relative flex flex-col rounded-xl p-3 shadow-sm"
                    :class="item.item_type === 'note' ? getBgClass((item as NoteItem).color) : 'bg-card'"
                >
                    <!-- Contenu cliquable -->
                    <div class="min-w-0 flex-1 cursor-pointer pr-6" @click="openItem(item)">
                        <!-- Note -->
                        <template v-if="item.item_type === 'note'">
                            <div class="flex items-center gap-1.5">
                                <Pin
                                    v-if="(item as NoteItem).is_pinned"
                                    :size="11"
                                    class="shrink-0 fill-foreground text-foreground"
                                />
                                <h3 class="truncate text-sm font-semibold text-foreground">{{ item.title }}</h3>
                            </div>
                            <p
                                v-if="(item as NoteItem).content"
                                class="mt-1 line-clamp-8 text-xs text-muted-foreground"
                            >
                                {{ (item as NoteItem).content }}
                            </p>
                        </template>

                        <!-- TodoList -->
                        <template v-else>
                            <div class="flex items-center gap-1.5">
                                <ListTodo :size="12" class="shrink-0 text-muted-foreground" />
                                <h3 class="truncate text-sm font-semibold text-foreground">{{ item.title }}</h3>
                            </div>
                            <ul class="mt-1.5 space-y-1">
                                <li
                                    v-for="todo in getPreviewTodos(item as TodoListItem)"
                                    :key="todo.id"
                                    class="flex items-center gap-1.5 text-xs"
                                    :class="todo.is_done ? 'text-muted-foreground line-through' : 'text-foreground'"
                                >
                                    <span
                                        class="size-3 shrink-0 rounded-sm border"
                                        :class="todo.is_done ? 'border-muted-foreground bg-muted-foreground/30' : 'border-muted-foreground'"
                                    />
                                    <span class="truncate">{{ todo.title }}</span>
                                </li>
                                <li
                                    v-if="(item as TodoListItem).todos && (item as TodoListItem).todos!.length > 3"
                                    class="flex items-center gap-1 text-xs text-muted-foreground"
                                >
                                    <ChevronRight :size="12" />
                                    +{{ (item as TodoListItem).todos!.length - 3 }} de plus
                                </li>
                            </ul>
                            <p v-if="!(item as TodoListItem).todos?.length" class="mt-1 text-xs text-muted-foreground">
                                Liste vide
                            </p>
                        </template>
                    </div>

                    <!-- Menu ⋮ -->
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <button class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded text-muted-foreground">
                                <span class="text-lg leading-none">⋮</span>
                            </button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <template v-if="item.item_type === 'note'">
                                <DropdownMenuItem @click="handleTogglePin(item as NoteItem)">
                                    <Pin :size="14" class="mr-2" />
                                    {{ (item as NoteItem).is_pinned ? 'Désépingler' : 'Épingler' }}
                                </DropdownMenuItem>
                                <DropdownMenuItem class="text-destructive" @click="handleDeleteNote(item as NoteItem)">
                                    <Trash2 :size="14" class="mr-2" />
                                    Supprimer
                                </DropdownMenuItem>
                            </template>
                            <template v-else>
                                <DropdownMenuItem @click="openEditTodoList(item as TodoListItem)">
                                    <Pencil :size="14" class="mr-2" />
                                    Modifier le titre
                                </DropdownMenuItem>
                                <DropdownMenuItem class="text-destructive" @click="handleDeleteTodoList(item as TodoListItem)">
                                    <Trash2 :size="14" class="mr-2" />
                                    Supprimer
                                </DropdownMenuItem>
                            </template>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>
        </div>

        <!-- FAB speed dial -->
        <div class="fixed z-40 flex flex-col items-end" style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px); right: 1rem;">
            <!-- Options speed dial -->
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="fabOpen" class="mb-3 flex flex-col items-end gap-2">
                    <button
                        class="flex items-center gap-2 rounded-full bg-card px-4 py-2 shadow-lg"
                        @click="createTodoList"
                    >
                        <span class="text-sm font-medium text-foreground">Liste</span>
                        <span class="flex size-8 items-center justify-center rounded-full bg-muted">
                            <ListTodo :size="16" class="text-foreground" />
                        </span>
                    </button>
                    <button
                        class="flex items-center gap-2 rounded-full bg-card px-4 py-2 shadow-lg"
                        @click="createNote"
                    >
                        <span class="text-sm font-medium text-foreground">Note</span>
                        <span class="flex size-8 items-center justify-center rounded-full bg-muted">
                            <StickyNote :size="16" class="text-foreground" />
                        </span>
                    </button>
                </div>
            </Transition>

            <!-- Bouton principal -->
            <button
                class="flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform active:scale-95"
                :class="fabOpen ? 'rotate-45' : ''"
                style="transition: transform 0.2s"
                @click="fabOpen = !fabOpen"
            >
                <Plus :size="26" />
            </button>
        </div>

        <!-- Overlay pour fermer le FAB -->
        <div
            v-if="fabOpen"
            class="fixed inset-0 z-30"
            @click="fabOpen = false"
        />

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

                    <div class="flex items-center justify-between">
                        <Label for="list-personal" class="cursor-pointer">Liste personnelle</Label>
                        <Switch
                            id="list-personal"
                            v-model:checked="todoListForm.is_personal"
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
