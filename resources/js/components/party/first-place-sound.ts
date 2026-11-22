/**
 * Son joué sur la page de résultat quand le joueur prend la 1re place du classement.
 *
 * Fichier : public/sounds/resultat/premier.mp3. Tant qu'il est absent, une petite fanfare
 * 8 bits générée par le navigateur le remplace. Joué une seule fois par partie (pas à chaque
 * rechargement de la page).
 */
const SOUND_URL = '/sounds/resultat/premier.mp3';
const PLAYED_KEY_PREFIX = 'party_first_place_played_';

/** Fanfare de secours : [fréquence en Hz, durée en secondes]. */
const FANFARE: [number, number][] = [
    [523, 0.12],
    [523, 0.12],
    [523, 0.12],
    [659, 0.3],
    [587, 0.12],
    [659, 0.12],
    [784, 0.45],
];

export function playFirstPlaceSound(runUuid: string): void {
    const key = PLAYED_KEY_PREFIX + runUuid;

    try {
        if (sessionStorage.getItem(key) === '1') {
            return;
        }
        sessionStorage.setItem(key, '1');
    } catch {
        // stockage indisponible : le son pourra rejouer au rechargement, sans gravité
    }

    const audio = new Audio(SOUND_URL);
    audio.volume = 0.8;
    audio.play().catch((error: unknown) => {
        // Lecture refusée par le navigateur (pas encore d'interaction) : on n'insiste pas.
        if (error instanceof DOMException && error.name === 'NotAllowedError') {
            return;
        }

        playFanfare();
    });
}

function playFanfare(): void {
    const AudioContextClass =
        window.AudioContext ??
        (window as unknown as { webkitAudioContext?: typeof AudioContext })
            .webkitAudioContext;

    if (!AudioContextClass) {
        return;
    }

    const context = new AudioContextClass();
    let start = context.currentTime + 0.05;

    for (const [frequency, duration] of FANFARE) {
        const oscillator = context.createOscillator();
        const gain = context.createGain();

        oscillator.type = 'square';
        oscillator.frequency.value = frequency;
        gain.gain.setValueAtTime(0.07, start);
        gain.gain.exponentialRampToValueAtTime(0.0001, start + duration * 0.95);

        oscillator.connect(gain).connect(context.destination);
        oscillator.start(start);
        oscillator.stop(start + duration);
        start += duration;
    }

    window.setTimeout(
        () => void context.close(),
        (start - context.currentTime + 0.2) * 1000,
    );
}
