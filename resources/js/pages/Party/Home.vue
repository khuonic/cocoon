<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDuration } from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import type { SpriteName } from '@/components/party/sprites';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import { show as showGame } from '@/routes/party/games';

type HomeGame = {
    slug: string;
    title: string;
    description: string;
    ranking: 'points' | 'speed';
    max_score: number;
    played: boolean;
    locked: boolean;
    required_title: string | null;
    best: { score: number; duration_ms: number } | null;
};

defineProps<{ games: HomeGame[] }>();

/** Vignette de chaque jeu sur l'accueil. */
const covers: Record<
    string,
    { sprite: SpriteName; px: number; background: string }
> = {
    'voie-de-la-sagesse': {
        sprite: 'sage',
        px: 4,
        background: 'var(--px-gold)',
    },
    memory: { sprite: 'pinte', px: 7, background: 'var(--px-plum)' },
    'casse-brique': { sprite: 'ventouse', px: 6, background: 'var(--px-teal)' },
};

function cover(slug: string) {
    return covers[slug] ?? covers['voie-de-la-sagesse'];
}

function recordLabel(game: HomeGame): string | null {
    if (game.best) {
        return game.max_score > 0
            ? `Ton record : ${game.best.score}/${game.max_score} · ${formatDuration(game.best.duration_ms)}`
            : `Ton record : ${game.best.score} pts`;
    }

    return game.played ? 'Tes records sont dans le jeu' : null;
}
</script>

<template>
    <PartyLayout title="Les jeux">
        <div
            class="flex flex-col items-center gap-4 px-5 pt-8 pb-2 text-center"
        >
            <PixelSprite name="gateau" :px="6" />
            <h1
                class="font-pixel m-0 text-xl leading-relaxed"
                style="color: var(--px-gold)"
            >
                Antoine<br />a 35 ans
            </h1>
            <p class="m-0 text-[19px] leading-snug">
                Choisis un jeu, fais le meilleur score, grimpe au classement.
            </p>
        </div>

        <div class="flex flex-col gap-6 px-5 pt-6 pb-10">
            <component
                :is="game.locked ? 'div' : Link"
                v-for="game in games"
                :key="game.slug"
                :href="game.locked ? undefined : showGame(game.slug)"
                class="px-border flex gap-4 p-4 no-underline"
                :style="{
                    background: game.locked ? '#d8cfb4' : 'var(--px-paper)',
                    color: 'var(--px-ink)',
                }"
                :aria-disabled="game.locked || undefined"
            >
                <div
                    class="relative flex w-[76px] flex-none items-center justify-center"
                    :style="{
                        background: cover(game.slug).background,
                        minHeight: '112px',
                        filter: game.locked
                            ? 'grayscale(1) brightness(0.6)'
                            : undefined,
                    }"
                >
                    <PixelSprite
                        :name="cover(game.slug).sprite"
                        :px="cover(game.slug).px"
                        decorative
                    />
                    <span
                        v-if="game.locked"
                        class="absolute inset-0 flex items-center justify-center text-3xl"
                        aria-hidden="true"
                        >🔒</span
                    >
                </div>
                <div class="flex min-w-0 flex-col gap-2">
                    <span class="font-pixel text-xs leading-normal">{{
                        game.title
                    }}</span>
                    <span class="text-[17px] leading-tight">{{
                        game.description
                    }}</span>
                    <span
                        v-if="recordLabel(game)"
                        class="text-base"
                        style="color: #4a3b55"
                    >
                        {{ recordLabel(game) }}
                    </span>
                    <span
                        v-if="game.locked"
                        class="font-pixel self-start px-3 py-2.5 text-[9px] leading-relaxed"
                        style="
                            background: var(--px-ink);
                            color: var(--px-paper);
                        "
                    >
                        🔒 TERMINE D'ABORD<br />{{
                            (game.required_title ?? '').toUpperCase()
                        }}
                    </span>
                    <span
                        v-else
                        class="font-pixel self-start px-3 py-2.5 text-[11px]"
                        style="background: var(--px-gold)"
                    >
                        {{ game.played ? 'REJOUER ▶' : 'JOUER ▶' }}
                    </span>
                </div>
            </component>

            <Link :href="leaderboard()" class="px-btn px-btn-cream"
                >CLASSEMENT</Link
            >
        </div>
    </PartyLayout>
</template>
