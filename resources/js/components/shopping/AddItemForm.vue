<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Plus } from 'lucide-vue-next';
import type { CategoryOption } from '@/types/shopping';
import { store } from '@/actions/App/Http/Controllers/ShoppingItemController';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    shoppingListId: number;
    categories: CategoryOption[];
}>();

const form = useForm({
    name: '',
    category: 'autre',
    quantity: '',
});

function submit(): void {
    form.post(store.url(props.shoppingListId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}
</script>

<template>
    <form @submit.prevent="submit" class="sticky top-0 z-30 border-b border-border bg-card p-3 backdrop-blur-sm">
        <div class="flex gap-2">
            <Input
                v-model="form.name"
                type="text"
                placeholder="Ajouter un article..."
                class="flex-1"
                autofocus
            />
            <select
                v-model="form.category"
                class="h-9 rounded-md border border-input bg-background px-2 text-sm text-foreground"
            >
                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                    {{ cat.label }}
                </option>
            </select>
            <Input
                v-model="form.quantity"
                type="text"
                placeholder="Qté"
                class="w-16"
            />
            <Button type="submit" size="icon" :disabled="form.processing || !form.name">
                <Plus :size="18" />
            </Button>
        </div>
        <InputError :message="form.errors.name" />
        <InputError :message="form.errors.category" />
    </form>
</template>
