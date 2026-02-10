<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { mobilePatch } from '@/lib/form-helpers';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Star, Trash2 } from 'lucide-vue-next';
import type { ShoppingItem } from '@/types/shopping';
import { toggleCheck } from '@/actions/App/Http/Controllers/ShoppingItemController';
import { toggleFavorite } from '@/actions/App/Http/Controllers/ShoppingItemController';
import { destroy } from '@/actions/App/Http/Controllers/ShoppingItemController';

const props = defineProps<{
    item: ShoppingItem;
}>();

function handleToggleCheck(): void {
    mobilePatch(toggleCheck.url(props.item.id), {}, {
        preserveScroll: true,
    });
}

function handleToggleFavorite(): void {
    mobilePatch(toggleFavorite.url(props.item.id), {}, {
        preserveScroll: true,
    });
}

function handleDelete(): void {
    router.delete(destroy.url(props.item.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div
        class="flex items-center gap-3 rounded-lg bg-card px-3 py-2.5"
        :class="item.is_checked ? 'opacity-50' : ''"
    >
        <Checkbox
            :checked="item.is_checked"
            @update:checked="handleToggleCheck"
        />

        <div class="min-w-0 flex-1">
            <span
                class="text-sm"
                :class="item.is_checked ? 'text-muted-foreground line-through' : 'text-foreground'"
            >
                {{ item.name }}
            </span>
            <span v-if="item.quantity" class="ml-1.5 text-xs text-muted-foreground">
                {{ item.quantity }}
            </span>
        </div>

        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0"
            @click="handleToggleFavorite"
        >
            <Star
                :size="16"
                :class="item.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-muted-foreground'"
            />
        </Button>

        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 text-muted-foreground"
            @click="handleDelete"
        >
            <Trash2 :size="16" />
        </Button>
    </div>
</template>
