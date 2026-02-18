<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ArrowRight, Check } from 'lucide-vue-next';
import { ref } from 'vue';
import type { BalanceData } from '@/types/budget';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
    DialogClose,
} from '@/components/ui/dialog';
import { settle, history } from '@/actions/App/Http/Controllers/ExpenseController';

defineProps<{
    balance: BalanceData;
}>();

const open = ref(false);
const settling = ref(false);

function confirmSettle(): void {
    settling.value = true;
    router.post(settle.url(), {}, {
        onFinish: () => {
            settling.value = false;
            open.value = false;
        },
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
    <div
        class="rounded-xl p-4"
        :class="balance.is_settled
            ? 'bg-green-50 dark:bg-green-950/30'
            : 'bg-primary/10'"
    >

        <div v-if="balance.is_settled" class="flex items-center gap-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-500/20">
                <Check :size="18" class="text-green-600 dark:text-green-400" />
            </div>
            <p class="font-medium text-green-700 dark:text-green-300">Vous êtes quittes</p>
        </div>

        <div v-else class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary/20">
                    <ArrowRight :size="18" class="text-primary" />
                </div>
                <div>
                    <p class="font-medium text-foreground">
                        {{ balance.debtor?.name }} doit
                        <span class="font-bold">{{ formatAmount(balance.balance) }}</span>
                    </p>
                    <p class="text-xs text-muted-foreground">
                        à {{ balance.creditor?.name }}
                    </p>
                </div>
            </div>

            <Dialog v-model:open="open">
                <DialogTrigger as-child>
                    <Button size="sm" variant="outline">Régler</Button>
                </DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Règlement</DialogTitle>
                        <DialogDescription>
                            En confirmant, {{ balance.unsettled_count }} dépense(s) seront archivées
                            et la balance sera remise à zéro.
                        </DialogDescription>
                    </DialogHeader>
                    <div class="rounded-lg bg-muted p-4 text-center">
                        <p class="text-sm text-muted-foreground">Balance actuelle</p>
                        <p class="text-xl font-bold text-foreground">
                            {{ formatAmount(balance.balance) }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ balance.debtor?.name }} → {{ balance.creditor?.name }}
                        </p>
                    </div>
                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button variant="ghost">Annuler</Button>
                        </DialogClose>
                        <Button
                            @click="confirmSettle"
                            :disabled="settling"
                        >
                            Confirmer le règlement
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </div>

        <div class="mt-3 text-right">
            <Link
                :href="history.url()"
                class="text-xs text-muted-foreground underline underline-offset-2"
            >
                Voir l'historique →
            </Link>
        </div>
    </div>
</template>
