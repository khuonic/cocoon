<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { useElementSize } from '@vueuse/core';
import {
    computed,
    nextTick,
    onUnmounted,
    ref,
    useTemplateRef,
    watch,
} from 'vue';
import {
    BreakoutEngine,
    bonuses,
    type HudState,
} from '@/components/party/breakout/engine';
import { levels } from '@/components/party/breakout/levels';
import {
    formatDuration,
    PartyApiError,
    partyPost,
} from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import type { GameMeta, PartySharedProps } from '@/components/party/types';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import {
    finish as finishRoute,
    show as showRun,
    store as storeRun,
} from '@/routes/party/runs';

defineProps<{
    game: GameMeta;
    best: { score: number; duration_ms: number } | null;
}>();

const page = usePage();
const isClosed = computed(
    () => (page.props as unknown as PartySharedProps).party.isClosed,
);

const HELP_SEEN_KEY = 'party_breakout_help_seen';
const bonusList = Object.values(bonuses);

const phase = ref<'intro' | 'playing' | 'sending'>('intro');
const runUuid = ref<string | null>(null);
const hud = ref<HudState>({
    score: 0,
    lives: 3,
    level: 1,
    levelName: levels[0].name,
});
const toast = ref<string | null>(null);
const isHelpOpen = ref(false);
const isPausedByVisibility = ref(false);
const error = ref<string | null>(null);

const canvas = useTemplateRef<HTMLCanvasElement>('canvas');
const stage = useTemplateRef<HTMLElement>('stage');
const { width: stageWidth, height: stageHeight } = useElementSize(stage);
let engine: BreakoutEngine | null = null;
let toastTimer = 0;

watch([stageWidth, stageHeight], ([width, height]) => {
    if (engine && width > 0 && height > 0) {
        engine.resize(width, height);
    }
});

function showToast(text: string): void {
    toast.value = text;
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(() => (toast.value = null), 1600);
}

async function start(): Promise<void> {
    error.value = null;

    try {
        const response = await partyPost<{ run: string }>(
            storeRun('casse-brique').url,
        );
        runUuid.value = response.run;
    } catch (exception) {
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : 'Impossible de lancer la partie.';
        return;
    }

    phase.value = 'playing';
    await nextTick();
    window.scrollTo({ top: 0 });

    engine = new BreakoutEngine(canvas.value!, {
        onHud: (state) => (hud.value = state),
        onToast: showToast,
        onEnd: sendScore,
    });
    engine.resize(stageWidth.value || 360, stageHeight.value || 560);
    engine.start();

    if (!hasSeenHelp()) {
        openHelp();
        markHelpSeen();
    }
}

function hasSeenHelp(): boolean {
    try {
        return localStorage.getItem(HELP_SEEN_KEY) === '1';
    } catch {
        return false;
    }
}

function markHelpSeen(): void {
    try {
        localStorage.setItem(HELP_SEEN_KEY, '1');
    } catch {
        // stockage indisponible : l'aide se rouvrira simplement la prochaine fois
    }
}

function openHelp(): void {
    isHelpOpen.value = true;
    engine?.pause();
}

function closeHelp(): void {
    isHelpOpen.value = false;
    if (!isPausedByVisibility.value) {
        engine?.resume();
    }
}

function resumeAfterVisibility(): void {
    isPausedByVisibility.value = false;
    if (!isHelpOpen.value) {
        engine?.resume();
    }
}

function handleVisibilityChange(): void {
    if (document.hidden && phase.value === 'playing') {
        isPausedByVisibility.value = true;
        engine?.pause();
    }
}
document.addEventListener('visibilitychange', handleVisibilityChange);

async function sendScore(result: {
    score: number;
    level: number;
    won: boolean;
}): Promise<void> {
    if (!runUuid.value) {
        return;
    }

    phase.value = 'sending';

    try {
        await partyPost(finishRoute(runUuid.value).url, result);
        window.setTimeout(() => router.visit(showRun(runUuid.value!).url), 900);
    } catch (exception) {
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : "Le score n'est pas parti, désolé.";
        stopEngine();
        phase.value = 'intro';
    }
}

function quit(): void {
    stopEngine();
    phase.value = 'intro';
}

function stopEngine(): void {
    engine?.destroy();
    engine = null;
}

