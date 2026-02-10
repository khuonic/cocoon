<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import TodoItem from '@/components/todos/TodoItem.vue';
import TodoFormDialog from '@/components/todos/TodoFormDialog.vue';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { CheckSquare, Plus, ChevronDown } from 'lucide-vue-next';
import type { Todo } from '@/types/todo';
import type { User } from '@/types/auth';

defineProps<{
    sharedTodos: Todo[];
    personalTodos: Todo[];
    doneTodos: Todo[];
    users: User[];
}>();

const dialogOpen = ref(false);
const editingTodo = ref<Todo | undefined>(undefined);
const doneOpen = ref(false);

function openCreate(): void {
    editingTodo.value = undefined;
    dialogOpen.value = true;
}

function openEdit(todo: Todo): void {
    editingTodo.value = todo;
    dialogOpen.value = true;
}
</script>

<template>
    <AppLayout title="Tâches">
        <template #header-right>
            <Button variant="ghost" size="icon" @click="openCreate">
                <Plus :size="22" />
            </Button>
        </template>

        <Head title="Tâches" />

        <div class="space-y-6 p-4">
            <!-- Empty state global -->
            <EmptyState
                v-if="sharedTodos.length === 0 && personalTodos.length === 0 && doneTodos.length === 0"
                title="Aucune tâche"
                description="Ajoute des tâches pour organiser votre quotidien à deux."
                :icon="CheckSquare"
            >
                <template #action>
                    <Button @click="openCreate">Ajouter une tâche</Button>
                </template>
            </EmptyState>

            <template v-else>
                <!-- Tâches partagées -->
                <section>
                    <h2 class="mb-2 text-sm font-medium text-muted-foreground">Tâches partagées</h2>
                    <div v-if="sharedTodos.length > 0" class="space-y-1">
                        <TodoItem
                            v-for="todo in sharedTodos"
                            :key="todo.id"
                            :todo="todo"
                            @edit="openEdit"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground/60">Aucune tâche partagée</p>
                </section>

                <!-- Mes tâches -->
                <section>
                    <h2 class="mb-2 text-sm font-medium text-muted-foreground">Mes tâches</h2>
                    <div v-if="personalTodos.length > 0" class="space-y-1">
                        <TodoItem
                            v-for="todo in personalTodos"
                            :key="todo.id"
                            :todo="todo"
                            @edit="openEdit"
                        />
                    </div>
                    <p v-else class="text-sm text-muted-foreground/60">Aucune tâche personnelle</p>
                </section>

                <!-- Terminées -->
                <Collapsible v-if="doneTodos.length > 0" v-model:open="doneOpen">
                    <CollapsibleTrigger class="flex w-full items-center gap-2 rounded-lg px-1 py-2 text-sm text-muted-foreground">
                        <ChevronDown
                            :size="16"
                            class="transition-transform"
                            :class="doneOpen ? 'rotate-0' : '-rotate-90'"
                        />
                        Terminées ({{ doneTodos.length }})
                    </CollapsibleTrigger>
                    <CollapsibleContent class="space-y-1">
                        <TodoItem
                            v-for="todo in doneTodos"
                            :key="todo.id"
                            :todo="todo"
                            @edit="openEdit"
                        />
                    </CollapsibleContent>
                </Collapsible>
            </template>
        </div>

        <TodoFormDialog
            v-model:open="dialogOpen"
            :todo="editingTodo"
            :users="users"
        />
    </AppLayout>
</template>
