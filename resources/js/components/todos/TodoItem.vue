<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Trash2, Calendar } from 'lucide-vue-next';
import type { Todo } from '@/types/todo';
import { toggle, destroy } from '@/actions/App/Http/Controllers/TodoController';

const props = defineProps<{
    todo: Todo;
}>();

const emit = defineEmits<{
    edit: [todo: Todo];
}>();

function handleToggle(): void {
    router.patch(toggle.url(props.todo.id), {}, {
        preserveScroll: true,
    });
}

function handleDelete(): void {
    router.delete(destroy.url(props.todo.id), {
        preserveScroll: true,
    });
}

function formatDate(dateStr: string): string {
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);

    if (date.getTime() === today.getTime()) return "Aujourd'hui";
    if (date.getTime() === tomorrow.getTime()) return 'Demain';

    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}

function isOverdue(dateStr: string): boolean {
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return date < today;
}
</script>

<template>
    <div
        class="flex items-center gap-3 rounded-lg bg-card px-3 py-2.5"
        :class="todo.is_done ? 'opacity-50' : ''"
    >
        <Checkbox
            :checked="todo.is_done"
            @update:checked="handleToggle"
        />

        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', todo)">
            <div class="flex items-center gap-2">
                <span
                    class="text-sm"
                    :class="todo.is_done ? 'text-muted-foreground line-through' : 'text-foreground'"
                >
                    {{ todo.title }}
                </span>
                <Badge v-if="todo.assignee" variant="secondary" class="shrink-0 text-xs">
                    {{ todo.assignee.name }}
                </Badge>
            </div>
            <div v-if="todo.due_date && !todo.is_done" class="mt-0.5 flex items-center gap-1">
                <Calendar :size="12" :class="isOverdue(todo.due_date) ? 'text-destructive' : 'text-muted-foreground'" />
                <span
                    class="text-xs"
                    :class="isOverdue(todo.due_date) ? 'text-destructive font-medium' : 'text-muted-foreground'"
                >
                    {{ formatDate(todo.due_date) }}
                </span>
            </div>
        </div>

        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 text-muted-foreground"
            @click="handleDelete"
        >
            <Trash2 :size="16" />
        </Button>
    </div>
</template>
