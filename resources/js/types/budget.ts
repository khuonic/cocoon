import type { User } from './auth';

export type ExpenseCategory = {
    id: number;
    name: string;
    icon: string;
    color: string;
    sort_order: number;
};

export type Expense = {
    id: number;
    amount: string;
    description: string;
    category_id: number;
    paid_by: number;
    split_type: 'equal' | 'full_payer' | 'full_other' | 'custom';
    split_value: string | null;
    date: string;
    is_recurring: boolean;
    recurrence_type: 'daily' | 'weekly' | 'monthly' | 'yearly' | null;
    settled_at: string | null;
    uuid: string;
    created_at: string;
    updated_at: string;
    category: ExpenseCategory;
    payer: User;
};

export type BalanceData = {
    balance: string;
    creditor: User | null;
    debtor: User | null;
    is_settled: boolean;
    unsettled_count: number;
};
