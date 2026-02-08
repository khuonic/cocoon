<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { LayoutDashboard } from 'lucide-vue-next';
</script>

<template>
    <Head title="Accueil" />

    <AppLayout title="Cocon">
        <div class="p-4">
            <EmptyState
                title="Bienvenue dans Cocon"
                description="Ton espace couple pour organiser le quotidien. Les widgets arriveront bientôt !"
                :icon="LayoutDashboard"
            />
        </div>
    </AppLayout>
</template>
