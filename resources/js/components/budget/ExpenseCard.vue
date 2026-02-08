<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Expense } from '@/types/budget';
import CategoryIcon from './CategoryIcon.vue';
import { edit } from '@/routes/expenses';

const props = defineProps<{
    expense: Expense;
}>();

const splitLabel: Record<string, string> = {
    equal: '50/50',
    full_payer: 'Perso',
    full_other: '100%',
    custom: 'Custom',
};

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
    });
}

function formatAmount(amount: string): string {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(amount));
}
</script>

<template>
    <Link
        :href="edit({ expense: expense.id })"
        class="flex items-center gap-3 rounded-xl bg-card p-3 shadow-sm transition-colors active:bg-muted"
    >
        <div
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full"
            :style="{ backgroundColor: expense.category.color + '20' }"
        >
            <CategoryIcon
                :name="expense.category.icon"
                :color="expense.category.color"
                :size="18"
            />
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate font-medium text-foreground">{{ expense.description }}</p>
            <p class="text-xs text-muted-foreground">
                {{ formatDate(expense.date) }} &middot; {{ expense.payer.name }}
            </p>
        </div>

        <div class="shrink-0 text-right">
            <p class="font-semibold text-foreground">{{ formatAmount(expense.amount) }}</p>
            <p class="text-xs text-muted-foreground">{{ splitLabel[expense.split_type] }}</p>
        </div>
    </Link>
</template>
