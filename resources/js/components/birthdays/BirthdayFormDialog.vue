<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store, update } from '@/actions/App/Http/Controllers/BirthdayController';
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
import { mobilePut } from '@/lib/form-helpers';
import type { Birthday } from '@/types/birthday';

const props = defineProps<{
    birthday?: Birthday;
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.birthday;

const form = useForm({
    name: '',
    date: '',
    reminder_days_before: null as number | null,
});

function resetForm(): void {
    if (props.birthday) {
        form.name = props.birthday.name;
        form.date = props.birthday.date.split('T')[0];
        form.reminder_days_before = props.birthday.reminder_days_before;
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

watch(() => props.birthday, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function submit(): void {
    if (isEditMode() && props.birthday) {
        mobilePut(form, update.url(props.birthday.id), {
            preserveScroll: true,
            onSuccess: () => { form.reset(); isOpen.value = false; },
        });
    } else {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => { form.reset(); isOpen.value = false; },
        });
    }
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ isEditMode() ? 'Modifier l\'anniversaire' : 'Nouvel anniversaire' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails de l\'anniversaire.' : 'Ajoute un nouvel anniversaire.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="birthday-name">Nom</Label>
                    <Input
                        id="birthday-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Ex: Maman"
                        required
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label for="birthday-date">Date de naissance</Label>
                    <Input
                        id="birthday-date"
                        v-model="form.date"
                        type="date"
                        required
                    />
                    <InputError :message="form.errors.date" />
                </div>

                <div class="space-y-2">
                    <Label for="birthday-reminder">Rappel</Label>
                    <select
                        id="birthday-reminder"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :value="form.reminder_days_before === null ? '' : String(form.reminder_days_before)"
                        @change="(e) => { const v = (e.target as HTMLSelectElement).value; form.reminder_days_before = v === '' ? null : Number(v); }"
                    >
                        <option value="">Pas de rappel</option>
                        <option value="1">La veille</option>
                        <option value="0">Le jour J</option>
                    </select>
                </div>

                <DialogFooter>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditMode() ? 'Enregistrer' : 'Ajouter' }}
                    </Button>
                </DialogFooter>

            </form>
        </DialogContent>
    </Dialog>
</template>
