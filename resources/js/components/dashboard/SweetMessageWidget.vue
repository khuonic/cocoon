<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Heart, Send } from 'lucide-vue-next';
import { ref } from 'vue';
import { store } from '@/actions/App/Http/Controllers/SweetMessageController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import type { SweetMessage } from '@/types/sweet-message';

const props = defineProps<{
    sweetMessage: SweetMessage | null;
    mySweetMessage: SweetMessage | null;
}>();

const isEditing = ref(false);

const form = useForm({
    content: props.mySweetMessage?.content ?? '',
});

function submit(): void {
    form.post(store.url(), {
        preserveScroll: true,
        onSuccess: () => { isEditing.value = false; },
    });
}
</script>

<template>
    <div class="rounded-xl bg-card p-4 shadow-sm">
        <!-- Partner's message -->
        <div v-if="sweetMessage" class="mb-3">
            <div class="flex items-center gap-2 text-xs font-medium text-pink-500">
                <Heart :size="14" class="fill-pink-500" />
                Mot doux
            </div>
            <p class="mt-1 text-sm text-foreground italic">"{{ sweetMessage.content }}"</p>
        </div>
        <div v-else class="mb-3">
            <div class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                <Heart :size="14" />
                Pas encore de mot doux
            </div>
        </div>

        <!-- My message form -->
        <div class="border-t border-border pt-3">
            <template v-if="isEditing">
                <form @submit.prevent="submit" class="space-y-2">
                    <Textarea
                        v-model="form.content"
                        placeholder="Ecris un mot pour ton/ta partenaire..."
                        rows="2"
                        class="text-sm"
                    />
                    <InputError :message="form.errors.content" />
                    <div class="flex justify-end gap-2">
                        <Button type="button" variant="ghost" size="sm" @click="isEditing = false">
                            Annuler
                        </Button>
                        <Button type="submit" size="sm" :disabled="form.processing">
                            <Send :size="14" class="mr-1" />
                            Envoyer
                        </Button>
                    </div>
                </form>
            </template>
            <template v-else>
                <button
                    class="w-full text-left text-xs text-muted-foreground hover:text-foreground"
                    @click="isEditing = true"
                >
                    {{ mySweetMessage ? 'Modifier ton mot doux...' : 'Ecrire un mot doux...' }}
                </button>
            </template>
        </div>
    </div>
</template>
