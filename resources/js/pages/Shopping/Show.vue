<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import AddItemForm from '@/components/shopping/AddItemForm.vue';
import CategoryGroup from '@/components/shopping/CategoryGroup.vue';
import ShoppingItemRow from '@/components/shopping/ShoppingItemRow.vue';
import { MoreVertical, Trash2, Copy, ChevronDown } from 'lucide-vue-next';
import type { ShoppingList, ShoppingItem, CategoryOption } from '@/types/shopping';
import { destroy, duplicate } from '@/actions/App/Http/Controllers/ShoppingListController';

const props = defineProps<{
    shoppingList: ShoppingList;
    uncheckedItemsByCategory: Record<string, ShoppingItem[]>;
    checkedItems: ShoppingItem[];
    categories: CategoryOption[];
}>();

const checkedOpen = ref(false);

const categoryLabels: Record<string, string> = {};
props.categories.forEach((c) => {
    categoryLabels[c.value] = c.label;
});

function handleDelete(): void {
    router.delete(destroy.url(props.shoppingList.id));
}

function handleDuplicate(): void {
    router.post(duplicate.url(props.shoppingList.id));
}
</script>

<template>
    <AppLayout :title="shoppingList.name">
        <template #header-left>
            <BackButton href="/shopping-lists" />
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

        <AddItemForm
            :shopping-list-id="shoppingList.id"
            :categories="categories"
        />

        <div class="space-y-4 p-4">
            <template v-for="(items, category) in uncheckedItemsByCategory" :key="category">
                <CategoryGroup :label="categoryLabels[category] ?? category">
                    <ShoppingItemRow
                        v-for="item in items"
                        :key="item.id"
                        :item="item"
                    />
                </CategoryGroup>
            </template>

            <Collapsible v-if="checkedItems.length > 0" v-model:open="checkedOpen">
                <CollapsibleTrigger class="flex w-full items-center gap-2 rounded-lg px-1 py-2 text-sm text-muted-foreground">
                    <ChevronDown
                        :size="16"
                        class="transition-transform"
                        :class="checkedOpen ? 'rotate-0' : '-rotate-90'"
                    />
                    Articles cochés ({{ checkedItems.length }})
                </CollapsibleTrigger>
                <CollapsibleContent class="space-y-1">
                    <ShoppingItemRow
                        v-for="item in checkedItems"
                        :key="item.id"
                        :item="item"
                    />
                </CollapsibleContent>
            </Collapsible>
        </div>
    </AppLayout>
</template>
