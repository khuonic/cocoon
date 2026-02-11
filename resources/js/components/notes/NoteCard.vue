<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { mobilePatch } from '@/lib/form-helpers';
import { Button } from '@/components/ui/button';
import { Pin, Trash2 } from 'lucide-vue-next';
import type { Note, NoteColor } from '@/types/note';
import { togglePin, destroy } from '@/actions/App/Http/Controllers/NoteController';

const props = defineProps<{
    note: Note;
}>();

const emit = defineEmits<{
    edit: [note: Note];
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

function handleTogglePin(): void {
    mobilePatch(togglePin.url(props.note.id), {}, {
        preserveScroll: true,
    });
}

function handleDelete(): void {
    router.delete(destroy.url(props.note.id), {
        preserveScroll: true,
    });
}

function formatRelativeDate(dateStr: string): string {
    const date = new Date(dateStr);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return "À l'instant";
    if (diffMins < 60) return `Il y a ${diffMins} min`;
    if (diffHours < 24) return `Il y a ${diffHours}h`;
    if (diffDays < 7) return `Il y a ${diffDays}j`;

    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
}
</script>

<template>
    <div
        class="flex flex-col rounded-lg p-3"
        :class="getBgClass(note.color)"
    >
        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', note)">
            <div class="flex items-center gap-1.5">
                <Pin v-if="note.is_pinned" :size="14" class="shrink-0 fill-foreground text-foreground" />
                <h3 class="truncate text-sm font-semibold text-foreground">{{ note.title }}</h3>
            </div>
            <p class="mt-1 line-clamp-3 text-xs text-muted-foreground">{{ note.content }}</p>
        </div>

        <div class="mt-2 flex items-center justify-between">
            <span class="text-[10px] text-muted-foreground">
                {{ note.creator?.name }} &middot; {{ formatRelativeDate(note.updated_at) }}
            </span>
            <div class="flex gap-0.5">
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6"
                    @click="handleTogglePin"
                >
                    <Pin
                        :size="12"
                        :class="note.is_pinned ? 'fill-foreground text-foreground' : 'text-muted-foreground'"
                    />
                </Button>
                <Button
                    variant="ghost"
                    size="icon"
                    class="h-6 w-6 text-muted-foreground"
                    @click="handleDelete"
                >
                    <Trash2 :size="12" />
                </Button>
            </div>
        </div>
    </div>
</template>