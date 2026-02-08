<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { ShoppingCart } from 'lucide-vue-next';
</script>

<template>
    <Head title="Courses" />

    <AppLayout title="Courses">
        <div class="p-4">
            <EmptyState
                title="Aucune liste de courses"
                description="Crée ta première liste pour ne plus rien oublier au supermarché."
                :icon="ShoppingCart"
            />
        </div>
    </AppLayout>
</template>
