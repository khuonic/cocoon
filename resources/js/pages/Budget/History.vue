<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChevronLeft, ChevronRight } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import CategoryIcon from '@/components/budget/CategoryIcon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Expense, ExpenseCategory } from '@/types/budget';
import { history } from '@/actions/App/Http/Controllers/ExpenseController';

type CategoryTotal = {
    category_id: number;
    total: string;
    category: ExpenseCategory;
};

type Period = 'monthly' | 'annual' | 'total';

const props = defineProps<{
    expenses: Expense[];
    categoryTotals: CategoryTotal[];
    period: Period;
    currentMonth: string;
    totalAmount: string;
}>();

const maxTotal = computed(() =>
    Math.max(...props.categoryTotals.map((ct) => parseFloat(ct.total)), 1),
);

const prevMonth = computed(() => {
    const [year, month] = props.currentMonth.split('-').map(Number);
    const d = new Date(year, month - 2);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

const nextMonth = computed(() => {
    const [year, month] = props.currentMonth.split('-').map(Number);
    const d = new Date(year, month);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
});

const currentMonthLabel = computed(() => {
    const [year, month] = props.currentMonth.split('-').map(Number);
    return new Date(year, month - 1).toLocaleDateString('fr-FR', {
        month: 'long',
        year: 'numeric',
    });
});

const periods: { key: Period; label: string }[] = [
    { key: 'monthly', label: 'Mensuel' },
    { key: 'annual', label: 'Annuel' },
    { key: 'total', label: 'Total' },
];

function setPeriod(period: Period): void {
    router.get(history.url(), { period, month: props.currentMonth });
}

function navigateMonth(month: string): void {
    router.get(history.url(), { period: 'monthly', month });
}

function formatAmount(amount: string | number): string {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'EUR',
    }).format(parseFloat(String(amount)));
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
        <template #header-left>
            <BackButton href="/expenses" />
        </template>

        <Head title="Historique" />

        <div class="space-y-6 p-4">
            <!-- Filtres période -->
            <div class="flex gap-2">
                <button
                    v-for="p in periods"
                    :key="p.key"
                    class="flex-1 rounded-full px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="period === p.key
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground'"
                    @click="setPeriod(p.key)"
                >
                    {{ p.label }}
                </button>
            </div>

            <!-- Navigation mensuelle -->
            <div v-if="period === 'monthly'" class="flex items-center justify-between">
                <Button variant="ghost" size="icon" @click="navigateMonth(prevMonth)">
                    <ChevronLeft :size="20" />
                </Button>
                <span class="text-sm font-medium capitalize text-foreground">
                    {{ currentMonthLabel }}
                </span>
                <Button variant="ghost" size="icon" @click="navigateMonth(nextMonth)">
                    <ChevronRight :size="20" />
                </Button>
            </div>

            <!-- Total période -->
            <div class="rounded-xl bg-primary/10 p-4 text-center">
                <p class="text-xs text-muted-foreground uppercase tracking-wide">Total</p>
                <p class="text-2xl font-bold text-foreground">{{ formatAmount(totalAmount) }}</p>
                <p class="text-xs text-muted-foreground">{{ expenses.length }} dépense(s)</p>
            </div>

            <!-- Totaux par catégorie -->
            <div v-if="categoryTotals.length > 0" class="space-y-3">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Par catégorie
                </h2>
                <div class="space-y-2">
                    <div
                        v-for="ct in categoryTotals"
                        :key="ct.category_id"
                        class="flex items-center gap-3"
                    >
                        <div class="flex w-20 shrink-0 items-center gap-2">
                            <CategoryIcon
                                :name="ct.category.icon"
                                :color="ct.category.color"
                                :size="16"
                            />
                            <span class="truncate text-xs text-foreground">{{ ct.category.name }}</span>
                        </div>
                        <div class="h-5 flex-1 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full transition-all"
                                :style="{
                                    width: (parseFloat(ct.total) / maxTotal * 100) + '%',
                                    backgroundColor: ct.category.color,
                                }"
                            />
                        </div>
                        <span class="w-20 shrink-0 text-right text-xs font-medium text-foreground">
                            {{ formatAmount(ct.total) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Liste des dépenses -->
            <div v-if="expenses.length > 0" class="space-y-2">
                <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                    Dépenses
                </h2>

                <div
                    v-for="expense in expenses"
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

            <div v-else class="py-8 text-center text-sm text-muted-foreground">
                Aucune dépense sur cette période.
            </div>
        </div>
    </AppLayout>
</template>
