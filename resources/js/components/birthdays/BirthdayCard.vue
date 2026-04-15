<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Trash2, Cake } from 'lucide-vue-next';
import { destroy } from '@/actions/App/Http/Controllers/BirthdayController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Birthday } from '@/types/birthday';

const props = defineProps<{
    birthday: Birthday;
}>();

const emit = defineEmits<{
    edit: [birthday: Birthday];
}>();

const deleteOpen = ref(false);
const deleting = ref(false);

function formatDate(dateStr: string): string {
    const date = new Date(dateStr);
    return date.toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
}

function confirmDelete(): void {
    deleting.value = true;
    router.delete(destroy.url(props.birthday.id), {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}
</script>

<template>
    <div class="flex items-center gap-4 rounded-xl border border-border bg-card p-4">
        <div class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-100">
            <Cake :size="22" class="text-amber-600" />
        </div>
        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', birthday)">
            <h3 class="truncate text-base font-semibold text-foreground">{{ birthday.name }}</h3>
            <p class="mt-0.5 text-sm text-muted-foreground">
                {{ formatDate(birthday.date) }}
            </p>
            <p class="text-sm font-medium text-foreground">{{ birthday.age }} ans</p>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="h-9 w-9 shrink-0 text-muted-foreground hover:text-destructive"
            @click="deleteOpen = true"
        >
            <Trash2 :size="18" />
        </Button>
    </div>

    <Dialog v-model:open="deleteOpen">
        <DialogContent position="center">
            <DialogHeader>
                <DialogTitle>Supprimer l'anniversaire</DialogTitle>
                <DialogDescription>
                    Supprimer l'anniversaire de {{ birthday.name }} ? Cette action est irréversible.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <Button variant="ghost" @click="deleteOpen = false">Annuler</Button>
                <Button variant="destructive" :disabled="deleting" @click="confirmDelete">
                    Supprimer
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
