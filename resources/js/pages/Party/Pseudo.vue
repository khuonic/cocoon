<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PixelSprite from '@/components/party/PixelSprite.vue';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { store } from '@/routes/party/player';

const props = defineProps<{ pseudo: string | null }>();

const form = useForm({ pseudo: props.pseudo ?? '' });

function submit(): void {
    form.post(store().url);
}
</script>

<template>
    <PartyLayout title="Ton pseudo">
        <div
            class="flex flex-col items-center gap-6 px-5 pt-10 pb-10 text-center"
        >
            <PixelSprite name="sage" :px="5" />
            <h1
                class="font-pixel m-0 text-base leading-relaxed"
                style="color: var(--px-gold)"
            >
                {{ pseudo ? 'Changer de pseudo' : 'Qui es-tu ?' }}
            </h1>
            <p class="m-0 text-xl leading-snug">
                Ton pseudo apparaîtra au classement. Tu pourras rejouer autant
                que tu veux.
            </p>

            <form
                class="flex w-full flex-col gap-6 pt-2 text-left"
                @submit.prevent="submit"
            >
                <label for="party-pseudo" class="font-pixel text-[11px]"
                    >PSEUDO</label
                >
                <input
                    id="party-pseudo"
                    v-model="form.pseudo"
                    type="text"
                    class="px-input"
                    maxlength="20"
                    autocomplete="nickname"
                    required
                />
                <p v-if="form.errors.pseudo" class="px-error m-0">
                    {{ form.errors.pseudo }}
                </p>
                <button
                    type="submit"
                    class="px-btn px-btn-gold"
                    :disabled="form.processing"
                >
                    {{ pseudo ? 'ENREGISTRER' : "C'EST PARTI ▶" }}
                </button>
            </form>
        </div>
    </PartyLayout>
</template>
