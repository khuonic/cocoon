<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDuration } from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import { show as showGame } from '@/routes/party/games';

defineProps<{
    game: { slug: string; title: string };
    run: {
        uuid: string;
        score: number;
        max_score: number;
        duration_ms: number;
    };
    rank: number;
    totalPlayers: number;
    verdict: string;
    recap: { key: string; context: string; answer: string; correct: boolean }[];
}>();
</script>

<template>
    <PartyLayout title="Résultat">
        <div
            class="relative h-[300px] overflow-hidden"
            style="background: var(--px-gold)"
        >
            <div
                class="absolute top-0 left-6 h-[250px] w-7"
                style="background: var(--px-gold-dark)"
            />
            <div
                class="absolute top-0 right-6 h-[250px] w-7"
                style="background: var(--px-gold-dark)"
            />
            <p
                class="font-pixel absolute inset-x-0 top-6 m-0 text-center text-[15px]"
                style="color: var(--px-ink)"
            >
                FIN DU VOYAGE
            </p>
            <div
                class="absolute inset-x-0 bottom-0 h-14"
                style="
                    background: var(--px-plum);
                    border-top: 8px solid #4a3b55;
                "
            />
            <div
                class="px-walk-in absolute bottom-14 left-1/2 -translate-x-1/2"
            >
                <PixelSprite name="sage" :px="7" />
            </div>
        </div>

        <div class="flex flex-col gap-6 px-4 pt-6 pb-10">
            <div class="grid grid-cols-3 gap-2.5 text-center">
                <div
                    class="flex flex-col gap-2.5 px-1 py-3.5"
                    style="background: var(--px-ink)"
                >
                    <span class="text-[15px]" style="color: var(--px-muted)"
                        >Score</span
                    >
                    <span
                        class="font-pixel text-lg"
                        style="color: var(--px-gold)"
                        >{{ run.score }}/{{ run.max_score }}</span
                    >
                </div>
                <div
                    class="flex flex-col gap-2.5 px-1 py-3.5"
                    style="background: var(--px-ink)"
                >
                    <span class="text-[15px]" style="color: var(--px-muted)"
                        >Temps</span
                    >
                    <span class="font-pixel text-lg">{{
                        formatDuration(run.duration_ms)
                    }}</span>
                </div>
                <div
                    class="flex flex-col gap-2.5 px-1 py-3.5"
                    style="background: var(--px-ink)"
                >
                    <span class="text-[15px]" style="color: var(--px-muted)"
                        >Rang</span
                    >
                    <span
                        class="font-pixel text-lg"
                        style="color: var(--px-sky)"
                        >#{{ rank }}</span
                    >
                    <span class="text-sm" style="color: var(--px-muted)"
                        >sur {{ totalPlayers }}</span
                    >
                </div>
            </div>

            <div class="px-dialog mt-2">
                <span class="px-dialog-tag">VERDICT</span>
                <p class="m-0 text-xl leading-snug">{{ verdict }}</p>
            </div>

            <ol class="m-0 flex list-none flex-col gap-2 p-0">
                <li
                    v-for="step in recap"
                    :key="step.key"
                    class="flex items-start gap-3 px-3 py-2.5"
                    style="background: var(--px-ink)"
                >
                    <span
                        class="font-pixel flex size-7 flex-none items-center justify-center text-[11px]"
                        :style="{
                            background: step.correct
                                ? 'var(--px-teal)'
                                : 'var(--px-coral)',
                            color: 'var(--px-ink)',
                        }"
                        :aria-label="step.correct ? 'Trouvé' : 'Raté'"
                    >
                        {{ step.correct ? '✔' : '✘' }}
                    </span>
                    <span class="flex flex-col gap-0.5">
                        <span
                            class="text-[15px]"
                            style="color: var(--px-muted)"
                            >{{ step.context }}</span
                        >
                        <span class="text-[17px] leading-tight">{{
                            step.answer
                        }}</span>
                    </span>
                </li>
            </ol>

            <div class="grid grid-cols-2 gap-4 pt-2">
                <Link :href="showGame(game.slug)" class="px-btn px-btn-gold"
                    >REJOUER</Link
                >
                <Link
                    :href="leaderboard({ query: { game: game.slug } })"
                    class="px-btn px-btn-cream"
                    >CLASSEMENT</Link
                >
            </div>
        </div>
    </PartyLayout>
</template>
