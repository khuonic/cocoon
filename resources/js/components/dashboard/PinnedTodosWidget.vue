<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckSquare } from 'lucide-vue-next';
import { toggle } from '@/actions/App/Http/Controllers/TodoController';
import { mobilePatch } from '@/lib/form-helpers';
import type { Todo } from '@/types/todo';

defineProps<{
    todos: Todo[];
}>();

function handleToggle(todo: Todo): void {
    mobilePatch(toggle.url(todo.id), {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <div v-if="todos.length > 0" class="rounded-xl bg-card p-4 shadow-sm">
        <Link href="/todos" class="flex items-center gap-2 text-xs font-medium text-emerald-600">
            <CheckSquare :size="14" />
            A faire
        </Link>
        <div class="mt-2 space-y-1.5">
            <div
                v-for="todo in todos"
                :key="todo.id"
                class="flex items-center gap-2"
            >
                <button
                    class="flex size-5 shrink-0 items-center justify-center rounded border border-border"
                    @click="handleToggle(todo)"
                />
                <span class="text-sm text-foreground">{{ todo.title }}</span>
                <span v-if="todo.assignee" class="text-[10px] text-muted-foreground">
                    {{ todo.assignee.name }}
                </span>
            </div>
        </div>
    </div>
</template>
