<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { UtensilsCrossed } from 'lucide-vue-next';
</script>

<template>
    <Head title="Repas" />

    <AppLayout title="Repas">
        <div class="p-4">
            <EmptyState
                title="Aucun repas planifié"
                description="Planifie vos repas de la semaine pour ne plus se poser la question."
                :icon="UtensilsCrossed"
            />
        </div>
    </AppLayout>
</template>
