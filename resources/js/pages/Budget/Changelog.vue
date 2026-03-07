<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';
import AppLayout from '@/layouts/AppLayout.vue';
import BackButton from '@/components/BackButton.vue';
import type { User } from '@/types/auth';

type ActivityProperties = {
    attributes?: Record<string, unknown>;
    old?: Record<string, unknown>;
};

type Activity = {
    id: number;
    description: string;
    event: string | null;
    properties: ActivityProperties;
    causer: User | null;
    created_at: string;
};

defineProps<{
    activities: Activity[];
}>();

const FIELD_LABELS: Record<string, string> = {
    description: 'Description',
    amount: 'Montant',
    date: 'Date',
    category_id: 'Catégorie',
    split_type: 'Répartition',
    paid_by: 'Payé par',
    is_recurring: 'Récurrent',
};

const SPLIT_LABELS: Record<string, string> = {
    equal: 'Moitié-moitié',
    full_payer: 'Perso',
    full_other: '100% l\'autre',
    custom: 'Custom',
};

function formatValue(key: string, value: unknown): string {
    if (value === null || value === undefined) return '–';
    if (key === 'amount') return `${parseFloat(String(value)).toFixed(2)} €`;
    if (key === 'split_type') return SPLIT_LABELS[String(value)] ?? String(value);
    if (key === 'is_recurring') return value ? 'Oui' : 'Non';
    if (key === 'date') return new Date(String(value)).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });
    return String(value);
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function changedFields(activity: Activity): { key: string; label: string; old: unknown; new: unknown }[] {
    const attrs = activity.properties.attributes ?? {};
    const old = activity.properties.old ?? {};
    return Object.keys(attrs)
        .filter((k) => k in FIELD_LABELS)
        .map((k) => ({ key: k, label: FIELD_LABELS[k], old: old[k], new: attrs[k] }));
}
</script>

<template>
    <AppLayout title="Journal des modifications">
        <template #header-left>
            <BackButton href="/expenses/history" />
        </template>

        <Head title="Journal des modifications" />

        <div class="space-y-3 p-4">
            <div v-if="activities.length === 0" class="py-12 text-center text-sm text-muted-foreground">
                Aucune modification enregistrée.
            </div>

            <div
                v-for="activity in activities"
                :key="activity.id"
                class="rounded-xl border border-border bg-card p-4 space-y-3"
            >
                <!-- En-tête -->
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <div
                            class="flex size-7 shrink-0 items-center justify-center rounded-full"
                            :class="{
                                'bg-emerald-100 text-emerald-600': activity.event === 'created',
                                'bg-amber-100 text-amber-600': activity.event === 'updated',
                                'bg-red-100 text-red-600': activity.event === 'deleted',
                            }"
                        >
                            <Plus v-if="activity.event === 'created'" :size="14" />
                            <Pencil v-else-if="activity.event === 'updated'" :size="14" />
                            <Trash2 v-else :size="14" />
                        </div>
                        <p class="text-sm font-semibold text-foreground truncate max-w-[160px]">
                            {{ activity.description }}
                        </p>
                    </div>
                    <p class="shrink-0 text-xs text-muted-foreground">
                        {{ formatDate(activity.created_at) }}
                    </p>
                </div>

                <!-- Champs modifiés -->
                <div v-if="changedFields(activity).length > 0" class="space-y-1.5">
                    <div
                        v-for="field in changedFields(activity)"
                        :key="field.key"
                        class="flex items-center gap-2 text-xs"
                    >
                        <span class="w-20 shrink-0 text-muted-foreground">{{ field.label }}</span>
                        <template v-if="field.old !== undefined">
                            <span class="rounded bg-red-50 px-1.5 py-0.5 text-red-600 line-through">
                                {{ formatValue(field.key, field.old) }}
                            </span>
                            <span class="text-muted-foreground">→</span>
                        </template>
                        <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700">
                            {{ formatValue(field.key, field.new) }}
                        </span>
                    </div>
                </div>

                <p v-if="activity.causer" class="text-xs text-muted-foreground">
                    par {{ activity.causer.name }}
                </p>
            </div>
        </div>
    </AppLayout>
</template>
