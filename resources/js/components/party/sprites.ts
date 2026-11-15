/**
 * Sprites pixel art du mini-site, dessinés en grilles de caractères.
 * Chaque caractère est une couleur de la palette ('.' = transparent).
 * Pour retoucher un sprite : modifier les lignes (même longueur pour toutes).
 */

export type Sprite = {
    label: string;
    palette?: Record<string, string>;
    rows: string[];
};

export const basePalette: Record<string, string> = {
    k: '#1B1B2F',
    s: '#F1C27D',
    S: '#D9A066',
    h: '#5B3A29',
    w: '#FFF8E7',
    r: '#C8553D',
    b: '#3D2C2E',
    p: '#3A5A8C',
    g: '#F2B134',
};

const enfantRows = [
    '....hhhhhh....',
    '...hhhhhhhh...',
    '..hhhhhhhhhh..',
    '..hhsssssshh..',
    '..hsksssskSh..',
    '..hssssssssh..',
    '..hsssrrsssh..',
    '..h.ssssss.h..',
    '..htttttttth..',
    '.tttttttttttt.',
    'stttttttttttts',
    's.tttttttttt.s',
    '...pppppppp...',
    '...ppp..ppp...',
    '...sss..sss...',
    '..bbbb..bbbb..',
];

const adulteRows = [
    '...hhhhhh.....',
    '..hhhhhhhh....',
    '..hhhhhhhhhh..',
    '..hssssssh.hh.',
    '..sksssskS..h.',
    '..ssssssss..h.',
    '..hssrrssh....',
    '..hhhhhhhh....',
    '..hhhhhhhh....',
    '...hhhhhh.....',
    '..tttwwttt....',
    '.tttttttttt...',
    '.tttttttttt...',
    '.tTttttttTt...',
    '.s.tttttt.s...',
    '...pppppp.....',
    '...pppppp.....',
    '...pp..pp.....',
    '...pp..pp.....',
    '..bbb..bbb....',
];

const voitureRows = [
    '.....kkkkkkkk.......',
    '....kwwwkkwwwk......',
    '...kkkkkkkkkkkkkk...',
    '.kqqqqqqqqqqqqqqqqk.',
    'kqqqqqqqqqqqqqqqqqqk',
    'kqqqqqqqqqqqqqqqqqqk',
    '.kkkkkkkkkkkkkkkkkk.',
    '....kwwk.....kwwk...',
    '.....kk.......kk....',
];

