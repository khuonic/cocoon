<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { nextTick, onUnmounted, ref, useTemplateRef } from 'vue';
import PixelSprite from '@/components/party/PixelSprite.vue';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { home } from '@/routes/party';
import { voice } from '@/routes/party/secret';

defineProps<{ available: boolean }>();

const audio = useTemplateRef<HTMLAudioElement>('audio');
const audioUrl = ref<string | null>(null);
const isLoading = ref(false);
const error = ref<string | null>(null);

/**
 * Le vocal est téléchargé en entier puis lu depuis un blob :
 * pas de dépendance au streaming par plages (Range) côté serveur, le fichier est court.
 */
async function listen(): Promise<void> {
    if (audioUrl.value) {
        await audio.value?.play();

        return;
    }

    isLoading.value = true;
    error.value = null;

    try {
        const response = await fetch(voice().url, {
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error();
        }

        audioUrl.value = URL.createObjectURL(await response.blob());
        await nextTick();
        await audio.value?.play();
    } catch {
        error.value = 'Le vocal ne veut pas sortir. Réessaie.';
    } finally {
        isLoading.value = false;
    }
}

onUnmounted(() => {
    if (audioUrl.value) {
        URL.revokeObjectURL(audioUrl.value);
    }
});
</script>

<template>
    <PartyLayout title="Le secret d'Antoine">
        <div
            class="flex flex-col items-center gap-6 px-5 pt-10 pb-10 text-center"
        >
            <PixelSprite name="sage" :px="5" decorative />
            <h1
                class="font-pixel m-0 text-base leading-relaxed"
                style="color: var(--px-gold)"
            >
                Le secret<br />d'Antoine
            </h1>
            <p class="m-0 text-xl leading-snug">
                Voici un document d'archive rare, enregistré dans un état…
                niquel.
            </p>

            <template v-if="available">
                <button
                    type="button"
                    class="px-btn px-btn-gold w-full"
                    :disabled="isLoading"
                    @click="listen"
                >
                    {{ isLoading ? 'CHARGEMENT…' : 'LANCER LE VOCAL ▶' }}
                </button>
                <audio
                    v-if="audioUrl"
                    ref="audio"
                    :src="audioUrl"
                    controls
                    class="w-full"
                />
                <p v-if="error" class="px-error m-0" role="alert">
                    {{ error }}
                </p>
            </template>
            <p v-else class="m-0 text-lg" style="color: var(--px-muted)">
                Le vocal arrive bientôt, reviens plus tard.
            </p>

            <Link :href="home()" class="px-btn px-btn-cream w-full"
                >LES JEUX</Link
            >
        </div>
    </PartyLayout>
</template>
