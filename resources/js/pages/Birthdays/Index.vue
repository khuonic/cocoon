<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Cake } from 'lucide-vue-next';
import { ref } from 'vue';
import BirthdayCard from '@/components/birthdays/BirthdayCard.vue';
import BirthdayFormDialog from '@/components/birthdays/BirthdayFormDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Birthday } from '@/types/birthday';

defineProps<{
    birthdays: Birthday[];
}>();

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

            <div v-else class="space-y-3">
                <BirthdayCard
                    v-for="birthday in birthdays"
                    :key="birthday.id"
                    :birthday="birthday"
                    @edit="openEdit"
                />
            </div>
        </div>

        <FloatingActionButton @click="openCreate" />

        <BirthdayFormDialog
            v-model:open="showDialog"
            :birthday="editingBirthday"
        />
    </AppLayout>
</template>
