<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onUnmounted, reactive, ref } from 'vue';
import {
    formatDuration,
    PartyApiError,
    partyPost,
} from '@/components/party/party-api';
import PixelSprite from '@/components/party/PixelSprite.vue';
import { scenes } from '@/components/party/scenes';
import type {
    AnswerResult,
    PartySharedProps,
    QuizStep,
} from '@/components/party/types';
import PartyLayout from '@/layouts/PartyLayout.vue';
import { leaderboard } from '@/routes/party';
import {
    answer as answerRoute,
    show as showRun,
    store as storeRun,
} from '@/routes/party/runs';

const props = defineProps<{
    game: {
        slug: string;
        title: string;
        description: string;
        max_score: number;
    };
    steps: QuizStep[];
}>();

const page = usePage();
const isClosed = computed(
    () => (page.props as unknown as PartySharedProps).party.isClosed,
);

const chapterLabels: Record<QuizStep['chapter'], string> = {
    enfance: "CH.1 · L'ENFANCE",
    ado: "CH.2 · L'ADOLESCENCE",
    adulte: "CH.3 · L'ÂGE ADULTE",
};

const letterColors: Record<string, string> = {
    a: 'var(--px-coral)',
    b: 'var(--px-teal)',
    c: 'var(--px-gold)',
};

const runUuid = ref<string | null>(null);
const visibleCount = ref(0);
const answers = reactive<
    Record<string, { choice: string; result: AnswerResult }>
>({});
const pendingStep = ref<string | null>(null);
const isStarting = ref(false);
const error = ref<string | null>(null);

const visibleSteps = computed(() => props.steps.slice(0, visibleCount.value));
const isFinished = computed(() =>
    Object.values(answers).some((entry) => entry.result.finished),
);

/* Chrono affiché (le temps officiel est calculé par le serveur). */
const startedAt = ref<number | null>(null);
const now = ref(Date.now());
const finishedAt = ref<number | null>(null);
const timer = window.setInterval(() => (now.value = Date.now()), 1000);
onUnmounted(() => window.clearInterval(timer));
const elapsed = computed(() =>
    startedAt.value ? (finishedAt.value ?? now.value) - startedAt.value : 0,
);

function isNewChapter(index: number): boolean {
    return (
        index === 0 ||
        props.steps[index - 1].chapter !== props.steps[index].chapter
    );
}

function pipColor(step: QuizStep, index: number): string {
    const entry = answers[step.key];
    if (entry) {
        return entry.result.correct ? 'var(--px-teal)' : 'var(--px-coral)';
    }

    return index === visibleCount.value - 1 ? 'var(--px-gold)' : 'transparent';
}