onUnmounted(() => {
    stopEngine();
    window.clearTimeout(toastTimer);
    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <PartyLayout :title="game.title">
        <!-- Intro -->
        <div
            v-if="phase === 'intro'"
            class="flex flex-col items-center gap-5 px-5 pt-10 pb-10 text-center"
        >
            <div class="flex items-end gap-4" aria-hidden="true">
                <PixelSprite name="ventouse" :px="5" decorative />
                <PixelSprite name="falafel" :px="6" decorative />
                <PixelSprite name="pinte" :px="6" decorative />
            </div>
            <h1
                class="font-pixel m-0 text-lg leading-relaxed"
                style="color: var(--px-gold)"
            >
                Le casse-brique<br />d'Antoine
            </h1>
            <p class="m-0 text-xl leading-snug">
                {{ levels.length }} niveaux, un par anecdote. Glisse ton pouce
                pour bouger la raquette, attrape les bonus, garde tes pintes.
            </p>
            <p class="m-0 text-[17px]" style="color: var(--px-muted)">
                <template v-if="best">
                    Ton record : {{ best.score }} pts ·
                    {{ formatDuration(best.duration_ms) }}
                </template>
                <template v-else>Pas encore de record.</template>
            </p>
            <p v-if="error" class="px-error m-0" role="alert">{{ error }}</p>
            <p v-if="isClosed" class="m-0 text-lg">
                La fête est finie : les parties sont fermées.
            </p>
            <button
                v-else
                type="button"
                class="px-btn px-btn-gold w-full"
                @click="start"
            >
                JOUER ▶
            </button>
            <button
                type="button"
                class="px-btn px-btn-cream w-full"
                @click="isHelpOpen = true"
            >
                LES BONUS
            </button>
            <Link
                :href="leaderboard({ query: { game: game.slug } })"
                class="px-btn px-btn-cream w-full"
                >CLASSEMENT</Link
            >
        </div>

        <!-- Partie -->
        <div
            v-else
            class="flex flex-col"
            style="height: calc(100dvh - 68px); overscroll-behavior: none"
        >
            <div
                class="flex items-center justify-between gap-2 px-4 py-2.5"
                style="background: var(--px-ink); border-top: 2px solid #2e2e4a"
            >
                <span
                    class="font-pixel text-[13px]"
                    style="color: var(--px-gold)"
                    aria-live="off"
                    >{{ hud.score }}</span
                >
                <span
                    class="font-pixel truncate text-[9px]"
                    style="color: var(--px-muted)"
                    >NIV.{{ hud.level }} · {{ hud.levelName }}</span
                >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex gap-1"
                        :aria-label="`${hud.lives} vie${hud.lives > 1 ? 's' : ''}`"
                    >
                        <PixelSprite
                            v-for="life in hud.lives"
                            :key="life"
                            name="pinte"
                            :px="3"
                            decorative
                        />
                    </div>
                    <button
                        type="button"
                        class="font-pixel size-9 border-0 text-[13px]"
                        style="
                            background: var(--px-cream);
                            color: var(--px-ink);
                            box-shadow:
                                0 -3px 0 var(--px-gold),
                                0 3px 0 var(--px-gold),
                                -3px 0 0 var(--px-gold),
                                3px 0 0 var(--px-gold);
                        "
                        aria-label="Les bonus (met le jeu en pause)"
                        :disabled="phase !== 'playing'"
                        @click="openHelp"
                    >
                        ?
                    </button>
                </div>
            </div>

            <div
                ref="stage"
                class="relative flex flex-1 items-start justify-center overflow-hidden"
            >
                <canvas
                    ref="canvas"
                    class="block"
                    style="touch-action: none; user-select: none"
                    aria-label="Terrain du casse-brique : glisse le doigt pour bouger la raquette, touche pour lancer la balle"
                    role="img"
                />

                <p
                    v-if="toast"
                    class="font-pixel px-pop pointer-events-none absolute top-3 left-1/2 m-0 -translate-x-1/2 px-3 py-2 text-[10px] whitespace-nowrap"
                    style="background: var(--px-gold); color: var(--px-ink)"
                    role="status"
                >
                    {{ toast }}
                </p>

                <p
                    v-if="phase === 'sending'"
                    class="font-pixel absolute inset-x-0 bottom-6 m-0 text-center text-[10px]"
                    style="color: var(--px-gold)"
                    role="status"
                >
                    ENVOI DU SCORE…
                </p>

                <div
                    v-if="isPausedByVisibility && !isHelpOpen"
                    class="absolute inset-0 flex flex-col items-center justify-center gap-6 px-6"
                    style="background: rgb(27 27 47 / 0.85)"
                >
                    <p
                        class="font-pixel m-0 text-sm"
                        style="color: var(--px-gold)"
                    >
                        PAUSE
                    </p>
                    <button
                        type="button"
                        class="px-btn px-btn-gold"
                        @click="resumeAfterVisibility"
                    >
                        REPRENDRE ▶
                    </button>
                </div>
            </div>

            <button
                v-if="phase === 'playing'"
                type="button"
                class="border-0 bg-transparent p-2 text-[15px] underline"
                style="color: var(--px-muted)"
                @click="quit"
            >
                Abandonner
            </button>
        </div>

        <!-- Aide : récap des bonus -->
        <div
            v-if="isHelpOpen"
            class="fixed inset-0 z-40 flex items-center justify-center p-5"
            style="background: rgb(27 27 47 / 0.85)"
        >
            <div
                class="px-dialog flex w-full max-w-sm flex-col gap-3.5"
                role="dialog"
                aria-modal="true"
                aria-label="Les bonus"
                style="background: var(--px-night); padding-top: 26px"
            >
                <span class="px-dialog-tag">{{
                    phase === 'intro' ? 'LES BONUS' : 'PAUSE · LES BONUS'
                }}</span>
                <div
                    v-for="bonus in bonusList"
                    :key="bonus.title"
                    class="flex items-center gap-3"
                >
                    <div
                        class="flex size-10 flex-none items-center justify-center"
                        :style="{
                            background: bonus.isMalus
                                ? 'var(--px-coral)'
                                : 'var(--px-cream)',
                        }"
                    >
                        <PixelSprite
                            :name="bonus.sprite"
                            :px="bonus.sprite === 'scooter' ? 2 : 3"
                            decorative
                        />
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span
                            class="font-pixel text-[10px]"
                            :style="{
                                color: bonus.isMalus
                                    ? 'var(--px-coral)'
                                    : 'var(--px-teal)',
                            }"
                            >{{ bonus.title }}</span
                        >
                        <span class="text-[17px] leading-tight">{{
                            bonus.text
                        }}</span>
                    </div>
                </div>
                <button
                    type="button"
                    class="px-btn px-btn-gold mt-1.5"
                    @click="
                        phase === 'intro' ? (isHelpOpen = false) : closeHelp()
                    "
                >
                    {{ phase === 'intro' ? 'COMPRIS' : 'REPRENDRE ▶' }}
                </button>
            </div>
        </div>
    </PartyLayout>
</template>
