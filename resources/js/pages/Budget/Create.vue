<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm, usePage } from '@inertiajs/vue3';
import { watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import InputError from '@/components/InputError.vue';
import CategoryPicker from '@/components/budget/CategoryPicker.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import type { ExpenseCategory } from '@/types/budget';
import type { User } from '@/types/auth';
import { store } from '@/actions/App/Http/Controllers/ExpenseController';

const props = defineProps<{
    categories: ExpenseCategory[];
    users: User[];
}>();

const page = usePage();
const currentUserId = page.props.auth.user.id;

const form = useForm({
    amount: '',
    description: '',
    category_id: null as number | null,
    paid_by: currentUserId,
    split_type: 'equal',
    split_value: '',
    date: new Date().toISOString().split('T')[0],
    is_recurring: false,
    recurrence_type: null as string | null,
});

watch(() => form.is_recurring, (val) => {
    if (!val) { form.recurrence_type = null; }
});

function submit(): void {
    form.post(store.url());
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
    <AppLayout title="Nouvelle dépense">
        <template #header-right>
            <Button size="sm" :disabled="form.processing" @click="submit">
                Ajouter
            </Button>
        </template>

        <Head title="Nouvelle dépense" />

        <form @submit.prevent="submit" class="space-y-6 p-4">
            <!-- Description -->
            <div class="space-y-2">
                <Label for="description">Description</Label>
                <Input
                    id="description"
                    v-model="form.description"
                    type="text"
                    placeholder="Ex: Courses Leclerc"
                    required
                    autofocus
                />
                <InputError :message="form.errors.description" />
            </div>

            <!-- Montant + Date sur la même ligne -->
            <div class="flex gap-3">
                <div class="flex-1 space-y-2">
                    <Label for="amount">Montant (EUR)</Label>
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        inputmode="decimal"
                        placeholder="0.00"
                        class="text-xl font-bold text-center"
                        required
                    />
                    <InputError :message="form.errors.amount" />
                </div>
                <div class="flex-1 space-y-2">
                    <Label for="date">Date</Label>
                    <Input
                        id="date"
                        v-model="form.date"
                        type="date"
                    />
                    <InputError :message="form.errors.date" />
                </div>
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

            <!-- Récurrence -->
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <Label for="is_recurring">Dépense récurrente</Label>
                    <Switch
                        id="is_recurring"
                        v-model:checked="form.is_recurring"
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

        </form>
    </AppLayout>
</template>
