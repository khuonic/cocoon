<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { store, toggleCheck } from '@/actions/App/Http/Controllers/ShoppingItemController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import type { CategoryOption, ShoppingItem } from '@/types/shopping';
import { mobilePatch } from '@/lib/form-helpers';

const props = defineProps<{
    shoppingListId: number;
    categories: CategoryOption[];
    itemSuggestions: string[];
    checkedItems: ShoppingItem[];
    uncheckedItems: ShoppingItem[];
}>();

const open = ref(false);
const name = ref('');
const category = ref('');
const processing = ref(false);
const inputRef = ref<InstanceType<typeof Input> | null>(null);

// ─── Autocomplete ────────────────────────────────────────────────────────────

const showSuggestions = ref(false);

type SuggestionKind = 'checked' | 'unchecked' | 'history';

interface Suggestion {
    name: string;
    kind: SuggestionKind;
    item?: ShoppingItem;
}

const filteredSuggestions = computed((): Suggestion[] => {
    const q = name.value.trim().toLowerCase();
    if (!q) return [];

    return props.itemSuggestions
        .filter((s) => s.toLowerCase().includes(q))
        .slice(0, 8)
        .map((s) => {
            const checked = props.checkedItems.find(
                (i) => i.name.toLowerCase() === s.toLowerCase(),
            );
            if (checked) return { name: s, kind: 'checked', item: checked };

            const unchecked = props.uncheckedItems.find(
                (i) => i.name.toLowerCase() === s.toLowerCase(),
            );
            if (unchecked) return { name: s, kind: 'unchecked', item: unchecked };

            return { name: s, kind: 'history' };
        });
});

function selectSuggestion(suggestion: Suggestion): void {
    if (suggestion.kind === 'unchecked') return;

    if (suggestion.kind === 'checked' && suggestion.item) {
        mobilePatch(toggleCheck.url(suggestion.item.id), {}, { preserveScroll: true });
        name.value = '';
        showSuggestions.value = false;
        return;
    }

    name.value = suggestion.name;
    showSuggestions.value = false;
    nextTick(() => submit());
}

// ─── Form ────────────────────────────────────────────────────────────────────

function openModal(): void {
    open.value = true;
    nextTick(() => {
        (inputRef.value as any)?.$el?.focus();
    });
}

function submit(): void {
    const trimmed = name.value.trim();
    if (!trimmed || processing.value) return;

    processing.value = true;
    router.post(
        store.url(props.shoppingListId),
        { name: trimmed, category: category.value || null },
        {
            preserveScroll: true,
            onSuccess: () => {
                name.value = '';
                showSuggestions.value = false;
                processing.value = false;
                nextTick(() => (inputRef.value as any)?.$el?.focus());
            },
            onError: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <!-- FAB -->
    <button
        class="fixed z-40 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform active:scale-95"
        style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px); right: 1rem"
        @click="openModal"
    >
        <Plus :size="24" />
    </button>

    <!-- Dialog (positionné top-4 via DialogContent) -->
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Ajouter un article</DialogTitle>
            </DialogHeader>

            <!-- Input -->
            <Input
                ref="inputRef"
                v-model="name"
                type="text"
                placeholder="Nom de l'article..."
                enterkeyhint="done"
                autocomplete="off"
                @focus="showSuggestions = true"
                @blur="setTimeout(() => (showSuggestions = false), 150)"
                @keydown.enter.prevent="submit"
            />

            <!-- Suggestions (en flux, max-h + scroll) -->
            <div
                v-if="showSuggestions && filteredSuggestions.length > 0"
                class="max-h-48 overflow-y-auto rounded-xl border border-border bg-card shadow-sm"
            >
                <button
                    v-for="suggestion in filteredSuggestions"
                    :key="suggestion.name"
                    type="button"
                    class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm"
                    :class="suggestion.kind === 'unchecked'
                        ? 'cursor-default text-muted-foreground/50'
                        : 'text-foreground transition-colors active:bg-muted'"
                    @mousedown.prevent="selectSuggestion(suggestion)"
                >
                    <span class="flex-1">{{ suggestion.name }}</span>
                    <span
                        v-if="suggestion.kind === 'unchecked'"
                        class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                    >
                        déjà listé
                    </span>
                </button>
            </div>

            <!-- Catégorie -->
            <select
                v-model="category"
                class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
            >
                <option value="">Sans catégorie</option>
                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                    {{ cat.icon }} {{ cat.label }}
                </option>
            </select>

            <Button
                class="w-full"
                :disabled="!name.trim() || processing"
                @click="submit"
            >
                <Plus :size="16" class="mr-2" />
                Ajouter
            </Button>
        </DialogContent>
    </Dialog>
</template>