<script setup lang="ts">
import '../../css/party.css';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, onUnmounted, ref } from 'vue';
import type { PartySharedProps } from '@/components/party/types';
import { home, leaderboard } from '@/routes/party';
import { edit as editPseudo } from '@/routes/party/player';
import { show as showSecret } from '@/routes/party/secret';

defineProps<{ title?: string }>();

const page = usePage();
const party = computed(() => (page.props as unknown as PartySharedProps).party);
const isMenuOpen = ref(false);

const stopListening = router.on('navigate', () => {
    isMenuOpen.value = false;
});
onUnmounted(stopListening);
</script>

<template>
    <div class="party flex flex-col">
        <Head :title="title ?? party.title" />

        <header
            class="sticky top-0 z-30 flex items-center justify-between gap-3 px-4 py-3"
            style="background: var(--px-ink)"
        >
            <component
                :is="party.player ? Link : 'span'"
                :href="party.player ? home().url : undefined"
                class="font-pixel text-[11px] no-underline"
                style="color: var(--px-gold)"
            >
                LA FÊTE
            </component>

            <div v-if="party.player" class="flex items-center gap-2">
                <span
                    class="max-w-[9rem] truncate px-2 py-1 text-lg"
                    style="background: var(--px-teal); color: var(--px-ink)"
                >
                    {{ party.player.pseudo }}
                </span>
                <button
                    type="button"
                    class="flex size-11 flex-col items-center justify-center gap-[5px]"
                    style="background: var(--px-paper)"
                    :aria-expanded="isMenuOpen"
                    aria-controls="party-menu"
                    aria-label="Menu"
                    @click="isMenuOpen = !isMenuOpen"
                >
                    <span
                        class="block h-1 w-5"
                        style="background: var(--px-ink)"
                    />
                    <span
                        class="block h-1 w-5"
                        style="background: var(--px-ink)"
                    />
                    <span
                        class="block h-1 w-5"
                        style="background: var(--px-ink)"
                    />
                </button>
            </div>
        </header>

        <nav
            v-if="isMenuOpen && party.player"
            id="party-menu"
            class="sticky top-[68px] z-20 flex flex-col gap-5 px-4 pt-3 pb-6"
            style="background: var(--px-ink)"
        >
            <Link :href="home()" class="px-btn px-btn-cream">LES JEUX</Link>
            <Link :href="leaderboard()" class="px-btn px-btn-cream"
                >CLASSEMENT</Link
            >
            <Link :href="showSecret()" class="px-btn px-btn-gold"
                >LE SECRET D'ANTOINE ★</Link
            >
            <Link :href="editPseudo()" class="px-btn px-btn-cream"
                >CHANGER DE PSEUDO</Link
            >
        </nav>

        <p
            v-if="party.isClosed"
            class="font-pixel m-0 px-4 py-3 text-center text-[10px] leading-relaxed"
            style="background: var(--px-plum)"
        >
            LA FÊTE EST FINIE · LE CLASSEMENT EST FIGÉ
        </p>

        <main class="mx-auto flex w-full max-w-md flex-1 flex-col">
            <slot />
        </main>
    </div>
</template>
