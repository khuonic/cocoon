/**
 * Sons du casse-brique.
 *
 * Chaque événement a un fichier dans public/sounds/casse-brique/. Tant qu'un fichier est absent,
 * un petit bip rétro généré par le navigateur le remplace. Pour changer un son : déposer le
 * fichier au nom indiqué ci-dessous (mp3 court, idéalement < 1 s et < 50 Ko).
 */
export type SoundName =
    | 'launch'
    | 'bounce'
    | 'brick'
    | 'hardBrick'
    | 'bonus'
    | 'malus'
    | 'shot'
    | 'lifeLost'
    | 'levelClear'
    | 'win'
    | 'gameOver';

const SOUND_DIRECTORY = '/sounds/casse-brique';

export const soundFiles: Record<SoundName, string> = {
    launch: 'lancer.mp3',
    bounce: 'rebond.mp3',
    brick: 'brique.mp3',
    hardBrick: 'brique-dure.mp3',
    bonus: 'bonus.mp3',
    malus: 'malus.mp3',
    shot: 'tir.mp3',
    lifeLost: 'vie-perdue.mp3',
    levelClear: 'niveau.mp3',
    win: 'victoire.mp3',
    gameOver: 'defaite.mp3',
};

type Note = { frequency: number; duration: number; type?: OscillatorType };

/** Bips de secours : suites de notes très courtes, façon console 8 bits. */
const fallbackNotes: Record<SoundName, Note[]> = {
    launch: [{ frequency: 880, duration: 0.05 }],
    bounce: [{ frequency: 440, duration: 0.04 }],
    brick: [{ frequency: 660, duration: 0.05 }],
    hardBrick: [{ frequency: 260, duration: 0.06 }],
    bonus: [
        { frequency: 523, duration: 0.06 },
        { frequency: 659, duration: 0.06 },
        { frequency: 784, duration: 0.09 },
    ],
    malus: [
        { frequency: 300, duration: 0.1, type: 'sawtooth' },
        { frequency: 160, duration: 0.18, type: 'sawtooth' },
    ],
    shot: [
        { frequency: 1200, duration: 0.03, type: 'triangle' },
        { frequency: 700, duration: 0.03, type: 'triangle' },
    ],
    lifeLost: [
        { frequency: 494, duration: 0.1 },
        { frequency: 392, duration: 0.1 },
        { frequency: 262, duration: 0.2 },
    ],
    levelClear: [
        { frequency: 523, duration: 0.08 },
        { frequency: 659, duration: 0.08 },
        { frequency: 784, duration: 0.08 },
        { frequency: 1047, duration: 0.16 },
    ],
    win: [
        { frequency: 523, duration: 0.1 },
        { frequency: 659, duration: 0.1 },
        { frequency: 784, duration: 0.1 },
        { frequency: 1047, duration: 0.12 },
        { frequency: 784, duration: 0.08 },
        { frequency: 1047, duration: 0.3 },
    ],
    gameOver: [
        { frequency: 392, duration: 0.18 },
        { frequency: 330, duration: 0.18 },
        { frequency: 262, duration: 0.18 },
        { frequency: 196, duration: 0.4 },
    ],
};

/** Intervalle minimum entre deux mêmes sons (évite la cacophonie en multi-balles). */
const MIN_INTERVAL_MS = 40;
const MUTE_KEY = 'party_breakout_muted';

export class SoundPlayer {
    private context: AudioContext | null = null;
    private buffers = new Map<SoundName, AudioBuffer>();
    private lastPlayedAt = new Map<SoundName, number>();
    private isLoaded = false;
    isMuted: boolean;

    constructor() {
        this.isMuted = SoundPlayer.readMuted();
    }

    /**
     * À appeler pendant un geste de l'utilisateur (tap sur JOUER) : les navigateurs mobiles
     * n'autorisent le son qu'à partir de là.
     */
    unlock(): void {
        const AudioContextClass =
            window.AudioContext ??
            (window as unknown as { webkitAudioContext?: typeof AudioContext })
                .webkitAudioContext;

        if (!AudioContextClass) {
            return;
        }

        this.context ??= new AudioContextClass();
        void this.context.resume();

        if (!this.isLoaded) {
            this.isLoaded = true;
            void this.loadFiles();
        }
    }

    play(name: SoundName): void {
        if (this.isMuted || !this.context) {
            return;
        }

        const now = performance.now();
        if (now - (this.lastPlayedAt.get(name) ?? 0) < MIN_INTERVAL_MS) {
            return;
        }
        this.lastPlayedAt.set(name, now);

        const buffer = this.buffers.get(name);
        if (buffer) {
            const source = this.context.createBufferSource();
            source.buffer = buffer;
            source.connect(this.context.destination);
            source.start();
            return;
        }

        this.playNotes(fallbackNotes[name]);
    }

    toggleMute(): boolean {
        this.isMuted = !this.isMuted;
        try {
            localStorage.setItem(MUTE_KEY, this.isMuted ? '1' : '0');
        } catch {
            // stockage indisponible : le choix vaut pour cette partie seulement
        }

        return this.isMuted;
    }

    destroy(): void {
        void this.context?.close();
        this.context = null;
    }

    private async loadFiles(): Promise<void> {
        await Promise.all(
            (Object.keys(soundFiles) as SoundName[]).map(async (name) => {
                try {
                    const response = await fetch(
                        `${SOUND_DIRECTORY}/${soundFiles[name]}`,
                    );
                    if (!response.ok || !this.context) {
                        return;
                    }
                    const data = await response.arrayBuffer();
                    this.buffers.set(
                        name,
                        await this.context.decodeAudioData(data),
                    );
                } catch {
                    // fichier absent ou illisible : le bip de secours reste utilisé
                }
            }),
        );
    }

    private playNotes(notes: Note[]): void {
        const context = this.context!;
        let start = context.currentTime;

        for (const note of notes) {
            const oscillator = context.createOscillator();
            const gain = context.createGain();

            oscillator.type = note.type ?? 'square';
            oscillator.frequency.value = note.frequency;
            gain.gain.setValueAtTime(0.06, start);
            gain.gain.exponentialRampToValueAtTime(
                0.0001,
                start + note.duration,
            );

            oscillator.connect(gain).connect(context.destination);
            oscillator.start(start);
            oscillator.stop(start + note.duration);
            start += note.duration;
        }
    }

    private static readMuted(): boolean {
        try {
            return localStorage.getItem(MUTE_KEY) === '1';
        } catch {
            return false;
        }
    }
}
