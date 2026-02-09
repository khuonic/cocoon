<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Plus, Copy } from 'lucide-vue-next';
import type { ShoppingList } from '@/types/shopping';
import { create, show } from '@/routes/shopping-lists';
import { duplicate } from '@/actions/App/Http/Controllers/ShoppingListController';

defineProps<{
    shoppingLists: ShoppingList[];
}>();

function handleDuplicate(list: ShoppingList): void {
    router.post(duplicate.url(list.id));
}
</script>

<template>
    <AppLayout title="Courses">
        <template #header-right>
            <Button as-child size="icon" variant="ghost">
                <Link :href="create.url()">
                    <Plus :size="22" />
                </Link>
            </Button>
        </template>

        <Head title="Courses" />

        <div class="p-4">
            <EmptyState
                v-if="shoppingLists.length === 0"
                title="Aucune liste de courses"
                description="Crée ta première liste pour ne plus rien oublier au supermarché."
                :icon="ShoppingCart"
            >
                <template #action>
                    <Button as-child>
                        <Link :href="create.url()">Créer une liste</Link>
                    </Button>
                </template>
            </EmptyState>

            <div v-else class="space-y-2">
                <Link
                    v-for="list in shoppingLists"
                    :key="list.id"
                    :href="show.url(list.id)"
                    class="flex items-center gap-3 rounded-xl bg-card p-3 shadow-sm transition-colors active:bg-muted"
                >
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary/10">
                        <ShoppingCart :size="18" class="text-primary" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium text-foreground">{{ list.name }}</p>
                            <Badge v-if="list.is_template" variant="secondary" class="shrink-0 text-xs">
                                Modèle
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ list.checked_items_count }}/{{ list.items_count }} cochés
                        </p>
                    </div>

                    <Button
                        v-if="list.is_template"
                        variant="ghost"
                        size="icon"
                        class="shrink-0"
                        @click.prevent="handleDuplicate(list)"
                    >
                        <Copy :size="16" />
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
