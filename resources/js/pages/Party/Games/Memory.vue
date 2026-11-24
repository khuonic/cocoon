<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import { useBackgroundMusic } from '@/components/party/background-music';
import { memoryFaces } from '@/components/party/memory-faces';
import {
    formatDuration,
    PartyApiError,
    partyPost,
} from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import SoundToggle from '@/components/party/SoundToggle.vue';
import type { GameMeta, PartySharedProps } from '@/components/party/types';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import { photo as photoRoute } from '@/routes/party/memory';
import {
    finish as finishRoute,
    show as showRun,
    store as storeRun,
} from '@/routes/party/runs';

type MemoryRecord = { duration_ms: number; moves: number };
type Card = { position: number; face: number; isMatched: boolean };

const props = defineProps<{
    game: GameMeta;
    records: Record<string, MemoryRecord>;
    photoCount: number;
}>();

const page = usePage();
const isClosed = computed(
    () => (page.props as unknown as PartySharedProps).party.isClosed,
);

const sizes = ['12', '16', '20'] as const;
/** Grille de jeu par taille : 12 cartes en 3 colonnes pour des cartes plus larges. */
const grids: Record<string, { columns: number; rows: number }> = {
    '12': { columns: 3, rows: 4 },
    '16': { columns: 4, rows: 4 },
    '20': { columns: 4, rows: 5 },
};
/** Hauteur maximale d'une rangée, pour garder des cartes raisonnables sur grand écran. */
const MAX_ROW_HEIGHT = 170;
const CHOICE_KEY = 'party_memory_choice';

/* Derniers choix retenus (confort uniquement : la page marche sans). */
function readChoice(): { size: string; style: string } {
    try {
        const saved = JSON.parse(localStorage.getItem(CHOICE_KEY) ?? '{}');
        return {
            size: sizes.includes(saved.size) ? saved.size : '16',
            style: saved.style === 'photos' ? 'photos' : 'pixel',
        };
    } catch {
        return { size: '16', style: 'pixel' };
    }
}

const choice = ref(readChoice());
const variant = computed(() => `${choice.value.size}-${choice.value.style}`);
const pairsNeeded = computed(() => Number(choice.value.size) / 2);
const photosAvailable = computed(() => props.photoCount >= pairsNeeded.value);
const canPlay = computed(
    () =>
        !isClosed.value &&
        (choice.value.style === 'pixel' || photosAvailable.value),
);
const record = computed(() => props.records[variant.value] ?? null);

function pick(size: string, style: string): void {
    choice.value = { size, style };
    if (style === 'photos') {
        preloadPhotos();
    }
    try {
        localStorage.setItem(CHOICE_KEY, JSON.stringify(choice.value));
    } catch {
        // stockage indisponible : le choix n'est simplement pas retenu
    }
}

/* Musique de fond optionnelle : public/sounds/memory/fond.mp3 (silence tant qu'il est absent). */
const {
    isAvailable: hasMusic,
    isMuted: isMusicMuted,
    start: startMusic,
    stop: stopMusic,
    toggleMute: toggleMusic,
} = useBackgroundMusic('/sounds/memory/fond.mp3', 'party_memory_music_muted');

/* Partie en cours */
const phase = ref<'setup' | 'playing' | 'sending'>('setup');
const runUuid = ref<string | null>(null);
const cards = ref<Card[]>([]);
const flipped = ref<number[]>([]);
const flips: number[] = [];
const moves = ref(0);
const isLocked = ref(false);
const error = ref<string | null>(null);
const playedStyle = ref('pixel');

const matchedPairs = computed(
    () => cards.value.filter((card) => card.isMatched).length / 2,
);
const totalPairs = computed(() => cards.value.length / 2);
const grid = computed(() => grids[String(cards.value.length)] ?? grids['16']);
const gridStyle = computed(() => ({
    gridTemplateColumns: `repeat(${grid.value.columns}, minmax(0, 1fr))`,
    gridTemplateRows: `repeat(${grid.value.rows}, minmax(0, 1fr))`,
    maxHeight: `${grid.value.rows * MAX_ROW_HEIGHT}px`,
}));

/** Les cartes sont plus larges en 3 colonnes : on agrandit les dessins d'autant. */
function facePx(card: Card): number {
    const base = memoryFaces[card.face]?.px ?? 4;

    return grid.value.columns === 3 ? Math.round(base * 1.4) : base;
}

