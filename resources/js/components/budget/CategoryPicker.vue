<script setup lang="ts">
import type { ExpenseCategory } from '@/types/budget';
import CategoryIcon from './CategoryIcon.vue';

defineProps<{
    categories: ExpenseCategory[];
    modelValue: number | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: number];
}>();
</script>

<template>
    <div class="grid grid-cols-4 gap-3">
        <button
            v-for="category in categories"
            :key="category.id"
            type="button"
            class="flex flex-col items-center gap-1.5 rounded-xl p-3 transition-all"
            :class="modelValue === category.id
                ? 'ring-2 shadow-sm bg-card'
                : 'bg-muted/50 hover:bg-muted'"
            :style="modelValue === category.id ? { ringColor: category.color } : undefined"
            @click="emit('update:modelValue', category.id)"
        >
            <div
                class="flex h-10 w-10 items-center justify-center rounded-full"
                :style="{ backgroundColor: category.color + '20' }"
            >
                <CategoryIcon :name="category.icon" :color="category.color" :size="20" />
            </div>
            <span class="text-xs font-medium text-foreground">{{ category.name }}</span>
        </button>
    </div>
</template>
