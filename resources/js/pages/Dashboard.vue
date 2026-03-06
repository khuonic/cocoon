<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Heart, Send } from 'lucide-vue-next';
import JokeWidget from '@/components/dashboard/JokeWidget.vue';
import SweetMessageWidget from '@/components/dashboard/SweetMessageWidget.vue';
import TodayWidget from '@/components/dashboard/TodayWidget.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from '@/components/ui/dialog';
import { store } from '@/actions/App/Http/Controllers/SweetMessageController';
import type { SweetMessage } from '@/types/sweet-message';

type TodayItem = {
    type: 'event' | 'birthday';
    title: string;
    time: string | null;
    color: string;
    age?: number;
};

const props = defineProps<{
    sweetMessage: SweetMessage | null;
    mySweetMessage: SweetMessage | null;
    todayItems: TodayItem[];
    todayItemsCount: number;
    joke: { id: number; content: string } | null;
}>();

const showSweetForm = ref(false);

const form = useForm({
    content: props.mySweetMessage?.content ?? '',
});

function openSweetForm(): void {
    form.content = props.mySweetMessage?.content ?? '';
    form.clearErrors();
    showSweetForm.value = true;
}

function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => { showSweetForm.value = false; },
    });
}
</script>

<template>
    <Head title="Accueil" />

    <AppLayout title="Cocon">
        <div class="space-y-4 p-4 pb-28">
            <SweetMessageWidget :sweet-message="sweetMessage" />

            <TodayWidget :items="todayItems" :total-count="todayItemsCount" />

            <JokeWidget :joke="joke" />
        </div>

        <!-- FAB cœur -->
        <button
            class="fixed right-4 z-40 flex size-14 items-center justify-center rounded-full shadow-lg transition-transform active:scale-95"
            :class="mySweetMessage ? 'bg-pink-500 text-white' : 'bg-pink-100 text-pink-500'"
            style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px)"
            @click="openSweetForm"
        >
            <Heart :size="24" :class="mySweetMessage ? 'fill-white' : ''" />
        </button>

        <!-- Dialog mot doux -->
        <Dialog v-model:open="showSweetForm">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{ mySweetMessage ? 'Modifier ton mot doux' : 'Écrire un mot doux' }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4">
                    <Textarea
                        v-model="form.content"
                        placeholder="Un petit mot pour ton/ta partenaire..."
                        rows="4"
                        autofocus
                    />
                    <InputError :message="form.errors.content" />

                    <DialogFooter>
                        <Button type="button" variant="outline" @click="showSweetForm = false">
                            Annuler
                        </Button>
                        <Button type="submit" :disabled="form.processing">
                            <Send :size="14" />
                            Envoyer
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
