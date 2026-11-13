<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import PixelSprite from '@/components/party/PixelSprite.vue';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { store } from '@/routes/party/enter';

const form = useForm({ password: '' });

function submit(): void {
    form.post(store().url, { onFinish: () => form.reset('password') });
}
</script>

<template>
    <PartyLayout title="Entrée">
        <div
            class="flex flex-col items-center gap-6 px-5 pt-12 pb-10 text-center"
        >
            <PixelSprite name="gateau" :px="7" />
            <h1
                class="font-pixel m-0 text-lg leading-relaxed"
                style="color: var(--px-gold)"
            >
                Fête privée
            </h1>
            <p class="m-0 text-xl leading-snug">
                Scanne le QR code de l'invitation, ou entre le mot de passe de
                la soirée.
            </p>

            <form
                class="flex w-full flex-col gap-6 pt-2 text-left"
                @submit.prevent="submit"
            >
                <label for="party-password" class="font-pixel text-[11px]"
                    >MOT DE PASSE</label
                >
                <input
                    id="party-password"
                    v-model="form.password"
                    type="password"
                    class="px-input"
                    autocomplete="current-password"
                    required
                />
                <p v-if="form.errors.password" class="px-error m-0">
                    {{ form.errors.password }}
                </p>
                <button
                    type="submit"
                    class="px-btn px-btn-gold"
                    :disabled="form.processing"
                >
                    ENTRER ▶
                </button>
            </form>
        </div>
    </PartyLayout>
</template>
