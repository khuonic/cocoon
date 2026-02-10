<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { mobilePut } from '@/lib/form-helpers';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import CategoryPicker from '@/components/budget/CategoryPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import type { Expense, ExpenseCategory } from '@/types/budget';
import type { User } from '@/types/auth';
import { update, destroy } from '@/actions/App/Http/Controllers/ExpenseController';

const props = defineProps<{
    expense: Expense;
    categories: ExpenseCategory[];
    users: User[];
}>();

const form = useForm({
    amount: props.expense.amount,
    description: props.expense.description,
    category_id: props.expense.category_id as number | null,
    paid_by: props.expense.paid_by,
    split_type: props.expense.split_type,
    split_value: props.expense.split_value ?? '',
    date: props.expense.date.split('T')[0],
    is_recurring: props.expense.is_recurring,
    recurrence_type: props.expense.recurrence_type,
});

function submit(): void {
    mobilePut(form, update.url({ expense: props.expense.id }));
}

const deleteOpen = ref(false);
const deleting = ref(false);

function confirmDelete(): void {
    deleting.value = true;
    router.delete(destroy.url({ expense: props.expense.id }), {
        onFinish: () => {
            deleting.value = false;
            deleteOpen.value = false;
        },
    });
}

const splitOptions = [
    { value: 'equal', label: 'Moitié-moitié' },
    { value: 'full_payer', label: 'Perso' },
    { value: 'full_other', label: '100% l\'autre' },
    { value: 'custom', label: 'Custom' },
];

const recurrenceOptions = [
    { value: 'daily', label: 'Quotidien' },
    { value: 'weekly', label: 'Hebdomadaire' },
    { value: 'monthly', label: 'Mensuel' },
    { value: 'yearly', label: 'Annuel' },
];
</script>

<template>
    <AppLayout title="Modifier la dépense">
        <Head title="Modifier la dépense" />

        <form @submit.prevent="submit" class="space-y-6 p-4">
            <!-- Montant -->
            <div class="space-y-2">
                <Label for="amount">Montant (EUR)</Label>
                <Input
                    id="amount"
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    min="0.01"
                    inputmode="decimal"
                    placeholder="0.00"
                    class="text-2xl font-bold text-center h-14"
                    required
                />
                <InputError :message="form.errors.amount" />
            </div>

            <!-- Description -->
            <div class="space-y-2">
                <Label for="description">Description</Label>
                <Input
                    id="description"
                    v-model="form.description"
                    type="text"
                    placeholder="Ex: Courses Leclerc"
                    required
                />
                <InputError :message="form.errors.description" />
            </div>

            <!-- Catégorie -->
            <div class="space-y-2">
                <Label>Catégorie</Label>
                <CategoryPicker
                    :categories="categories"
                    v-model="form.category_id"
                />
                <InputError :message="form.errors.category_id" />
            </div>

            <!-- Payé par -->
            <div class="space-y-2">
                <Label>Payé par</Label>
                <div class="flex gap-2">
                    <Button
                        v-for="user in users"
                        :key="user.id"
                        type="button"
                        :variant="form.paid_by === user.id ? 'default' : 'outline'"
                        class="flex-1"
                        @click="form.paid_by = user.id"
                    >
                        {{ user.name }}
                    </Button>
                </div>
                <InputError :message="form.errors.paid_by" />
            </div>

            <!-- Répartition -->
            <div class="space-y-2">
                <Label>Répartition</Label>
                <RadioGroup v-model="form.split_type" class="grid grid-cols-2 gap-2">
                    <div
                        v-for="option in splitOptions"
                        :key="option.value"
                        class="flex items-center gap-2 rounded-lg border p-3 cursor-pointer"
                        :class="form.split_type === option.value ? 'border-primary bg-primary/5' : 'border-border'"
                        @click="form.split_type = option.value"
                    >
                        <RadioGroupItem :value="option.value" :id="'split-' + option.value" />
                        <Label :for="'split-' + option.value" class="cursor-pointer font-normal">
                            {{ option.label }}
                        </Label>
                    </div>
                </RadioGroup>
                <InputError :message="form.errors.split_type" />

                <div v-if="form.split_type === 'custom'" class="mt-2">
                    <Label for="split_value">Montant dû par l'autre</Label>
                    <Input
                        id="split_value"
                        v-model="form.split_value"
                        type="number"
                        step="0.01"
                        min="0"
                        inputmode="decimal"
                        placeholder="0.00"
                    />
                    <InputError :message="form.errors.split_value" />
                </div>
            </div>

            <!-- Date -->
            <div class="space-y-2">
                <Label for="date">Date</Label>
                <Input
                    id="date"
                    v-model="form.date"
                    type="date"
                />
                <InputError :message="form.errors.date" />
            </div>

            <!-- Récurrence -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label for="is_recurring">Dépense récurrente</Label>
                    <Switch
                        id="is_recurring"
                        :checked="form.is_recurring"
                        @update:checked="(val: boolean) => {
                            form.is_recurring = val;
                            if (!val) form.recurrence_type = null;
                        }"
                    />
                </div>

                <div v-if="form.is_recurring" class="grid grid-cols-2 gap-2">
                    <Button
                        v-for="option in recurrenceOptions"
                        :key="option.value"
                        type="button"
                        :variant="form.recurrence_type === option.value ? 'default' : 'outline'"
                        size="sm"
                        @click="form.recurrence_type = option.value"
                    >
                        {{ option.label }}
                    </Button>
                </div>
                <InputError :message="form.errors.recurrence_type" />
            </div>

            <!-- Submit -->
            <Button
                type="submit"
                class="w-full"
                size="lg"
                :disabled="form.processing"
            >
                Enregistrer les modifications
            </Button>

            <!-- Delete -->
            <Dialog v-model:open="deleteOpen">
                <DialogTrigger as-child>
                    <Button type="button" variant="destructive" class="w-full">
                        Supprimer la dépense
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Supprimer la dépense</DialogTitle>
                        <DialogDescription>
                            Cette action est irréversible. La dépense "{{ expense.description }}" sera définitivement supprimée.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="ghost">Annuler</Button>
                        </DialogClose>
                        <Button
                            variant="destructive"
                            @click="confirmDelete"
                            :disabled="deleting"
                        >
                            Supprimer
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </form>
    </AppLayout>
</template>
