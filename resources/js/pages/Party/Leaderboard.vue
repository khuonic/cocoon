<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { formatDuration } from '@/components/party/party-api';
import type { VariantAxis } from '@/components/party/types';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';

type Entry = {
    rank: number;
    player_id: number;
    pseudo: string;
    score: number;
    max_score: number;
    duration_ms: number;
    moves: number | null;
};

const props = defineProps<{
    games: { slug: string; title: string }[];
    selectedGame: {
        slug: string;
        ranking: 'points' | 'speed';
        variant_axes: VariantAxis[];
    } | null;
    selectedVariant: string | null;
    entries: Entry[];
    currentPlayerId: number;
}>();

const isSpeed = computed(() => props.selectedGame?.ranking === 'speed');
const variantValues = computed(() => props.selectedVariant?.split('-') ?? []);

/** Variante obtenue en changeant la valeur d'un seul axe. */
function variantWith(axisIndex: number, value: string): string {
    const values = [...variantValues.value];
    values[axisIndex] = value;

    return values.join('-');
}

function variantHref(axisIndex: number, value: string) {
    return leaderboard({
        query: {
            game: props.selectedGame!.slug,
            variant: variantWith(axisIndex, value),
        },
    });
}

function scoreLabel(entry: Entry): string {
    return entry.max_score > 0
        ? `${entry.score}/${entry.max_score}`
        : `${entry.score}`;
}

/** Ordre visuel du podium : 2e, 1er, 3e. */
const podium = computed(() =>
    [
        { entry: props.entries[1], height: 70, color: 'var(--px-sky)' },
        { entry: props.entries[0], height: 100, color: 'var(--px-gold)' },
        { entry: props.entries[2], height: 50, color: 'var(--px-coral)' },
    ].filter((step) => step.entry !== undefined),
);
</script>

<template>
    <PartyLayout title="Classement">
        <div class="flex flex-col gap-6 px-4 pt-5 pb-10">
            <h1
                class="font-pixel m-0 text-center text-sm"
                style="color: var(--px-gold)"
            >
                CLASSEMENT
            </h1>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="game in games"
                    :key="game.slug"
                    :href="leaderboard({ query: { game: game.slug } })"
                    class="font-pixel px-2.5 py-2.5 text-[10px] leading-normal no-underline"
                    :style="
                        game.slug === selectedGame?.slug
                            ? 'background: var(--px-gold); color: var(--px-ink)'
                            : 'background: #2e2e4a; color: #8d89a6'
                    "
                    :aria-current="
                        game.slug === selectedGame?.slug ? 'page' : undefined
                    "
                >
                    {{ game.title.toUpperCase() }}
                </Link>
            </div>

            <div
                v-if="selectedGame && selectedGame.variant_axes.length"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div
                    v-for="(axis, axisIndex) in selectedGame.variant_axes"
                    :key="axis.key"
                    class="flex gap-1.5"
                    role="group"
                    :aria-label="axis.label"
                >
                    <Link
                        v-for="(label, value) in axis.options"
                        :key="value"
                        :href="variantHref(axisIndex, String(value))"
                        preserve-scroll
                        class="font-pixel flex min-h-11 min-w-12 items-center justify-center px-2.5 text-[10px] no-underline"
                        :style="
                            variantValues[axisIndex] === String(value)
                                ? 'background: var(--px-gold); color: var(--px-ink)'
                                : 'background: var(--px-cream); color: var(--px-ink)'
                        "
                        :aria-current="
                            variantValues[axisIndex] === String(value)
                                ? 'true'
                                : undefined
                        "
                        :aria-label="label"
                    >
                        {{ label.replace(' cartes', '').toUpperCase() }}
                    </Link>
                </div>
            </div>

            <p
                v-if="selectedGame"
                class="m-0 text-base"
                style="color: var(--px-muted)"
            >
                {{
                    isSpeed
                        ? 'Le plus rapide gagne, les coups départagent.'
                        : 'Le plus de points gagne, le temps départage.'
                }}
            </p>

            <p
                v-if="entries.length === 0"
                class="m-0 py-10 text-center text-xl"
            >
                Personne n'a encore fini de partie. À toi de jouer !
            </p>

            <template v-else>
                <div class="grid grid-cols-3 items-end gap-2 pt-4 text-center">
                    <div
                        v-for="step in podium"
                        :key="step.entry!.player_id"
                        class="flex min-w-0 flex-col gap-2"
                    >
                        <span class="truncate text-lg">{{
                            step.entry!.pseudo
                        }}</span>
                        <div
                            class="font-pixel flex items-center justify-center text-lg"
                            :style="{
                                height: `${step.height}px`,
                                background: step.color,
                                color: 'var(--px-ink)',
                            }"
                        >
                            {{ step.entry!.rank }}
                        </div>
                    </div>
                </div>
                <div class="h-2" style="background: var(--px-plum)" />

                <ol class="m-0 flex list-none flex-col gap-2 p-0">
                    <li
                        class="grid grid-cols-[44px_minmax(0,1fr)_60px_56px] gap-2 px-3 text-sm"
                        style="color: #8d89a6"
                        aria-hidden="true"
                    >
                        <span>Rang</span>
                        <span>Joueur</span>
                        <span>{{ isSpeed ? 'Temps' : 'Score' }}</span>
                        <span class="text-right">{{
                            isSpeed ? 'Coups' : 'Temps'
                        }}</span>
                    </li>
                    <li
                        v-for="entry in entries"
                        :key="entry.player_id"
                        class="grid grid-cols-[44px_minmax(0,1fr)_60px_56px] items-center gap-2 px-3 py-2.5 text-lg"
                        :style="
                            entry.player_id === currentPlayerId
                                ? 'background: var(--px-teal); color: var(--px-ink)'
                                : 'background: var(--px-ink); color: var(--px-paper)'
                        "
                    >
                        <span class="font-pixel text-[11px]"
                            >#{{ entry.rank }}</span
                        >
                        <span class="truncate">{{ entry.pseudo }}</span>
                        <span class="font-pixel text-[11px]">{{
                            isSpeed
                                ? formatDuration(entry.duration_ms)
                                : scoreLabel(entry)
                        }}</span>
                        <span class="text-right">{{
                            isSpeed
                                ? entry.moves
                                : formatDuration(entry.duration_ms)
                        }}</span>
                    </li>
                </ol>
            </template>
        </div>
    </PartyLayout>
</template>
