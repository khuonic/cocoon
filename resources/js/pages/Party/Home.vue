<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { formatDuration } from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import { show as showGame } from '@/routes/party/games';

defineProps<{
    games: {
        slug: string;
        title: string;
        description: string;
        max_score: number;
        best: { score: number; duration_ms: number } | null;
    }[];
}>();
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
            <Link
                v-for="game in games"
                :key="game.slug"
                :href="showGame(game.slug)"
                class="px-border flex gap-4 p-4 no-underline"
                style="background: var(--px-paper); color: var(--px-ink)"
            >
                <div
                    class="flex w-[76px] flex-none items-end justify-center pb-1.5"
                    style="background: var(--px-gold); min-height: 112px"
                >
                    <PixelSprite name="sage" :px="4" decorative />
                </div>
                <div class="flex min-w-0 flex-col gap-2">
                    <span class="font-pixel text-xs leading-normal">{{
                        game.title
                    }}</span>
                    <span class="text-[17px] leading-tight">{{
                        game.description
                    }}</span>
                    <span
                        v-if="game.best"
                        class="text-base"
                        style="color: #4a3b55"
                    >
                        Ton record : {{ game.best.score }}/{{
                            game.max_score
                        }}
                        · {{ formatDuration(game.best.duration_ms) }}
                    </span>
                    <span
                        class="font-pixel self-start px-3 py-2.5 text-[11px]"
                        style="background: var(--px-gold)"
                    >
                        {{ game.best ? 'REJOUER ▶' : 'JOUER ▶' }}
                    </span>
                </div>
            </Link>

            <div
                class="flex gap-4 p-4"
                style="
                    background: #2e2e4a;
                    color: var(--px-muted);
                    box-shadow:
                        0 -4px 0 #4a4e69,
                        0 4px 0 #4a4e69,
                        -4px 0 0 #4a4e69,
                        4px 0 0 #4a4e69;
                "
            >
                <div
                    class="font-pixel flex size-[76px] flex-none items-center justify-center text-2xl"
                    style="background: #3a3a5c; color: #8d89a6"
                >
                    ?
                </div>
                <div class="flex flex-col gap-2">
                    <span class="font-pixel text-xs leading-normal"
                        >Prochain jeu</span
                    >
                    <span class="text-[17px]">Bientôt débloqué…</span>
                </div>
            </div>

            <Link :href="leaderboard()" class="px-btn px-btn-cream"
                >CLASSEMENT</Link
            >
        </div>
    </PartyLayout>
</template>
