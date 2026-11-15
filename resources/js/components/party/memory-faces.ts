import type { SpriteName } from './sprites';

/**
 * Faces pixel art du memory, dans l'ordre d'utilisation :
 * 12 cartes = les 6 premières, 16 = les 8 premières, 20 = toutes.
 * L'index correspond à la valeur envoyée par le serveur dans le paquet (App\Party\Games\Memory::PIXEL_FACES).
 */
export const memoryFaces: { sprite: SpriteName; px: number; label: string }[] =
    [
        { sprite: 'pinte', px: 5, label: 'Pinte' },
        { sprite: 'falafel', px: 6, label: 'Falafel' },
        { sprite: 'caca_pq', px: 5, label: 'Caca' },
        { sprite: 'ventouse', px: 4, label: 'Ventouse' },
        { sprite: 'scooter', px: 4, label: 'Scooter' },
        { sprite: 'gateau', px: 5, label: 'Gâteau' },
        { sprite: 'voiture', px: 3, label: 'Voiture' },
        { sprite: 'toilettes', px: 4, label: 'Toilettes' },
        { sprite: 'lunettes', px: 5, label: 'Lunettes thug life' },
        { sprite: 'sage', px: 3, label: 'Antoine hipster' },
    ];
