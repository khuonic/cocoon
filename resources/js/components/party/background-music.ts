import { onUnmounted, ref } from 'vue';

/** Volume de la musique de fond (0 à 1). */
const MUSIC_VOLUME = 0.18;

/** En dessous de ce niveau, un échantillon est considéré comme du silence. */
const SILENCE_THRESHOLD = 0.002;

/**
 * Musique de fond optionnelle d'un jeu : jouée en boucle si le fichier existe, silence sinon.
 *
 * Lecture par la Web Audio API (et non une balise <audio>) pour une boucle sans coupure :
 * les silences ajoutés par l'encodeur MP3 au début et à la fin sont retirés de la boucle.
 * `start()` doit être appelé pendant un geste de l'utilisateur (tap sur JOUER) pour les navigateurs mobiles.
 */
export function useBackgroundMusic(src: string, muteKey: string) {
    const isAvailable = ref(false);
    const isMuted = ref(readMuted());

    let context: AudioContext | null = null;
    let gain: GainNode | null = null;
    let buffer: AudioBuffer | null = null;
    let loading: Promise<void> | null = null;
    let source: AudioBufferSourceNode | null = null;
    let isPlaying = false;

    function readMuted(): boolean {
        try {
            return localStorage.getItem(muteKey) === '1';
        } catch {
            return false;
        }
    }

    function ensureContext(): AudioContext | null {
        if (context) {
            return context;
        }

        const AudioContextClass =
            window.AudioContext ??
            (window as unknown as { webkitAudioContext?: typeof AudioContext })
                .webkitAudioContext;

        if (!AudioContextClass) {
            return null;
        }

        context = new AudioContextClass();
        gain = context.createGain();
        gain.gain.value = isMuted.value ? 0 : MUSIC_VOLUME;
        gain.connect(context.destination);

        return context;
    }

    function load(audioContext: AudioContext): Promise<void> {
        loading ??= (async () => {
            try {
                const response = await fetch(src);
                if (!response.ok) {
                    return;
                }
                buffer = await audioContext.decodeAudioData(
                    await response.arrayBuffer(),
                );
                isAvailable.value = true;
            } catch {
                // fichier absent ou illisible : on reste en silence
            }
        })();

        return loading;
    }

    /** Début et fin du son réel, sans les silences de l'encodeur. */
    function loopBounds(audioBuffer: AudioBuffer): {
        start: number;
        end: number;
    } {
        const samples = audioBuffer.getChannelData(0);
        let first = 0;
        let last = samples.length - 1;

        while (first < last && Math.abs(samples[first]) < SILENCE_THRESHOLD) {
            first++;
        }
        while (last > first && Math.abs(samples[last]) < SILENCE_THRESHOLD) {
            last--;
        }

        return {
            start: first / audioBuffer.sampleRate,
            end: (last + 1) / audioBuffer.sampleRate,
        };
    }

    function playBuffer(): void {
        if (!context || !gain || !buffer || !isPlaying || source) {
            return;
        }

        const bounds = loopBounds(buffer);
        source = context.createBufferSource();
        source.buffer = buffer;
        source.loop = true;
        source.loopStart = bounds.start;
        source.loopEnd = bounds.end;
        source.connect(gain);
        source.start(0, bounds.start);
    }

    function start(): void {
        const audioContext = ensureContext();
        if (!audioContext) {
            return;
        }

        void audioContext.resume();
        isPlaying = true;
        void load(audioContext).then(playBuffer);
    }

    function stop(): void {
        isPlaying = false;
        source?.stop();
        source?.disconnect();
        source = null;
    }

    function toggleMute(): void {
        isMuted.value = !isMuted.value;

        try {
            localStorage.setItem(muteKey, isMuted.value ? '1' : '0');
        } catch {
            // stockage indisponible : le choix vaut pour cette visite seulement
        }

        if (gain && context) {
            gain.gain.setTargetAtTime(
                isMuted.value ? 0 : MUSIC_VOLUME,
                context.currentTime,
                0.05,
            );
        }
    }

    onUnmounted(() => {
        stop();
        void context?.close();
        context = null;
    });

    return { isAvailable, isMuted, start, stop, toggleMute };
}