async function scrollToStep(index: number): Promise<void> {
    await nextTick();
    document
        .getElementById(`step-${index}`)
        ?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

async function start(): Promise<void> {
    isStarting.value = true;
    error.value = null;

    try {
        const response = await partyPost<{ run: string }>(
            storeRun(props.game.slug).url,
        );
        runUuid.value = response.run;
        startedAt.value = Date.now();
        visibleCount.value = 1;
        await scrollToStep(0);
    } catch (exception) {
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : 'Impossible de démarrer la partie.';
    } finally {
        isStarting.value = false;
    }
}

async function choose(step: QuizStep, choice: string): Promise<void> {
    if (!runUuid.value || answers[step.key] || pendingStep.value) {
        return;
    }

    pendingStep.value = step.key;
    error.value = null;

    try {
        const result = await partyPost<AnswerResult>(
            answerRoute(runUuid.value).url,
            { step: step.key, choice },
        );
        answers[step.key] = { choice, result };
        if (result.finished) {
            finishedAt.value = Date.now();
        }
    } catch (exception) {
        error.value =
            exception instanceof PartyApiError
                ? exception.message
                : 'Réponse non envoyée, réessaie.';
    } finally {
        pendingStep.value = null;
    }
}

function choiceStyle(
    step: QuizStep,
    choice: string,
): Record<string, string> | undefined {
    const entry = answers[step.key];
    if (!entry) {
        return undefined;
    }

    if (choice === entry.result.answer) {
        return { background: 'var(--px-teal)' };
    }
    if (choice === entry.choice) {
        return { background: 'var(--px-coral)' };
    }

    return { opacity: '0.5' };
}

async function goToNextStep(): Promise<void> {
    visibleCount.value++;
    await scrollToStep(visibleCount.value - 1);
}

function showResult(): void {
    if (runUuid.value) {
        router.visit(showRun(runUuid.value).url);
    }
}
</script>

<template>
    <PartyLayout :title="game.title">
        <!-- HUD -->
        <div
            v-if="runUuid"
            class="sticky top-[68px] z-10 flex items-center justify-between px-4 py-3"
            style="background: var(--px-ink); border-top: 2px solid #2e2e4a"
        >
            <div
                class="flex gap-1.5"
                :aria-label="`Étape ${Math.min(visibleCount, steps.length)} sur ${steps.length}`"
            >
                <div
                    v-for="(step, index) in steps"
                    :key="step.key"
                    class="size-4"
                    :style="{
                        background: pipColor(step, index),
                        boxShadow: 'inset 0 0 0 2px #4a4e69',
                    }"
                />
            </div>
            <span class="font-pixel text-xs" aria-live="off">{{
                formatDuration(elapsed)
            }}</span>
        </div>

        <!-- Intro -->
        <section
            class="flex flex-col items-center gap-5 px-5 pt-10 pb-8 text-center"
        >
            <p class="font-pixel m-0 text-[10px]" style="color: var(--px-sky)">
                ANTOINE · 35 ANS
            </p>
            <h1
                class="font-pixel m-0 text-xl leading-relaxed"
                style="color: var(--px-gold)"
            >
                La voie<br />de la sagesse
            </h1>
            <p class="m-0 text-xl leading-snug">
                Antoine descend le chemin de sa vie. À chaque étape, devine ce
                qu'il a <em>vraiment</em> fait.
            </p>
            <PixelSprite
                name="enfant"
                :px="6"
                :class="{ 'px-bounce': !runUuid }"
            />

            <template v-if="!runUuid">
                <p v-if="isClosed" class="m-0 text-lg">
                    La fête est finie : les parties sont fermées.
                </p>
                <button
                    v-else
                    type="button"
                    class="px-btn px-btn-gold w-full"
                    :disabled="isStarting"
                    @click="start"
                >
                    COMMENCER ↓
                </button>
                <Link
                    :href="leaderboard({ query: { game: game.slug } })"
                    class="px-btn px-btn-cream w-full"
                    >CLASSEMENT</Link
                >
            </template>
        </section>

        <!-- Étapes -->
        <section
            v-for="(step, index) in visibleSteps"
            :id="`step-${index}`"
            :key="step.key"
            class="flex scroll-mt-32 flex-col pt-6"
        >
            <p
                v-if="isNewChapter(index)"
                class="font-pixel m-0 px-4 pb-3 text-[10px]"
                style="color: var(--px-gold)"
            >
                {{ chapterLabels[step.chapter] }}
            </p>

            <div class="relative">
                <component
                    :is="scenes[step.scene]"
                    :action="answers[step.key]?.result.correct === true"
                />
                <p
                    v-if="answers[step.key]"
                    class="font-pixel px-pop absolute top-3 right-3 m-0 px-3 py-2.5 text-[11px]"
                    :style="{
                        background: answers[step.key].result.correct
                            ? 'var(--px-teal)'
                            : 'var(--px-coral)',
                        color: 'var(--px-ink)',
                    }"
                    role="status"
                >
                    {{
                        answers[step.key].result.correct
                            ? 'BONNE RÉPONSE'
                            : 'RATÉ !'
                    }}
                </p>
            </div>

            <div class="flex flex-col gap-5 px-4 pt-7 pb-4">
                <div class="px-dialog">
                    <span class="px-dialog-tag">{{
                        step.context.toUpperCase()
                    }}</span>
                    <p class="m-0 text-[19px] leading-snug">
                        {{ step.question }}
                    </p>
                </div>

                <button
                    v-for="(label, choice) in step.choices"
                    :key="choice"
                    type="button"
                    class="px-choice"
                    :style="choiceStyle(step, choice)"
                    :disabled="
                        !!answers[step.key] || pendingStep !== null || isClosed
                    "
                    @click="choose(step, choice)"
                >
                    <span
                        class="px-choice-letter"
                        :style="{
                            background:
                                letterColors[choice] ?? 'var(--px-gold)',
                        }"
                    >
                        {{ choice.toUpperCase() }}
                    </span>
                    <span>{{ label }}</span>
                </button>

                <template v-if="answers[step.key]">
                    <div class="px-dialog px-pop mt-2">
                        <span class="px-dialog-tag">LA VÉRITÉ</span>
                        <p class="m-0 text-[19px] leading-snug">
                            {{ answers[step.key].result.reveal }}
                        </p>
                    </div>

                    <button
                        v-if="!answers[step.key].result.finished"
                        type="button"
                        class="px-btn px-btn-gold mt-2"
                        @click="goToNextStep"
                    >
                        CONTINUER ↓
                    </button>
                    <button
                        v-else
                        type="button"
                        class="px-btn px-btn-gold mt-2"
                        @click="showResult"
                    >
                        VOIR MON RÉSULTAT ▶
                    </button>
                </template>
            </div>
        </section>

        <p v-if="error" class="px-error m-0 px-4 pb-6 text-center" role="alert">
            {{ error }}
        </p>

        <div v-if="isFinished" class="h-10" />
    </PartyLayout>
</template>
