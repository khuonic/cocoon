<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Mic, MicOff, Plus } from 'lucide-vue-next';
import { onUnmounted, ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/ShoppingItemController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { CategoryOption } from '@/types/shopping';

const props = defineProps<{
    shoppingListId: number;
    categories: CategoryOption[];
}>();

const form = useForm({
    name: '',
    category: 'autre',
});

function submit(): void {
    form.post(store.url(props.shoppingListId), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
        },
    });
}

// --- Speech recognition ---

const SpeechRecognitionAPI =
    (window as any).SpeechRecognition ?? (window as any).webkitSpeechRecognition ?? null;

const speechSupported = SpeechRecognitionAPI !== null;
const isListening = ref(false);
let recognition: any = null;

function toggleListening(): void {
    if (isListening.value) {
        recognition?.stop();
        return;
    }

    recognition = new SpeechRecognitionAPI();
    recognition.lang = 'fr-FR';
    recognition.interimResults = false;
    recognition.maxAlternatives = 1;

    recognition.onstart = () => {
        isListening.value = true;
    };

    recognition.onresult = (event: SpeechRecognitionEvent) => {
        form.name = event.results[0][0].transcript;
    };

    recognition.onerror = () => {
        isListening.value = false;
    };

    recognition.onend = () => {
        isListening.value = false;
    };

    recognition.start();
}

onUnmounted(() => {
    recognition?.stop();
});
</script>

<template>
    <form @submit.prevent="submit" class="sticky top-0 z-30 border-b border-border bg-card p-3 backdrop-blur-sm">
        <div class="flex gap-2">
            <Input
                v-model="form.name"
                type="text"
                placeholder="Ajouter un article..."
                class="flex-1"
                autofocus
            />
            <select
                v-model="form.category"
                class="h-9 rounded-md border border-input bg-background px-2 text-sm text-foreground"
            >
                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                    {{ cat.label }}
                </option>
            </select>
            <Button
                v-if="speechSupported"
                type="button"
                size="icon"
                variant="outline"
                :class="{ 'animate-pulse border-red-400 text-red-500': isListening }"
                @click="toggleListening"
            >
                <MicOff v-if="isListening" :size="18" />
                <Mic v-else :size="18" />
            </Button>
            <Button type="submit" size="icon" :disabled="form.processing || !form.name">
                <Plus :size="18" />
            </Button>
        </div>
        <InputError :message="form.errors.name" />
        <InputError :message="form.errors.category" />
    </form>
</template>