const startedAt = ref<number | null>(null);
const now = ref(Date.now());
const timer = window.setInterval(() => (now.value = Date.now()), 500);
onUnmounted(() => window.clearInterval(timer));
const elapsed = computed(() =>
    startedAt.value ? now.value - startedAt.value : 0,
);

function photoUrl(id: number): string {
    return photoRoute(id).url;
}

/*
 * Photos téléchargées et décodées une seule fois, avant la création de la partie
 * (le chrono serveur démarre à ce moment-là) : retourner une carte n'attend plus le réseau.
 */
let photosReady: Promise<void> | null = null;
const preloadedPhotos: HTMLImageElement[] = [];

function preloadPhotos(): Promise<void> {
    photosReady ??= Promise.all(
        Array.from({ length: props.photoCount }, (_, index) => {
            const image = new Image();
            image.src = photoUrl(index + 1);
            preloadedPhotos.push(image);

            // Une photo illisible ne doit pas bloquer la partie.
            return image.decode().catch(() => undefined);
        }),
    ).then(() => undefined);

    return photosReady;
}

if (choice.value.style === 'photos' && photosAvailable.value) {
    preloadPhotos();
}

const isPreparing = ref(false);

async function start(): Promise<void> {
    // Lancée pendant le tap sur JOUER (exigence des navigateurs mobiles).
    startMusic();
    error.value = null;

    try {
        if (choice.value.style === 'photos') {
            isPreparing.value = true;
            await preloadPhotos();
        }

        const response = await partyPost<{ run: string; deck: number[] }>(
            storeRun(props.game.slug).url,
            { variant: variant.value },
        );

        playedStyle.value = choice.value.style;
        runUuid.value = response.run;
        cards.value = response.deck.map((face, position) => ({
            position,
            face,
            isMatched: false,
        }));
        flipped.value = [];
        flips.length = 0;
        moves.value = 0;
        startedAt.value = Date.now();
        phase.value = 'playing';
        window.scrollTo({ top: 0 });
    } catch (exception) {
        stopMusic();
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : 'Impossible de lancer la partie.';
    } finally {
        isPreparing.value = false;
    }
}

function isFaceUp(card: Card): boolean {
    return card.isMatched || flipped.value.includes(card.position);
}

function flip(card: Card): void {
    if (
        phase.value !== 'playing' ||
        isLocked.value ||
        isFaceUp(card) ||
        flipped.value.length >= 2
    ) {
        return;
    }

    flipped.value.push(card.position);
    flips.push(card.position);

    if (flipped.value.length < 2) {
        return;
    }

    moves.value++;
    const [first, second] = flipped.value.map(
        (position) => cards.value[position],
    );

    if (first.face === second.face) {
        first.isMatched = true;
        second.isMatched = true;
        flipped.value = [];

        if (matchedPairs.value === totalPairs.value) {
            finish();
        }

        return;
    }

    isLocked.value = true;
    window.setTimeout(() => {
        flipped.value = [];
        isLocked.value = false;
    }, 750);
}

async function finish(): Promise<void> {
    if (!runUuid.value) {
        return;
    }

    phase.value = 'sending';
    stopMusic();

    try {
        await partyPost(finishRoute(runUuid.value).url, { flips });
        router.visit(showRun(runUuid.value).url);
    } catch (exception) {
        phase.value = 'setup';
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : "Le résultat n'est pas parti, réessaie une partie.";
    }
}

function abandon(): void {
    stopMusic();
    phase.value = 'setup';
    runUuid.value = null;
    cards.value = [];
    startedAt.value = null;
}

function cardStyle(card: Card): Record<string, string> {
    if (card.isMatched) {
        return { background: 'var(--px-teal)' };
    }

    return {
        background: isFaceUp(card) ? '#CFE3EC' : 'var(--px-plum)',
    };
}

function cardLabel(card: Card): string {
    if (!isFaceUp(card)) {
        return `Carte ${card.position + 1}, face cachée`;
    }

    return playedStyle.value === 'photos'
        ? `Photo ${card.face}`
        : (memoryFaces[card.face]?.label ?? 'Carte');
}
</script>

