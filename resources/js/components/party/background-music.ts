import { onUnmounted, ref } from 'vue';

/**
 * Musique de fond optionnelle d'un jeu : jouée en boucle si le fichier existe, silence sinon.
 * `start()` doit être appelé pendant un geste de l'utilisateur (tap sur JOUER) pour les navigateurs mobiles.
 */
export function useBackgroundMusic(src: string, muteKey: string) {
    const isAvailable = ref(false);
    const isMuted = ref(readMuted());
    let audio: HTMLAudioElement | null = null;
    let isPlaying = false;

    function readMuted(): boolean {
        try {
            return localStorage.getItem(muteKey) === '1';
        } catch {
            return false;
        }
    }

    function ensureAudio(): HTMLAudioElement {
        if (!audio) {
            audio = new Audio(src);
            audio.loop = true;
            audio.volume = 0.35;
            audio.addEventListener('canplay', () => (isAvailable.value = true));
            audio.addEventListener('error', () => (isAvailable.value = false));
        }

        return audio;
    }

    function start(): void {
        const element = ensureAudio();
        element.currentTime = 0;
        isPlaying = true;

        if (isMuted.value) {
            // Charge quand même le fichier pour savoir s'il existe (et afficher le bouton son).
            element.load();
        } else {
            element.play().catch(() => {
                // fichier absent ou lecture refusée : on reste en silence
            });
        }
    }

    function stop(): void {
        isPlaying = false;
        audio?.pause();
    }

    function toggleMute(): void {
        isMuted.value = !isMuted.value;

        try {
            localStorage.setItem(muteKey, isMuted.value ? '1' : '0');
        } catch {
            // stockage indisponible : le choix vaut pour cette visite seulement
        }

        if (isMuted.value) {
            audio?.pause();
        } else if (isPlaying) {
            audio?.play().catch(() => {});
        }
    }

    onUnmounted(() => {
        audio?.pause();
        audio = null;
    });

    return { isAvailable, isMuted, start, stop, toggleMute };
}
