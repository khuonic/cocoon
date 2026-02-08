<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Bookmark } from 'lucide-vue-next';
</script>

<template>
    <Head title="Leboncoin" />

    <AppLayout title="Leboncoin">
        <div class="p-4">
            <EmptyState
                title="Aucune annonce sauvegardée"
                description="Ajoute des annonces Leboncoin qui vous intéressent."
                :icon="Bookmark"
            />
        </div>
    </AppLayout>
</template>
