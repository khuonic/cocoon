<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2, Cake } from 'lucide-vue-next';
import { destroy } from '@/actions/App/Http/Controllers/BirthdayController';
import { Button } from '@/components/ui/button';
import type { Birthday } from '@/types/birthday';

const props = defineProps<{
    birthday: Birthday;
}>();

const emit = defineEmits<{
    edit: [birthday: Birthday];
}>();

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

function handleDelete(): void {
    router.delete(destroy.url(props.birthday.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex items-center gap-3 rounded-lg border border-border bg-card p-3">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-100">
            <Cake :size="16" class="text-amber-600" />
        </div>
        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', birthday)">
            <h3 class="truncate text-sm font-medium text-foreground">{{ birthday.name }}</h3>
            <p class="text-xs text-muted-foreground">
                {{ formatDate(birthday.date) }} ({{ birthday.age }} ans)
            </p>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
            @click="handleDelete"
        >
            <Trash2 :size="16" />
        </Button>
    </div>
</template>
