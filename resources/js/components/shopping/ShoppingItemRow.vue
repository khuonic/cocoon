<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { mobilePatch, mobilePut } from '@/lib/form-helpers';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { MoreVertical, Pencil, Trash2 } from 'lucide-vue-next';
import type { ShoppingItem, CategoryOption } from '@/types/shopping';
import { toggleCheck, update, destroy } from '@/actions/App/Http/Controllers/ShoppingItemController';

const props = defineProps<{
    item: ShoppingItem;
    categories: CategoryOption[];
}>();

const editOpen = ref(false);

const editForm = useForm({
    name: '',
    category: '',
});

watch(editOpen, (open) => {
    if (open) {
        editForm.name = props.item.name;
        editForm.category = props.item.category;
        editForm.clearErrors();
    }
});

function handleToggleCheck(): void {
    mobilePatch(toggleCheck.url(props.item.id), {}, { preserveScroll: true });
}

function handleDelete(): void {
    router.delete(destroy.url(props.item.id), { preserveScroll: true });
}

function handleEdit(): void {
    mobilePut(editForm, update.url(props.item.id), {
        preserveScroll: true,
        onSuccess: () => {
            editOpen.value = false;
        },
    });
}
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="w-full rounded-xl bg-card p-4 pr-12 text-left shadow-sm transition-opacity active:opacity-70"
            :class="item.is_checked ? 'opacity-50' : ''"
            @click="handleToggleCheck"
        >
            <span
                class="text-sm font-medium"
                :class="item.is_checked ? 'text-muted-foreground line-through' : 'text-foreground'"
            >
                {{ item.name }}
            </span>
        </button>

        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="ghost"
                    size="icon"
                    class="absolute right-2 top-1/2 h-8 w-8 -translate-y-1/2 text-muted-foreground"
                    @click.stop
                >
                    <MoreVertical :size="16" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
                <DropdownMenuItem @click="editOpen = true">
                    <Pencil :size="16" class="mr-2" />
                    Modifier
                </DropdownMenuItem>
                <DropdownMenuItem class="text-destructive" @click="handleDelete">
                    <Trash2 :size="16" class="mr-2" />
                    Supprimer
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>

    <Dialog v-model:open="editOpen">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Modifier l'article</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="handleEdit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="edit-item-name">Nom</Label>
                    <Input
                        id="edit-item-name"
                        v-model="editForm.name"
                        type="text"
                        placeholder="Nom de l'article"
                        required
                    />
                    <InputError :message="editForm.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="edit-item-category">Catégorie</Label>
                    <select
                        id="edit-item-category"
                        v-model="editForm.category"
                        class="h-9 w-full rounded-md border border-input bg-background px-2 text-sm text-foreground"
                    >
                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                            {{ cat.label }}
                        </option>
                    </select>
                    <InputError :message="editForm.errors.category" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="editOpen = false">
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="editForm.processing">
                        Enregistrer
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
