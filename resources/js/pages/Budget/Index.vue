<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Wallet } from 'lucide-vue-next';
</script>

<template>
    <Head title="Budget" />

    <AppLayout title="Budget">
        <div class="p-4">
            <EmptyState
                title="Aucune dépense"
                description="Ajoute ta première dépense pour commencer à suivre votre budget."
                :icon="Wallet"
            />
        </div>
    </AppLayout>
</template>
