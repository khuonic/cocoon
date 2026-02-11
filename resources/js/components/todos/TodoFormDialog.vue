<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/TodoController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { mobilePut } from '@/lib/form-helpers';
import type { User } from '@/types/auth';
import type { Todo } from '@/types/todo';

const props = defineProps<{
    todo?: Todo;
    users: User[];
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.todo;

const form = useForm({
    title: '',
    description: '' as string | null,
    is_personal: false,
    assigned_to: null as number | null,
    due_date: '' as string | null,
    show_on_dashboard: false,
});

function resetForm(): void {
    if (props.todo) {
        form.title = props.todo.title;
        form.description = props.todo.description ?? '';
        form.is_personal = props.todo.is_personal;
        form.assigned_to = props.todo.assigned_to;
        form.due_date = props.todo.due_date ?? '';
        form.show_on_dashboard = props.todo.show_on_dashboard;
    } else {
        form.reset();
        form.clearErrors();
    }
}

watch(isOpen, (open) => {
    if (open) {
        resetForm();
    }
});

watch(() => props.todo, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function submit(): void {
    if (isEditMode() && props.todo) {
        mobilePut(form, update.url(props.todo.id), {
            preserveScroll: true,
            onSuccess: () => { isOpen.value = false; },
        });
    } else {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => { isOpen.value = false; },
        });
    }
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ isEditMode() ? 'Modifier la tâche' : 'Nouvelle tâche' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails de la tâche.' : 'Ajoute une nouvelle tâche.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <!-- Titre -->
                <div class="space-y-2">
                    <Label for="todo-title">Titre</Label>
                    <Input
                        id="todo-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Ex: Faire les courses"
                        required
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Description -->
                <div class="space-y-2">
                    <Label for="todo-description">Description</Label>
                    <Input
                        id="todo-description"
                        v-model="form.description"
                        type="text"
                        placeholder="Détails optionnels..."
                    />
                    <InputError :message="form.errors.description" />
                </div>

                <!-- Tâche personnelle -->
                <div class="flex items-center justify-between">
                    <Label for="todo-personal">Tâche personnelle</Label>
                    <Switch
                        id="todo-personal"
                        :checked="form.is_personal"
                        @update:checked="(val: boolean) => {
                            form.is_personal = val;
                            if (val) form.assigned_to = null;
                        }"
                    />
                </div>

                <!-- Assigné à -->
                <div v-if="!form.is_personal" class="space-y-2">
                    <Label>Assignée à</Label>
                    <div class="flex gap-2">
                        <Button
                            v-for="user in users"
                            :key="user.id"
                            type="button"
                            :variant="form.assigned_to === user.id ? 'default' : 'outline'"
                            class="flex-1"
                            size="sm"
                            @click="form.assigned_to = form.assigned_to === user.id ? null : user.id"
                        >
                            {{ user.name }}
                        </Button>
                    </div>
                    <InputError :message="form.errors.assigned_to" />
                </div>

                <!-- Date d'échéance -->
                <div class="space-y-2">
                    <Label for="todo-due-date">Date d'échéance</Label>
                    <Input
                        id="todo-due-date"
                        v-model="form.due_date"
                        type="date"
                    />
                    <InputError :message="form.errors.due_date" />
                </div>

                <!-- Afficher sur l'accueil -->
                <div class="flex items-center justify-between">
                    <Label for="todo-dashboard">Afficher sur l'accueil</Label>
                    <Switch
                        id="todo-dashboard"
                        :checked="form.show_on_dashboard"
                        @update:checked="(val: boolean) => form.show_on_dashboard = val"
                    />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        @click="isOpen = false"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        :disabled="form.processing"
                    >
                        {{ isEditMode() ? 'Enregistrer' : 'Ajouter' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
