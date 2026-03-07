<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Cake, ArrowUpDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';
import BirthdayCard from '@/components/birthdays/BirthdayCard.vue';
import BirthdayFormDialog from '@/components/birthdays/BirthdayFormDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Birthday } from '@/types/birthday';

const props = defineProps<{
    birthdays: Birthday[];
}>();

type SortMode = 'date' | 'name';
const sortMode = ref<SortMode>('date');

function toggleSort(): void {
    sortMode.value = sortMode.value === 'date' ? 'name' : 'date';
}

const sortedBirthdays = computed((): Birthday[] => {
    return [...props.birthdays].sort((a, b) => {
        if (sortMode.value === 'name') {
            return a.name.localeCompare(b.name, 'fr');
        }
        // Tri par date sans année : mois * 100 + jour
        const aDate = new Date(a.date);
        const bDate = new Date(b.date);
        const aVal = (aDate.getMonth() + 1) * 100 + aDate.getDate();
        const bVal = (bDate.getMonth() + 1) * 100 + bDate.getDate();
        return aVal - bVal;
    });
});

const showDialog = ref(false);
const editingBirthday = ref<Birthday | undefined>();

function openCreate(): void {
    editingBirthday.value = undefined;
    showDialog.value = true;
}

function openEdit(birthday: Birthday): void {
    editingBirthday.value = birthday;
    showDialog.value = true;
}
</script>

<template>
    <Head title="Anniversaires" />

    <AppLayout title="Anniversaires">
        <div class="p-4">
            <EmptyState
                v-if="birthdays.length === 0"
                title="Aucun anniversaire"
                description="Ajoute des anniversaires pour ne plus les oublier."
                :icon="Cake"
            >
                <template #action>
                    <Button @click="openCreate">Ajouter un anniversaire</Button>
                </template>
            </EmptyState>

            <template v-else>
                <div class="mb-3 flex justify-end">
                    <Button variant="outline" size="sm" class="gap-1.5" @click="toggleSort">
                        <ArrowUpDown :size="14" />
                        {{ sortMode === 'date' ? 'Par date' : 'Par nom' }}
                    </Button>
                </div>

                <div class="space-y-3">
                    <BirthdayCard
                        v-for="birthday in sortedBirthdays"
                        :key="birthday.id"
                        :birthday="birthday"
                        @edit="openEdit"
                    />
                </div>
            </template>
        </div>

        <FloatingActionButton @click="openCreate" />

        <BirthdayFormDialog
            v-model:open="showDialog"
            :birthday="editingBirthday"
        />
    </AppLayout>
</template>
