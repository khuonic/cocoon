<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import { store, update, destroy } from '@/actions/App/Http/Controllers/CalendarController';
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
import type { CalendarEvent } from '@/types/calendar';

const CATEGORIES = [
    { value: 'Conges', label: 'Congés', color: '#10B981' },
    { value: 'Pro', label: 'Pro', color: '#3B82F6' },
    { value: 'Loisir', label: 'Loisirs', color: '#8B5CF6' },
    { value: 'Rdv', label: 'RDV', color: '#F59E0B' },
] as const;

const REMINDERS = [
    { value: null, label: 'Pas de rappel' },
    { value: 30, label: '30 min avant' },
    { value: 60, label: '1h avant' },
    { value: 1440, label: 'Veille (9h)' },
] as const;

const props = defineProps<{
    event?: CalendarEvent;
    defaultDate?: string;
}>();

const isOpen = defineModel<boolean>('open');

const isEditMode = () => !!props.event;

const form = useForm({
    title: '',
    description: '',
    location: '',
    category: 'Loisir',
    starts_at: '',
    ends_at: '',
    all_day: true,
    is_personal: false,
    reminder_before: null as number | null,
});

function resetForm(): void {
    if (props.event) {
        form.title = props.event.title;
        form.description = props.event.description ?? '';
        form.location = props.event.location ?? '';
        form.category = props.event.category;
        form.starts_at = props.event.starts_at.slice(0, 16);
        form.ends_at = props.event.ends_at ? props.event.ends_at.slice(0, 16) : '';
        form.all_day = props.event.all_day;
        form.is_personal = props.event.is_personal;
        form.reminder_before = props.event.reminder_before;
    } else {
        form.reset();
        form.clearErrors();
        form.category = 'Loisir';
        form.all_day = true;
        if (props.defaultDate) {
            form.starts_at = props.defaultDate + 'T09:00';
        }
    }
}

watch(isOpen, (open) => {
    if (open) {
        resetForm();
    }
});

watch(() => props.event, () => {
    if (isOpen.value) {
        resetForm();
    }
});

function submit(): void {
    if (isEditMode() && props.event) {
        mobilePut(form, update.url(props.event.id), {
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

function handleDelete(): void {
    if (!props.event) { return; }
    form.delete(destroy.url(props.event.id), {
        preserveScroll: true,
        onSuccess: () => { isOpen.value = false; },
    });
}
</script>

<template>
    <Dialog :open="isOpen" @update:open="isOpen = $event">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ isEditMode() ? 'Modifier l\'événement' : 'Nouvel événement' }}</DialogTitle>
                <DialogDescription>
                    {{ isEditMode() ? 'Modifie les détails de l\'événement.' : 'Ajoute un événement au calendrier.' }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="space-y-2">
                    <Label for="event-title">Titre</Label>
                    <Input
                        id="event-title"
                        v-model="form.title"
                        type="text"
                        placeholder="Ex: Réunion de famille"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" />
                </div>

                <!-- Catégorie -->
                <div class="space-y-2">
                    <Label>Catégorie</Label>
                    <div class="flex gap-2">
                        <button
                            v-for="cat in CATEGORIES"
                            :key="cat.value"
                            type="button"
                            class="flex-1 rounded-lg border px-3 py-1.5 text-xs font-medium transition-all"
                            :style="form.category === cat.value
                                ? { backgroundColor: cat.color, borderColor: cat.color, color: '#fff' }
                                : { borderColor: cat.color, color: cat.color }"
                            @click="form.category = cat.value"
                        >
                            {{ cat.label }}
                        </button>
                    </div>
                    <InputError :message="form.errors.category" />
                </div>

                <!-- Date début -->
                <div class="space-y-2">
                    <Label for="event-starts-at">Début</Label>
                    <Input
                        id="event-starts-at"
                        v-model="form.starts_at"
                        :type="form.all_day ? 'date' : 'datetime-local'"
                        required
                    />
                    <InputError :message="form.errors.starts_at" />
                </div>

                <!-- Journée entière -->
                <div class="flex items-center gap-3">
                    <input
                        id="event-all-day"
                        v-model="form.all_day"
                        type="checkbox"
                        class="size-4 rounded border-border"
                    />
                    <Label for="event-all-day" class="cursor-pointer">Journée entière</Label>
                </div>

                <!-- Date fin (si pas journée entière) -->
                <div v-if="!form.all_day" class="space-y-2">
                    <Label for="event-ends-at">Fin</Label>
                    <Input
                        id="event-ends-at"
                        v-model="form.ends_at"
                        type="datetime-local"
                    />
                    <InputError :message="form.errors.ends_at" />
                </div>

                <!-- Lieu -->
                <div class="space-y-2">
                    <Label for="event-location">Lieu <span class="text-muted-foreground text-xs">(optionnel)</span></Label>
                    <Input
                        id="event-location"
                        v-model="form.location"
                        type="text"
                        placeholder="Ex: Paris"
                    />
                </div>

                <!-- Rappel -->
                <div class="space-y-2">
                    <Label for="event-reminder">Rappel</Label>
                    <select
                        id="event-reminder"
                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        :value="form.reminder_before === null ? '' : String(form.reminder_before)"
                        @change="(e) => { const v = (e.target as HTMLSelectElement).value; form.reminder_before = v === '' ? null : Number(v); }"
                    >
                        <option value="">Pas de rappel</option>
                        <option value="30">30 min avant</option>
                        <option value="60">1h avant</option>
                        <option value="1440">Veille (9h)</option>
                    </select>
                </div>

                <!-- Personnel / Partagé -->
                <div class="flex items-center gap-3">
                    <input
                        id="event-personal"
                        v-model="form.is_personal"
                        type="checkbox"
                        class="size-4 rounded border-border"
                    />
                    <Label for="event-personal" class="cursor-pointer">Personnel (visible par moi uniquement)</Label>
                </div>

                <DialogFooter class="flex-col gap-2 sm:flex-row">
                    <Button
                        v-if="isEditMode()"
                        type="button"
                        variant="destructive"
                        class="sm:mr-auto"
                        :disabled="form.processing"
                        @click="handleDelete"
                    >
                        Supprimer
                    </Button>
                    <Button type="button" variant="outline" @click="isOpen = false">
                        Annuler
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ isEditMode() ? 'Enregistrer' : 'Ajouter' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
