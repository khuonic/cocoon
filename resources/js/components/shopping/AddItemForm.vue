<script setup lang="ts">
import { ref, computed, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { Plus, X } from 'lucide-vue-next';
import { store, toggleCheck } from '@/actions/App/Http/Controllers/ShoppingItemController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { CategoryOption, ShoppingItem } from '@/types/shopping';
import { mobilePatch } from '@/lib/form-helpers';

const props = defineProps<{
    shoppingListId: number;
    categories: CategoryOption[];
    itemSuggestions: string[];
    checkedItems: ShoppingItem[];
}>();

const open = ref(false);
const name = ref('');
const category = ref('');
const processing = ref(false);
const inputRef = ref<InstanceType<typeof Input> | null>(null);

// ─── Autocomplete ────────────────────────────────────────────────────────────

const showSuggestions = ref(false);

const filteredSuggestions = computed(() => {
    const q = name.value.trim().toLowerCase();
    if (!q) return [];
    return props.itemSuggestions
        .filter((s) => s.toLowerCase().includes(q))
        .slice(0, 8);
});

function isCheckedInList(suggestion: string): ShoppingItem | undefined {
    return props.checkedItems.find(
        (item) => item.name.toLowerCase() === suggestion.toLowerCase(),
    );
}

function selectSuggestion(suggestion: string): void {
    const checkedItem = isCheckedInList(suggestion);
    if (checkedItem) {
        // Remettre dans la liste active (décocher)
        mobilePatch(toggleCheck.url(checkedItem.id), {}, { preserveScroll: true });
        name.value = '';
        showSuggestions.value = false;
        return;
    }
    name.value = suggestion;
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
    <!-- FAB ouvrir modal -->
    <button
        class="fixed z-40 flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform active:scale-95"
        style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px); right: 1rem"
        @click="openModal"
    >
        <Plus :size="24" />
    </button>

    <!-- Overlay + Modal bottom sheet -->
    <Transition name="fade">
        <div
            v-if="open"
            class="fixed inset-0 z-50 bg-black/40"
            @click="open = false"
        />
    </Transition>

    <Transition name="slide-up">
        <div
            v-if="open"
            class="fixed inset-x-0 bottom-0 z-50 rounded-t-2xl bg-card px-4 pb-8 pt-4 shadow-xl"
            :style="{ paddingBottom: 'max(2rem, calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 1rem))' }"
        >
            <!-- Handle bar -->
            <div class="mx-auto mb-4 h-1 w-12 rounded-full bg-muted-foreground/30" />

            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-base font-semibold">Ajouter un article</h2>
                <Button variant="ghost" size="icon" @click="open = false">
                    <X :size="18" />
                </Button>
            </div>

            <!-- Input + suggestions -->
            <div class="relative mb-3">
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

                <!-- Autocomplete dropdown -->
                <div
                    v-if="showSuggestions && filteredSuggestions.length > 0"
                    class="absolute left-0 right-0 top-full z-10 mt-1 overflow-hidden rounded-xl border border-border bg-card shadow-lg"
                >
                    <button
                        v-for="suggestion in filteredSuggestions"
                        :key="suggestion"
                        type="button"
                        class="flex w-full items-center gap-3 px-4 py-2.5 text-left text-sm transition-colors active:bg-muted"
                        :class="isCheckedInList(suggestion) ? 'text-muted-foreground' : 'text-foreground'"
                        @mousedown.prevent="selectSuggestion(suggestion)"
                    >
                        <span class="flex-1">{{ suggestion }}</span>
                        <span
                            v-if="isCheckedInList(suggestion)"
                            class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                        >
                            Coché — remettre
                        </span>
                    </button>
                </div>
            </div>

            <!-- Catégorie -->
            <select
                v-model="category"
                class="mb-4 h-10 w-full rounded-lg border border-input bg-background px-3 text-sm text-foreground"
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
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.25s ease;
}
.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
}
</style>