<template>
    <PartyLayout :title="game.title">
        <!-- Choix de la partie -->
        <template v-if="phase === 'setup'">
            <div class="flex flex-col items-center gap-3 px-5 pt-8 text-center">
                <div class="flex gap-2.5" aria-hidden="true">
                    <div
                        class="size-11"
                        style="
                            background: var(--px-plum);
                            box-shadow: inset 0 0 0 4px var(--px-gold);
                        "
                    />
                    <div
                        class="flex size-11 items-center justify-center"
                        style="background: #cfe3ec"
                    >
                        <PixelSprite name="pinte" :px="4" decorative />
                    </div>
                </div>
                <h1
                    class="font-pixel m-0 text-lg leading-relaxed"
                    style="color: var(--px-gold)"
                >
                    Trou de<br />mémoire
                </h1>
                <p class="m-0 text-lg leading-snug">
                    Retrouve toutes les paires le plus vite possible.
                </p>
            </div>

            <section class="flex flex-col gap-3 px-5 pt-6">
                <h2 class="font-pixel m-0 text-[10px]">NOMBRE DE CARTES</h2>
                <div class="grid grid-cols-3 gap-3.5">
                    <button
                        v-for="size in sizes"
                        :key="size"
                        type="button"
                        class="px-border flex min-h-[72px] flex-col items-center justify-center gap-1.5 border-0"
                        :style="{
                            background:
                                choice.size === size
                                    ? 'var(--px-gold)'
                                    : 'var(--px-cream)',
                            color: 'var(--px-ink)',
                        }"
                        :aria-pressed="choice.size === size"
                        @click="pick(size, choice.style)"
                    >
                        <span class="font-pixel text-lg">{{ size }}</span>
                        <span class="text-[15px]"
                            >{{ grids[size].columns }} ×
                            {{ grids[size].rows }}</span
                        >
                    </button>
                </div>
            </section>

            <section class="flex flex-col gap-3 px-5 pt-6">
                <h2 class="font-pixel m-0 text-[10px]">STYLE DES CARTES</h2>
                <div class="grid grid-cols-2 gap-3.5">
                    <button
                        type="button"
                        class="px-border flex flex-col items-center gap-2.5 border-0 p-3"
                        :style="{
                            background:
                                choice.style === 'pixel'
                                    ? 'var(--px-gold)'
                                    : 'var(--px-cream)',
                            color: 'var(--px-ink)',
                        }"
                        :aria-pressed="choice.style === 'pixel'"
                        @click="pick(choice.size, 'pixel')"
                    >
                        <span class="flex gap-1.5" aria-hidden="true">
                            <span
                                class="flex size-9 items-center justify-center"
                                style="background: #cfe3ec"
                            >
                                <PixelSprite
                                    name="falafel"
                                    :px="4"
                                    decorative
                                />
                            </span>
                            <span
                                class="flex size-9 items-center justify-center"
                                style="background: #cfe3ec"
                            >
                                <PixelSprite
                                    name="caca_pq"
                                    :px="4"
                                    decorative
                                />
                            </span>
                        </span>
                        <span class="font-pixel text-[10px]">PIXEL ART</span>
                    </button>
                    <button
                        type="button"
                        class="px-border flex flex-col items-center gap-2.5 border-0 p-3"
                        :style="{
                            background:
                                choice.style === 'photos'
                                    ? 'var(--px-gold)'
                                    : 'var(--px-cream)',
                            color: 'var(--px-ink)',
                        }"
                        :aria-pressed="choice.style === 'photos'"
                        @click="pick(choice.size, 'photos')"
                    >
                        <span class="flex gap-1.5" aria-hidden="true">
                            <span
                                v-for="index in 2"
                                :key="index"
                                class="size-9"
                                style="
                                    background: #8d89a6;
                                    box-shadow: inset 0 0 0 3px var(--px-gold);
                                "
                            />
                        </span>
                        <span class="font-pixel text-[10px]">PHOTOS</span>
                    </button>
                </div>
                <p
                    v-if="choice.style === 'photos' && !photosAvailable"
                    class="m-0 text-base"
                    style="color: var(--px-muted)"
                >
                    Les photos arrivent bientôt
                    <template v-if="photoCount > 0">
                        ({{ photoCount }} pour l'instant, il en faut
                        {{ pairsNeeded }})</template
                    >. En attendant, joue en pixel art !
                </p>
            </section>

            <div class="flex flex-col gap-5 px-5 pt-6 pb-10">
                <p
                    class="m-0 text-center text-[17px]"
                    style="color: var(--px-muted)"
                >
                    <template v-if="record">
                        Ton record : {{ formatDuration(record.duration_ms) }} ·
                        {{ record.moves }} coups
                    </template>
                    <template v-else>Pas encore de record ici.</template>
                </p>
                <p v-if="error" class="px-error m-0 text-center" role="alert">
                    {{ error }}
                </p>
                <p v-if="isClosed" class="m-0 text-center text-lg">
                    La fête est finie : les parties sont fermées.
                </p>
                <button
                    type="button"
                    class="px-btn px-btn-gold"
                    :disabled="!canPlay || isPreparing"
                    @click="start"
                >
                    {{ isPreparing ? 'CHARGEMENT…' : 'JOUER ▶' }}
                </button>
                <Link
                    :href="
                        leaderboard({
                            query: { game: game.slug, variant },
                        })
                    "
                    class="px-btn px-btn-cream"
                    >CLASSEMENT</Link
                >
            </div>
        </template>

        <!-- Partie : la grille occupe toute la hauteur restante, sans scroll -->
        <div v-else class="flex flex-col" style="height: calc(100dvh - 68px)">
            <div
                class="flex flex-none items-center gap-2 px-4 py-2.5 text-center"
                style="background: var(--px-ink); border-top: 2px solid #2e2e4a"
            >
                <div class="flex flex-1 flex-col gap-1.5">
                    <span class="text-sm" style="color: var(--px-muted)"
                        >Temps</span
                    >
                    <span class="font-pixel text-[13px]">{{
                        formatDuration(elapsed)
                    }}</span>
                </div>
                <div class="flex flex-1 flex-col gap-1.5">
                    <span class="text-sm" style="color: var(--px-muted)"
                        >Coups</span
                    >
                    <span class="font-pixel text-[13px]">{{ moves }}</span>
                </div>
                <div class="flex flex-1 flex-col gap-1.5">
                    <span class="text-sm" style="color: var(--px-muted)"
                        >Paires</span
                    >
                    <span
                        class="font-pixel text-[13px]"
                        style="color: var(--px-gold)"
                        >{{ matchedPairs }}/{{ totalPairs }}</span
                    >
                </div>
                <SoundToggle
                    v-if="hasMusic"
                    :muted="isMusicMuted"
                    @toggle="toggleMusic"
                />
            </div>

            <div
                class="grid min-h-0 w-full flex-1 gap-2.5 self-center px-3 py-3"
                :style="gridStyle"
            >
                <button
                    v-for="card in cards"
                    :key="card.position"
                    type="button"
                    class="relative flex min-h-0 items-center justify-center overflow-hidden border-0 p-0"
                    :style="{
                        boxShadow:
                            '0 -3px 0 #1b1b2f, 0 3px 0 #1b1b2f, -3px 0 0 #1b1b2f, 3px 0 0 #1b1b2f',
                        ...cardStyle(card),
                    }"
                    :aria-label="cardLabel(card)"
                    :disabled="phase !== 'playing'"
                    @click="flip(card)"
                >
                    <!-- Photo toujours présente (déjà décodée), seulement masquée face cachée. -->
                    <img
                        v-if="playedStyle === 'photos'"
                        :src="photoUrl(card.face)"
                        alt=""
                        class="absolute inset-0 size-full object-cover"
                        :class="{ 'px-pop': isFaceUp(card) }"
                        :style="{
                            visibility: isFaceUp(card) ? 'visible' : 'hidden',
                            boxShadow: `inset 0 0 0 3px ${card.isMatched ? 'var(--px-teal)' : 'var(--px-gold)'}`,
                            padding: '3px',
                        }"
                        draggable="false"
                    />
                    <span
                        v-if="!isFaceUp(card)"
                        class="absolute inset-2 flex items-center justify-center"
                        style="box-shadow: inset 0 0 0 4px var(--px-gold)"
                        aria-hidden="true"
                    >
                        <span
                            class="font-pixel text-xs"
                            style="color: var(--px-gold)"
                            >35</span
                        >
                    </span>
                    <PixelSprite
                        v-else-if="playedStyle !== 'photos'"
                        class="px-pop"
                        :name="memoryFaces[card.face]?.sprite ?? 'pinte'"
                        :px="facePx(card)"
                        decorative
                    />
                </button>
            </div>

            <p
                v-if="phase === 'sending'"
                class="font-pixel m-0 flex-none px-4 py-3 text-center text-xs"
                style="color: var(--px-gold)"
                role="status"
            >
                BRAVO ! ENVOI DU TEMPS…
            </p>
            <button
                v-else
                type="button"
                class="flex-none border-0 bg-transparent p-2 text-[15px] underline"
                style="color: var(--px-muted)"
                @click="abandon"
            >
                Abandonner
            </button>
        </div>
    </PartyLayout>
</template>
