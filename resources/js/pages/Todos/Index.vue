<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { CheckSquare } from 'lucide-vue-next';
</script>

<template>
    <Head title="Tâches" />

    <AppLayout title="Tâches">
        <div class="p-4">
            <EmptyState
                title="Aucune tâche"
                description="Ajoute des tâches pour organiser votre quotidien à deux."
                :icon="CheckSquare"
            />
        </div>
    </AppLayout>
</template>
