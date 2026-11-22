import type { DecorName } from './backgrounds';

/**
 * Niveaux du casse-brique, un par anecdote d'Antoine (App\Party\Games\CasseBrique::LEVELS).
 *
 * Grille de 8 colonnes : '.' vide · '1'…'6' brique de couleur · '#' brique dure (2 coups).
 * `decor` : décor pixel art du fond (backgrounds.ts), teinté par `background`.
 * Les bonus ne sont pas dessinés : `bonusCount` briques en cachent un, tiré au hasard à chaque partie.
 */
export type BreakoutLevel = {
    name: string;
    background: string;
    decor: DecorName;
    bonusCount: number;
    rows: string[];
};

export const levels: BreakoutLevel[] = [
    {
        name: 'LA CHAMBRE',
        decor: 'chambre',
        background: '#3B3355',
        bonusCount: 5,
        rows: ['11111111', '22222222', '33333333', '44444444'],
    },
    {
        name: 'LE BSR',
        decor: 'bsr',
        background: '#2E3A4F',
        bonusCount: 5,
        rows: ['55555555', '5..55..5', '66666666', '666..666', '33333333'],
    },
    {
        name: 'LE CAP CAVAL',
        decor: 'capCaval',
        background: '#1E1E36',
        bonusCount: 6,
        rows: ['1.2.3.4.', '.5.6.1.2', '########', '44444444', '55555555'],
    },
    {
        name: 'LE BRÉSIL',
        decor: 'bresil',
        background: '#1F4A45',
        bonusCount: 6,
        rows: [
            '..3333..',
            '.333333.',
            '33#33#33',
            '33333333',
            '.333333.',
            '..3333..',
        ],
    },
    {
        name: 'LE FALAFEL',
        decor: 'falafel',
        background: '#4A3424',
        bonusCount: 7,
        rows: [
            '22222222',
            '#222222#',
            '22222222',
            '1#1111#1',
            '11111111',
            '11111111',
        ],
    },
    {
        name: 'LE MARIAGE',
        decor: 'mariage',
        background: '#2A2240',
        bonusCount: 7,
        rows: [
            '5.5.5.5.',
            '########',
            '66666666',
            '66666666',
            '#4####4#',
            '44444444',
            '44444444',
        ],
    },
];
