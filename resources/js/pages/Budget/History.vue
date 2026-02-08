<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import CategoryIcon from '@/components/budget/CategoryIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Expense, ExpenseCategory } from '@/types/budget';
import { index } from '@/routes/expenses';

type CategoryTotal = {
    category_id: number;
    total: string;
    category: ExpenseCategory;
};

type PaginatedExpenses = {
    data: Expense[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    current_page: number;
    last_page: number;
};

const props = defineProps<{
    expenses: PaginatedExpenses;
    categoryTotals: CategoryTotal[];
}>();

const maxTotal = Math.max(
    ...props.categoryTotals.map((ct) => parseFloat(ct.total)),
    1,
);

function formatAmount(amount: string): string {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(amount));
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <AppLayout title="Historique">
        <template #header-right>
            <Button as-child size="icon" variant="ghost">
                <Link :href="index()">
                    <ArrowLeft :size="22" />
                </Link>
            </Button>
        </template>

        <Head title="Historique" />

        <div class="space-y-6 p-4">
            <!-- Totaux par catégorie -->
            <div v-if="categoryTotals.length > 0" class="space-y-3">
                <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                    Par catégorie
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="ct in categoryTotals"
                        :key="ct.category_id"
                        class="flex items-center gap-3"
                    >
                        <div class="flex w-20 items-center gap-2 shrink-0">
                            <CategoryIcon
                                :name="ct.category.icon"
                                :color="ct.category.color"
                                :size="16"
                            />
                            <span class="text-xs text-foreground truncate">{{ ct.category.name }}</span>
                        </div>
                        <div class="flex-1 h-5 rounded-full bg-muted overflow-hidden">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{
                                    width: (parseFloat(ct.total) / maxTotal * 100) + '%',
                                    backgroundColor: ct.category.color,
                                }"
                            />
                        </div>
                        <span class="text-xs font-medium text-foreground w-20 text-right shrink-0">
                            {{ formatAmount(ct.total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Liste des dépenses -->
            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wide">
                    Toutes les dépenses
                </h2>

                <div
                    v-for="expense in expenses.data"
                    :key="expense.id"
                    class="flex items-center gap-3 rounded-xl bg-card p-3 shadow-sm"
                    :class="expense.settled_at ? 'opacity-60' : ''"
                >
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                        :style="{ backgroundColor: expense.category.color + '20' }"
                    >
                        <CategoryIcon
                            :name="expense.category.icon"
                            :color="expense.category.color"
                            :size="14"
                        />
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-foreground">
                            {{ expense.description }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatDate(expense.date) }} &middot; {{ expense.payer.name }}
                        </p>
                    </div>

                    <div class="shrink-0 text-right">
                        <p class="text-sm font-semibold text-foreground">
                            {{ formatAmount(expense.amount) }}
                        </p>
                        <Badge v-if="expense.settled_at" variant="secondary" class="text-xs">
                            Réglé
                        </Badge>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="expenses.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in expenses.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm"
                        :class="link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'bg-muted text-muted-foreground hover:bg-muted/80'"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground/50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
