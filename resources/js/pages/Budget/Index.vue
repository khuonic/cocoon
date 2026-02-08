<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Wallet } from 'lucide-vue-next';
import type { Expense, BalanceData } from '@/types/budget';
import type { User } from '@/types/auth';
import AppLayout from '@/layouts/AppLayout.vue';
import EmptyState from '@/components/EmptyState.vue';
import BalanceBanner from '@/components/budget/BalanceBanner.vue';
import ExpenseCard from '@/components/budget/ExpenseCard.vue';
import { Button } from '@/components/ui/button';
import { create } from '@/routes/expenses';
import { history } from '@/actions/App/Http/Controllers/ExpenseController';

defineProps<{
    expenses: Expense[];
    balance: BalanceData;
    users: User[];
}>();
</script>

<template>
    <AppLayout title="Budget">
        <template #header-right>
            <Button as-child size="icon" variant="ghost">
                <Link :href="create()">
                    <Plus :size="22" />
                </Link>
            </Button>
        </template>

        <Head title="Budget" />

        <div class="space-y-4 p-4">
            <BalanceBanner :balance="balance" />

            <EmptyState
                v-if="expenses.length === 0"
                title="Aucune dépense"
                description="Ajoute ta première dépense pour commencer à suivre votre budget."
                :icon="Wallet"
            >
                <template #action>
                    <Button as-child>
                        <Link :href="create()">Ajouter une dépense</Link>
                    </Button>
                </template>
            </EmptyState>

            <div v-else class="space-y-2">
                <ExpenseCard
                    v-for="expense in expenses"
                    :key="expense.id"
                    :expense="expense"
                />
            </div>

            <div v-if="expenses.length > 0" class="pt-2 text-center">
                <Link
                    :href="history.url()"
                    class="text-sm text-muted-foreground underline underline-offset-4"
                >
                    Voir l'historique complet
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