export const sprites = {
    enfant: {
        label: 'Antoine enfant',
        palette: { t: '#E4572E' },
        rows: enfantRows,
    },
    enfant_crotte: {
        label: 'Antoine enfant colle une crotte de nez derrière le lit',
        palette: { t: '#E4572E', G: '#7FB800' },
        rows: enfantRows.map((row, index) => {
            if (index === 10) {
                return 'Gsss' + row;
            }
            if (index === 11) {
                return '......tttttttttt.s';
            }
            return '....' + row;
        }),
    },
    ado: {
        label: 'Antoine ado skater',
        palette: { c: '#E4572E', t: '#2A9D8F', T: '#1F7A6F' },
        rows: [
            '...cccccc...',
            '..cccccccc..',
            '.hhhhhhhhhh.',
            '.hhsssssshh.',
            '.hsksssskSh.',
            '.hssssssssh.',
            '.hsssrrsssh.',
            '.h..ssss..h.',
            '.htttttttth.',
            'tttttttttttt',
            'tTttttttttTt',
            'stTttttttTts',
            's.tttttttt.s',
            '..pppppppp..',
            '..pppppppp..',
            '..ppp..ppp..',
            '..ppp..ppp..',
            '.wwww..wwww.',
        ],
    },
    ado_scooter: {
        label: 'Antoine ado sur le scooter, lunettes thug life, genou en sang, pouce levé',
        palette: {
            q: '#E4572E',
            t: '#2A9D8F',
            T: '#1F7A6F',
            W: '#FFFFFF',
            R: '#B3122E',
        },
        rows: [
            '...qqqqqq...',
            '..qqqqqqqq..',
            '.qqqqqqqqqq.',
            '.qhsssssshq.',
            '.hkwkkkwkkh.',
            '.hssssssssh.',
            '.hssssrrssh.',
            '.h..ssss..hs',
            '.httttttttss',
            'ttttttttttt.',
            'tTttttttttT.',
            'stTttttttTt.',
            's.tttttttt..',
            '..ppppppppp.',
            '..pRWpppppp.',
        ],
    },
    adulte: {
        label: 'Antoine adulte, barbe épaisse, queue de cheval',
        palette: { t: '#4F6D9A', T: '#3B5579' },
        rows: adulteRows,
    },
    adulte_croque: {
        label: 'Antoine adulte, bouche grande ouverte',
        palette: { t: '#4F6D9A', T: '#3B5579' },
        rows: adulteRows.map((row, index) =>
            index === 6 ? '..hskkkksh....' : row,
        ),
    },
    rambo: {
        label: 'Antoine rampe au sol façon Rambo',
        palette: { q: '#C8553D', t: '#4F6D9A' },
        rows: [
            '...................hhh..',
            '..................qqqqqh',
            '..................sksss.',
            '..pppppptttttttttthhhhh.',
            '.bpppppptttttttttthhhh..',
            'bb..........s....s......',
        ],
    },
    sage: {
        label: 'Antoine à 35 ans, hipster : bonnet, barbe grise courte, pull, une pinte à la main',
        palette: {
            n: '#C8553D',
            N: '#9E3F2B',
            G: '#B9B8C4',
            v: '#2A9D8F',
            V: '#1F7A6F',
            p: '#2B2D42',
            y: '#E9A23B',
            Y: '#C47F1A',
        },
        rows: [
            '.......nn.......',
            '.....nnnnnn.....',
            '....nnnnnnnn....',
            '....NNNNNNNN....',
            '....sksssskS....',
            '....ssssssss....',
            '....GssrrssG....',
            '.....GGGGGG.....',
            '......ssss...www',
            '...vvvvvvvvvvyyy',
            '..vvvvvvvvvvsyyy',
            '..vVvvvvvvVvsyyy',
            '..s.vvvvvvvv.yyy',
            '....VVVVVVVV.YYY',
            '....pppppppp....',
            '....pppppppp....',
            '....ppp..ppp....',
            '....ppp..ppp....',
            '....ppp..ppp....',
            '...bbbb.bbbb....',
        ],
    },
    ballon_rouge: {
        label: 'Ballon',
        palette: { q: '#E4572E' },
        rows: [
            '..qqq..',
            '.qqqqq.',
            'qqwqqqq',
            'qqqqqqq',
            'qqqqqqq',
            '.qqqqq.',
            '..qqq..',
            '...k...',
            '...k...',
            '..k....',
        ],
    },
    ballon_bleu: {
        label: 'Ballon',
        palette: { q: '#8ECAE6' },
        rows: [
            '..qqq..',
            '.qqqqq.',
            'qqwqqqq',
            'qqqqqqq',
            'qqqqqqq',
            '.qqqqq.',
            '..qqq..',
            '...k...',
            '...k...',
            '....k..',
        ],
    },
    gateau: {
        label: 'Gâteau d’anniversaire',
        palette: { c: '#F7A8B8', m: '#8B5A3C' },
        rows: [
            '..g...g...g.',
            '..w...w...w.',
            '..w...w...w.',
            '.cccccccccc.',
            '.cmcmccmcmc.',
            '.mmmmmmmmmm.',
            '.mgmmgmmgmm.',
            '.mmmmmmmmmm.',
            'wwwwwwwwwwww',
        ],
    },
    scooter: {
        label: 'Scooter du BSR',
        palette: { q: '#E4572E' },
        rows: [
            '.........kk.....',
            '..........k.....',
            '..........k.....',
            '.qqqqq....k.....',
            'qqqqqqqqqqqqq...',
            '.qqqqqqqqqqqqq..',
            '..kk.......kk...',
            '.kwwk.....kwwk..',
            '.kwwk.....kwwk..',
            '..kk.......kk...',
        ],
    },
    toilettes: {
        label: 'Toilettes',
        palette: { L: '#B9B8C4', e: '#8ECAE6' },
        rows: [
            'kkkkkkk.....',
            'kwwwwLk.....',
            'kwwwwLk.....',
            'kwwwwLk.....',
            'kkkkkkkkkkkk',
            'kwwwwwwwwwLk',
            'kweeeeeeeeLk',
            'kwwwwwwwwwLk',
            '.kwwwwwwwLk.',
            '..kwwwwwLk..',
            '...kwwwLk...',
            '..kwwwwwLk..',
            '..kkkkkkkk..',
        ],
    },
    ventouse: {
        label: 'Ventouse',
        palette: { y: '#C8A060', q: '#C8553D' },
        rows: [
            '.yy.',
            '.yy.',
            '.yy.',
            '.yy.',
            '.yy.',
            '.yy.',
            '.yy.',
            '.yy.',
            'qqqq',
            'qqqq',
            'qqqq',
        ],
    },
    caca_pq: {
        label: 'Le caca sur du papier toilette',
        palette: { d: '#7A4A26' },
        rows: ['..dd..', '.dddd.', 'dddddd', 'wwwwww'],
    },
    falafel_main: {
        label: 'La main du patron tend un falafel',
        palette: { f: '#A0702A' },
        rows: [
            '..ff......',
            '.ffff.....',
            '.ffffsssss',
            '..ffssssss',
            '....ssssss',
        ],
    },
    voiture: {
        label: 'La voiture des parents',
        palette: { q: '#8ECAE6' },
        rows: voitureRows,
    },
    voiture_garee: {
        label: 'Voiture garée',
        palette: { q: '#B9B8C4' },
        rows: voitureRows,
    },
    boum: {
        label: 'Choc',
        palette: { q: '#E4572E' },
        rows: [
            '...g...',
            '.g.g.g.',
            '..ggg..',
            'gggqggg',
            '..ggg..',
            '.g.g.g.',
            '...g...',
        ],
    },
} satisfies Record<string, Sprite>;

export type SpriteName = keyof typeof sprites;
