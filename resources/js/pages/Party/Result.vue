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

const festiveColors = ['#E4572E', '#2A9D8F', '#F2B134', '#8ECAE6', '#F7A8B8'];

/** Ampoules de la guirlande, réparties sur la largeur et qui clignotent en décalé. */
const bulbs = Array.from({ length: 8 }, (_, index) => ({
    left: `${3 + index * 12.5}%`,
    color: festiveColors[index % 4],
    delay: `${(index % 3) * 330}ms`,
}));

/** Confettis qui tombent en boucle, positions et vitesses variées. */
const confetti = Array.from({ length: 14 }, (_, index) => ({
    left: `${(index * 37) % 100}%`,
    color: festiveColors[index % festiveColors.length],
    delay: `${(index * 410) % 3000}ms`,
    duration: `${2600 + ((index * 530) % 1800)}ms`,
}));
</script>

<template>
    <PartyLayout title="Résultat">
        <div
            class="relative h-[340px] overflow-hidden"
            style="background: #2e2e4a"
        >
            <p
                class="font-pixel absolute inset-x-0 top-3 m-0 text-center text-[10px]"
                style="color: var(--px-muted)"
            >
                FIN DU VOYAGE
            </p>

            <!-- Guirlande -->
            <div
                class="absolute inset-x-0 top-[38px] h-1"
                style="background: #4a4e69"
                aria-hidden="true"
            />
            <div
                v-for="(bulb, index) in bulbs"
                :key="`bulb-${index}`"
                class="px-blink absolute top-[42px] h-3 w-2.5"
                :style="{
                    left: bulb.left,
                    background: bulb.color,
                    animationDelay: bulb.delay,
                }"
                aria-hidden="true"
            />

            <p
                class="font-pixel px-border absolute top-[72px] left-1/2 m-0 -translate-x-1/2 px-3.5 py-2.5 text-sm whitespace-nowrap"
                style="background: var(--px-plum); color: var(--px-gold)"
            >
                JOYEUX 35 ANS !
            </p>

            <!-- Ballons -->
            <div class="absolute top-[120px] left-[18px]">
                <PixelSprite name="ballon_rouge" :px="5" decorative />
            </div>
            <div class="absolute top-[140px] left-[58px]">
                <PixelSprite name="ballon_bleu" :px="4" decorative />
            </div>
            <div class="absolute top-[112px] right-5">
                <PixelSprite name="ballon_bleu" :px="5" decorative />
            </div>

            <!-- Confettis -->
            <div
                v-for="(confetto, index) in confetti"
                :key="`confetto-${index}`"
                class="px-confetti absolute -top-2 size-1.5"
                :style="{
                    left: confetto.left,
                    background: confetto.color,
                    animationDelay: confetto.delay,
                    animationDuration: confetto.duration,
                }"
                aria-hidden="true"
            />

            <!-- Parquet, table et gâteau -->
            <div
                class="absolute inset-x-0 bottom-0 h-[50px]"
                style="background: #8b5a3c; border-top: 8px solid #6b4433"
            />
            <div
                class="absolute right-[22px] bottom-[50px] h-[34px] w-[76px]"
                style="
                    background: var(--px-paper);
                    border-top: 6px solid #e3d7b6;
                "
            />
            <div class="absolute right-[30px] bottom-[84px]">
                <PixelSprite name="gateau" :px="5" decorative />
            </div>

            <!-- Antoine et sa pinte -->
            <div
                class="px-walk-in absolute bottom-[50px] left-1/2"
                style="margin-left: -76px"
            >
                <PixelSprite name="sage" :px="7" />
            </div>
            <p
                class="font-pixel px-pop absolute bottom-[196px] left-1/2 m-0 ml-1 px-2.5 py-2 text-[11px]"
                style="
                    background: var(--px-cream);
                    color: var(--px-ink);
                    animation-delay: 600ms;
                    box-shadow:
                        0 -3px 0 #1b1b2f,
                        0 3px 0 #1b1b2f,
                        -3px 0 0 #1b1b2f,
                        3px 0 0 #1b1b2f;
                "
            >
                SANTÉ !
            </p>
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
