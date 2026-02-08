<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { PlusCircle } from 'lucide-vue-next';
</script>

<template>
    <Head title="Nouvelle dépense" />

    <AppLayout title="Nouvelle dépense">
        <div class="p-4">
            <EmptyState
                title="Formulaire à venir"
                description="Le formulaire d'ajout de dépense sera bientôt disponible."
                :icon="PlusCircle"
            />
        </div>
    </AppLayout>
</template>
