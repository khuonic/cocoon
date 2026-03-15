<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AddItemForm from '@/components/shopping/AddItemForm.vue';
import CategoryGroup from '@/components/shopping/CategoryGroup.vue';
import ShoppingItemRow from '@/components/shopping/ShoppingItemRow.vue';
import { MoreVertical, Trash2, Copy, ArrowLeft } from 'lucide-vue-next';
import type { ShoppingList, ShoppingItem, CategoryOption } from '@/types/shopping';
import { destroy, duplicate } from '@/actions/App/Http/Controllers/ShoppingListController';

const props = defineProps<{
    shoppingList: ShoppingList;
    uncheckedItemsByCategory: Record<string, ShoppingItem[]>;
    checkedItemsByCategory: Record<string, ShoppingItem[]>;
    categories: CategoryOption[];
    itemSuggestions: string[];
}>();

const categoryMeta: Record<string, { label: string; icon: string }> = {
    '': { label: 'Sans catégorie', icon: '📋' },
};
props.categories.forEach((c) => {
    categoryMeta[c.value] = { label: c.label, icon: c.icon };
});

const CATEGORY_BG: Record<string, string> = {
    fruits_legumes: 'bg-green-50',
    frais: 'bg-sky-50',
    epicerie_salee: 'bg-amber-50',
    epicerie_sucree: 'bg-orange-50',
    boissons: 'bg-cyan-50',
    hygiene: 'bg-violet-50',
    maison: 'bg-orange-50',
    autre: 'bg-zinc-50',
    '': 'bg-slate-50',
};

onMounted(() => {
    localStorage.setItem('cocon_last_shopping_list_id', String(props.shoppingList.id));
});

function handleDelete(): void {
    router.delete(destroy.url(props.shoppingList.id));
}

function handleDuplicate(): void {
    router.post(duplicate.url(props.shoppingList.id));
}

function checkedCount(): number {
    return Object.values(props.checkedItemsByCategory).reduce((sum, items) => sum + items.length, 0);
}

function allCheckedItems(): ShoppingItem[] {
    return Object.values(props.checkedItemsByCategory).flat();
}

function allUncheckedItems(): ShoppingItem[] {
    return Object.values(props.uncheckedItemsByCategory).flat();
}

function goBack(): void {
    sessionStorage.setItem('shopping_no_redirect', '1');
    router.visit('/shopping-lists');
}
</script>

<template>
    <AppLayout :title="shoppingList.name">
        <template #header-left>
            <Button variant="ghost" size="icon-xl" @click="goBack">
                <ArrowLeft :size="22" />
            </Button>
        </template>
        <template #header-right>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-xl">
                        <MoreVertical :size="20" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem v-if="shoppingList.is_template" @click="handleDuplicate">
                        <Copy :size="16" class="mr-2" />
                        Dupliquer
                    </DropdownMenuItem>
                    <DropdownMenuItem class="text-destructive" @click="handleDelete">
                        <Trash2 :size="16" class="mr-2" />
                        Supprimer la liste
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <Head :title="shoppingList.name" />

        <div class="space-y-4 p-4 pb-28">
            <!-- Articles non cochés groupés par catégorie -->
            <template v-for="(items, category) in uncheckedItemsByCategory" :key="category">
                <CategoryGroup
                    :label="categoryMeta[category as string]?.label ?? String(category)"
                    :icon="categoryMeta[category as string]?.icon"
                >
                    <ShoppingItemRow
                        v-for="item in items"
                        :key="item.id"
                        :item="item"
                        :categories="categories"
                        :bg-class="CATEGORY_BG[category as string] ?? 'bg-card'"
                    />
                </CategoryGroup>
            </template>

            <!-- Articles cochés groupés par catégorie -->
            <div v-if="checkedCount() > 0" class="rounded-2xl bg-muted/30 p-3 space-y-2">
                <p class="px-1 text-xs font-medium uppercase tracking-wider text-muted-foreground">
                    Cochés ({{ checkedCount() }})
                </p>
                <template v-for="(items, category) in checkedItemsByCategory" :key="`checked-${category}`">
                    <CategoryGroup
                        :label="categoryMeta[category as string]?.label ?? String(category)"
                        :icon="categoryMeta[category as string]?.icon"
                    >
                        <ShoppingItemRow
                            v-for="item in items"
                            :key="item.id"
                            :item="item"
                            :categories="categories"
                        />
                    </CategoryGroup>
                </template>
            </div>
        </div>

        <AddItemForm
            :shopping-list-id="shoppingList.id"
            :categories="categories"
            :item-suggestions="itemSuggestions"
            :checked-items="allCheckedItems()"
            :unchecked-items="allUncheckedItems()"
        />
    </AppLayout>
</template>