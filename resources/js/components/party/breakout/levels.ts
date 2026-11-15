/**
 * Niveaux du casse-brique, un par anecdote d'Antoine (App\Party\Games\CasseBrique::LEVELS).
 *
 * Grille de 8 colonnes :
 *  '.' vide · '1'…'6' brique de couleur · '#' brique dure (2 coups)
 *  'V' ventouse · 'F' falafel · 'P' pinte · 'S' scooter · 'C' caca (briques bonus)
 */
export type BreakoutLevel = {
    name: string;
    background: string;
    rows: string[];
};

export const levels: BreakoutLevel[] = [
    {
        name: 'LA CHAMBRE',
        background: '#3B3355',
        rows: ['11111111', '2222F222', '33333333', '4V444P44'],
    },
    {
        name: 'LE BSR',
        background: '#2E3A4F',
        rows: ['55555555', '5..CC..5', '66666666', '6P6..6S6', '33333333'],
    },
    {
        name: 'LE CAP CAVAL',
        background: '#1E1E36',
        rows: ['1.2.3.4.', '.F.V.P.S', '########', '44444444', '5C5555C5'],
    },
    {
        name: 'LE BRÉSIL',
        background: '#1F4A45',
        rows: [
            '..3333..',
            '.333V33.',
            '33#33#33',
            '3C3333P3',
            '.333333.',
            '..3F33..',
        ],
    },
    {
        name: 'LE FALAFEL',
        background: '#4A3424',
        rows: [
            '2222222S',
            '#2F22F2#',
            '22222222',
            '1#1111#1',
            '1C1P11C1',
            '11111111',
        ],
    },
    {
        name: 'LE MARIAGE',
        background: '#2A2240',
        rows: [
            '5.5.5.5.',
            '########',
            '6F6S6P6V',
            '66666666',
            '#C####C#',
            '44444444',
            '4444F444',
        ],
    },
];
