<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import AddItemForm from '@/components/shopping/AddItemForm.vue';
import CategoryGroup from '@/components/shopping/CategoryGroup.vue';
import ShoppingItemRow from '@/components/shopping/ShoppingItemRow.vue';
import { MoreVertical, Trash2, Copy, ChevronDown, Mic, MicOff, ArrowLeft } from 'lucide-vue-next';
import type { ShoppingList, ShoppingItem, CategoryOption } from '@/types/shopping';
import { destroy, duplicate } from '@/actions/App/Http/Controllers/ShoppingListController';
import { store } from '@/actions/App/Http/Controllers/ShoppingItemController';

const props = defineProps<{
    shoppingList: ShoppingList;
    uncheckedItemsByCategory: Record<string, ShoppingItem[]>;
    checkedItems: ShoppingItem[];
    categories: CategoryOption[];
}>();

const checkedOpen = ref(false);

const categoryLabels: Record<string, string> = {
    '': 'Sans catégorie',
};
props.categories.forEach((c) => {
    categoryLabels[c.value] = c.label;
});

const CATEGORY_BG: Record<string, string> = {
    'fruits_legumes': 'bg-green-50',
    'frais': 'bg-sky-50',
    'epicerie': 'bg-amber-50',
    'boissons': 'bg-cyan-50',
    'hygiene': 'bg-violet-50',
    'maison': 'bg-orange-50',
    'autre': 'bg-zinc-50',
    '': 'bg-slate-50',
};

onMounted(() => {
    localStorage.setItem('cocon_last_shopping_list_id', String(props.shoppingList.id));
});

function handleDelete(): void {
    router.delete(destroy.url(props.shoppingList.id));
}

function handleDuplicate(): void {
    router.post(duplicate.url(props.shoppingList.id));
}

// ─── FAB vocal ─────────────────────────────────────────────────────────────

const SpeechRecognitionAPI =
    (window as any).SpeechRecognition ?? (window as any).webkitSpeechRecognition ?? null;

console.log('[Micro] SpeechRecognition API:', SpeechRecognitionAPI ? 'disponible' : 'ABSENT');

const speechSupported = SpeechRecognitionAPI !== null;
const isListening = ref(false);
let recognition: any = null;

function toggleListening(): void {
    console.log('[Micro] toggleListening — isListening:', isListening.value);

    if (isListening.value) {
        console.log('[Micro] stop()');
        recognition?.stop();
        return;
    }

    console.log('[Micro] Création SpeechRecognition, lang=fr-FR');
    recognition = new SpeechRecognitionAPI();
    recognition.lang = 'fr-FR';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
        console.log('[Micro] onstart — micro actif');
        isListening.value = true;
    };

    recognition.onresult = (event: SpeechRecognitionEvent) => {
        const transcript = event.results[0][0].transcript;
        const confidence = event.results[0][0].confidence;
        console.log('[Micro] onresult — transcript:', transcript, 'confidence:', confidence);
        router.post(store.url(props.shoppingList.id), { name: transcript, category: null }, { preserveScroll: true });
    };

    recognition.onerror = (event: any) => {
        console.error('[Micro] onerror — code:', event.error, 'message:', event.message);
        isListening.value = false;
    };

    recognition.onend = () => {
        console.log('[Micro] onend — micro arrêté');
        isListening.value = false;
    };

    console.log('[Micro] start()');
    recognition.start();
}

onUnmounted(() => {
    recognition?.stop();
});

function goBack(): void {
    sessionStorage.setItem('shopping_no_redirect', '1');
    router.visit('/shopping-lists');
}
</script>

<template>
    <AppLayout :title="shoppingList.name">
        <template #header-left>
            <Button variant="ghost" size="icon-xl" @click="goBack">
                <ArrowLeft :size="22" />
            </Button>
        </template>
        <template #header-right>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <Button variant="ghost" size="icon-xl">
                        <MoreVertical :size="20" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem v-if="shoppingList.is_template" @click="handleDuplicate">
                        <Copy :size="16" class="mr-2" />
                        Dupliquer
                    </DropdownMenuItem>
                    <DropdownMenuItem class="text-destructive" @click="handleDelete">
                        <Trash2 :size="16" class="mr-2" />
                        Supprimer la liste
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </template>

        <Head :title="shoppingList.name" />

        <AddItemForm
            :shopping-list-id="shoppingList.id"
            :categories="categories"
        />

        <div class="space-y-4 p-4 pb-28">
            <template v-for="(items, category) in uncheckedItemsByCategory" :key="category">
                <CategoryGroup :label="categoryLabels[category as string] ?? String(category)">
                    <ShoppingItemRow
                        v-for="item in items"
                        :key="item.id"
                        :item="item"
                        :categories="categories"
                        :bg-class="CATEGORY_BG[category as string] ?? 'bg-card'"
                    />
                </CategoryGroup>
            </template>

            <Collapsible v-if="checkedItems.length > 0" v-model:open="checkedOpen">
                <CollapsibleTrigger class="flex w-full items-center gap-2 rounded-lg px-1 py-2 text-sm text-muted-foreground">
                    <ChevronDown
                        :size="16"
                        class="transition-transform"
                        :class="checkedOpen ? 'rotate-0' : '-rotate-90'"
                    />
                    Articles cochés ({{ checkedItems.length }})
                </CollapsibleTrigger>
                <CollapsibleContent>
                    <div class="grid grid-cols-3 gap-2">
                        <ShoppingItemRow
                            v-for="item in checkedItems"
                            :key="item.id"
                            :item="item"
                            :categories="categories"
                        />
                    </div>
                </CollapsibleContent>
            </Collapsible>
        </div>

        <!-- FAB vocal flottant -->
        <button
            v-if="speechSupported"
            class="fixed right-4 z-40 flex size-14 items-center justify-center rounded-full shadow-lg transition-transform active:scale-95"
            :class="isListening ? 'animate-pulse bg-red-500 text-white' : 'bg-primary text-primary-foreground'"
            style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px)"
            @click="toggleListening"
        >
            <MicOff v-if="isListening" :size="24" />
            <Mic v-else :size="24" />
        </button>
    </AppLayout>
</template>
