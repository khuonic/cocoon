<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Trash2, MoreVertical, ChevronDown } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { mobilePatch } from '@/lib/form-helpers';
import {
    store as storeTodo,
    toggle as toggleTodo,
    destroy as destroyTodo,
} from '@/actions/App/Http/Controllers/TodoController';
import { destroy as destroyTodoList } from '@/actions/App/Http/Controllers/TodoListController';
import type { TodoList, Todo } from '@/types/todo';

const props = defineProps<{
    todoList: TodoList;
}>();

const doneOpen = ref(false);

const pendingTodos = () => props.todoList.todos?.filter((t) => !t.is_done) ?? [];
const doneTodos = () => props.todoList.todos?.filter((t) => t.is_done) ?? [];

const form = useForm({ title: '' });

function submitTodo(): void {
    form.post(storeTodo.url(props.todoList.id), {
        onSuccess: () => form.reset(),
        preserveScroll: true,
    });
}

function handleToggle(todo: Todo): void {
    mobilePatch(toggleTodo.url(todo.id), {}, { preserveScroll: true });
}

function handleDeleteTodo(todo: Todo): void {
    router.delete(destroyTodo.url(todo.id), { preserveScroll: true });
}

function handleDeleteList(): void {
    router.delete(destroyTodoList.url(props.todoList.id));
}
</script>

<template>
    <AppLayout :title="todoList.title">
        <Head :title="todoList.title" />

        <template #header-left>
            <BackButton href="/notes?tab=todos" />
        </template>

        <template #header-right>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-xl">
                        <MoreVertical :size="20" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem class="text-destructive" @click="handleDeleteList">
                        <Trash2 :size="14" class="mr-2" />
                        Supprimer la liste
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <!-- Formulaire d'ajout sticky -->
        <form
            class="sticky top-0 z-10 flex gap-2 border-b border-border bg-background px-4 py-3"
            @submit.prevent="submitTodo"
        >
            <Input
                v-model="form.title"
                placeholder="Ajouter une tâche..."
                class="flex-1"
                :disabled="form.processing"
            />
            <Button type="submit" :disabled="!form.title.trim() || form.processing">
                Ajouter
            </Button>
        </form>

        <div class="space-y-1 p-4">
            <!-- Tâches en cours -->
            <div
                v-for="todo in pendingTodos()"
                :key="todo.id"
                class="flex items-center gap-3 rounded-lg bg-card px-3 py-2.5"
            >
                <button
                    class="flex size-5 shrink-0 items-center justify-center rounded border border-border"
                    @click="handleToggle(todo)"
                />
                <span class="flex-1 text-sm text-foreground">{{ todo.title }}</span>
                <button
                    class="text-muted-foreground"
                    @click="handleDeleteTodo(todo)"
                >
                    <Trash2 :size="14" />
                </button>
            </div>

            <!-- Vide -->
            <p
                v-if="pendingTodos().length === 0 && doneTodos().length === 0"
                class="py-8 text-center text-sm text-muted-foreground"
            >
                Aucune tâche. Ajoutes-en une !
            </p>

            <!-- Terminées -->
            <Collapsible v-if="doneTodos().length > 0" v-model:open="doneOpen" class="mt-2">
                <CollapsibleTrigger class="flex w-full items-center gap-2 rounded-lg px-1 py-2 text-sm text-muted-foreground">
                    <ChevronDown
                        :size="16"
                        class="transition-transform"
                        :class="doneOpen ? 'rotate-0' : '-rotate-90'"
                    />
                    Terminées ({{ doneTodos().length }})
                </CollapsibleTrigger>
                <CollapsibleContent class="space-y-1">
                    <div
                        v-for="todo in doneTodos()"
                        :key="todo.id"
                        class="flex items-center gap-3 rounded-lg bg-card px-3 py-2.5"
                    >
                        <button
                            class="flex size-5 shrink-0 items-center justify-center rounded border border-border bg-primary/10"
                            @click="handleToggle(todo)"
                        >
                            <span class="text-primary" style="font-size: 10px;">✓</span>
                        </button>
                        <span class="flex-1 text-sm text-muted-foreground line-through">{{ todo.title }}</span>
                        <button
                            class="text-muted-foreground"
                            @click="handleDeleteTodo(todo)"
                        >
                            <Trash2 :size="14" />
                        </button>
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>
    </AppLayout>
</template>
