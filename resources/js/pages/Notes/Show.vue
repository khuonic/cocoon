<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, onUnmounted } from 'vue';
import { Pin, Trash2, ArrowLeft, MoreVertical } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { mobilePatch } from '@/lib/form-helpers';
import {
    update as updateNote,
    togglePin,
    destroy as destroyNote,
} from '@/actions/App/Http/Controllers/NoteController';
import type { Note, NoteColor } from '@/types/note';

const props = defineProps<{
    note: Note;
}>();

const title = ref(props.note.title);
const content = ref(props.note.content ?? '');

const colorClasses: Record<string, string> = {
    default: '',
    yellow: 'bg-yellow-50',
    green: 'bg-green-50',
    blue: 'bg-blue-50',
    pink: 'bg-pink-50',
    purple: 'bg-purple-50',
};

function getBgClass(color: NoteColor | null): string {
    return colorClasses[color ?? 'default'] ?? '';
}

let saveTimer: ReturnType<typeof setTimeout> | null = null;

function scheduleSave(): void {
    if (saveTimer) {
        clearTimeout(saveTimer);
    }
    saveTimer = setTimeout(() => {
        mobilePatch(updateNote.url(props.note.id), {
            title: title.value,
            content: content.value,
        }, { preserveScroll: true });
    }, 1000);
}

onUnmounted(() => {
    if (saveTimer) {
        clearTimeout(saveTimer);
    }
});

function autoResize(event: Event): void {
    const textarea = event.target as HTMLTextAreaElement;
    textarea.style.height = 'auto';
    textarea.style.height = textarea.scrollHeight + 'px';
}

function handleTogglePin(): void {
    mobilePatch(togglePin.url(props.note.id), {}, { preserveScroll: true });
}

function handleDelete(): void {
    router.delete(destroyNote.url(props.note.id));
}
</script>

<template>
    <AppLayout :title="note.title" :class="getBgClass(note.color)">
        <Head :title="note.title" />

        <template #header-left>
            <Button variant="ghost" size="icon-xl" @click="router.visit('/notes')">
                <ArrowLeft :size="20" />
            </Button>
        </template>

        <template #header-right>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-xl">
                        <MoreVertical :size="20" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="handleTogglePin">
                        <Pin :size="14" class="mr-2" />
                        {{ note.is_pinned ? 'Désépingler' : 'Épingler' }}
                    </DropdownMenuItem>
                    <DropdownMenuItem class="text-destructive" @click="handleDelete">
                        <Trash2 :size="14" class="mr-2" />
                        Supprimer
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <div class="flex flex-col gap-2 p-4" :class="getBgClass(note.color)">
            <input
                v-model="title"
                type="text"
                class="w-full bg-transparent text-xl font-semibold text-foreground outline-none placeholder:text-muted-foreground"
                placeholder="Titre"
                @input="scheduleSave"
            />
            <textarea
                v-model="content"
                class="min-h-[50vh] w-full resize-none bg-transparent text-base text-foreground outline-none placeholder:text-muted-foreground"
                placeholder="Commencer à écrire..."
                @input="(e) => { autoResize(e); scheduleSave(); }"
            />
        </div>
    </AppLayout>
</template>
