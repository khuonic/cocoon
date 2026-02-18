<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    UtensilsCrossed,
    ShoppingCart,
    Settings,
    ChevronRight,
} from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';

const menuItems = [
    { label: 'Courses', href: '/shopping-lists', icon: ShoppingCart, description: 'Listes de courses' },
    { label: 'Repas', href: '/recipes', icon: UtensilsCrossed, description: 'Recettes et idées' },
    { label: 'Paramètres', href: '/settings/profile', icon: Settings, description: 'Profil et préférences' },
];
</script>

<template>
    <Head title="Plus" />

    <AppLayout title="Plus">
        <div class="p-4">
            <div class="space-y-2">
                <Link
                    v-for="item in menuItems"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-4 rounded-xl bg-card p-4 shadow-sm transition-colors active:bg-accent"
                >
                    <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                        <component :is="item.icon" :size="20" class="text-primary" />
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-foreground">{{ item.label }}</p>
                        <p class="text-sm text-muted-foreground">{{ item.description }}</p>
                    </div>
                    <ChevronRight :size="18" class="text-muted-foreground" />
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
