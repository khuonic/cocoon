<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import FloatingActionButton from '@/components/FloatingActionButton.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ShoppingCart, Copy } from 'lucide-vue-next';
import type { ShoppingList } from '@/types/shopping';
import { create, show } from '@/routes/shopping-lists';
import { duplicate } from '@/actions/App/Http/Controllers/ShoppingListController';

const props = defineProps<{
    shoppingLists: ShoppingList[];
}>();

onMounted(() => {
    const lastId = localStorage.getItem('cocon_last_shopping_list_id');
    if (lastId) {
        const found = props.shoppingLists.find((l) => String(l.id) === lastId);
        if (found) {
            router.visit(show.url(found.id), { replace: true });
        }
    }
});

function handleDuplicate(list: ShoppingList): void {
    router.post(duplicate.url(list.id));
}
</script>

<template>
    <AppLayout title="Courses">
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
                    class="flex items-center gap-3 rounded-xl bg-card p-4 shadow-sm transition-colors active:bg-muted"
                >
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-primary/10">
                        <ShoppingCart :size="20" class="text-primary" />
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate text-base font-medium text-foreground">{{ list.name }}</p>
                            <Badge v-if="list.is_template" variant="secondary" class="shrink-0 text-xs">
                                Modèle
                            </Badge>
                        </div>
                        <p class="text-sm text-muted-foreground">
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
                        <Copy :size="18" />
                    </Button>
                </Link>
            </div>
        </div>

        <FloatingActionButton @click="router.visit(create.url())" />
    </AppLayout>
</template>
