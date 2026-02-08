<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { StickyNote } from 'lucide-vue-next';
</script>

<template>
    <Head title="Notes" />

    <AppLayout title="Notes">
        <div class="p-4">
            <EmptyState
                title="Aucune note"
                description="Crée des notes partagées pour garder une trace de tout."
                :icon="StickyNote"
            />
        </div>
    </AppLayout>
</template>
