/**
 * Décors pixel art des niveaux du casse-brique.
 *
 * Chaque décor est dessiné une seule fois dans un canvas hors écran à la taille du monde logique
 * (1 unité = 1 pixel), puis recopié à chaque frame : aucun coût pendant la partie.
 * Les motifs restent discrets et plutôt sous la zone des briques pour ne pas gêner la lecture.
 */

export type DecorName =
    | 'chambre'
    | 'bsr'
    | 'capCaval'
    | 'bresil'
    | 'falafel'
    | 'mariage';

type Context = CanvasRenderingContext2D;

const WIDTH = 360;
const HEIGHT = 560;

const cache = new Map<string, HTMLCanvasElement>();

/** Éclaircit (amount > 0) ou assombrit (amount < 0) une couleur hexadécimale. */
export function shade(hex: string, amount: number): string {
    const value = parseInt(hex.replace('#', ''), 16);
    const channels = [(value >> 16) & 255, (value >> 8) & 255, value & 255];
    const target = amount > 0 ? 255 : 0;
    const ratio = Math.min(1, Math.abs(amount));

    return `#${channels
        .map((channel) =>
            Math.round(channel + (target - channel) * ratio)
                .toString(16)
                .padStart(2, '0'),
        )
        .join('')}`;
}

export function renderBackground(
    decor: DecorName,
    baseColor: string,
): HTMLCanvasElement {
    const key = `${decor}:${baseColor}`;
    const cached = cache.get(key);
    if (cached) {
        return cached;
    }

    const canvas = document.createElement('canvas');
    canvas.width = WIDTH;
    canvas.height = HEIGHT;
    const context = canvas.getContext('2d')!;
    const random = seededRandom(decor.length * 7919 + baseColor.length);

    drawGradient(context, baseColor);
    decorPainters[decor](context, baseColor, random);
    drawVignette(context);

    cache.set(key, canvas);

    return canvas;
}

/* ---------- Base commune ---------- */

/** Dégradé vertical en bandes, avec une ligne de tramage entre deux bandes. */
function drawGradient(context: Context, baseColor: string): void {
    const bands = 8;
    const bandHeight = HEIGHT / bands;

    for (let index = 0; index < bands; index++) {
        const amount = -0.3 + (index / (bands - 1)) * 0.38;
        const color =
            amount < 0
                ? shade(baseColor, amount)
                : shade(baseColor, amount * 0.5);
        context.fillStyle = color;
        context.fillRect(0, index * bandHeight, WIDTH, bandHeight);

        if (index > 0) {
            context.fillStyle = color;
            for (let x = 0; x < WIDTH; x += 4) {
                context.fillRect(x, index * bandHeight - 2, 2, 2);
                context.fillRect(x + 2, index * bandHeight - 4, 2, 2);
            }
        }
    }
}

/** Bords légèrement assombris pour recentrer le regard sur le jeu. */
function drawVignette(context: Context): void {
    context.fillStyle = 'rgba(0, 0, 0, 0.18)';
    context.fillRect(0, 0, 6, HEIGHT);
    context.fillRect(WIDTH - 6, 0, 6, HEIGHT);
    context.fillStyle = 'rgba(0, 0, 0, 0.1)';
    context.fillRect(6, 0, 4, HEIGHT);
    context.fillRect(WIDTH - 10, 0, 4, HEIGHT);
}

/* ---------- Outils de dessin ---------- */

function seededRandom(seed: number): () => number {
    let state = seed;

    return () => {
        state = (state * 1664525 + 1013904223) % 4294967296;

        return state / 4294967296;
    };
}

function disc(
    context: Context,
    centerX: number,
    centerY: number,
    radius: number,
): void {
    for (let dy = -radius; dy <= radius; dy++) {
        const half = Math.floor(Math.sqrt(radius * radius - dy * dy));
        context.fillRect(centerX - half, centerY + dy, half * 2, 1);
    }
}

function stars(
    context: Context,
    random: () => number,
    count: number,
    maxY: number,
): void {
    for (let index = 0; index < count; index++) {
        const x = Math.floor(random() * WIDTH);
        const y = 210 + Math.floor(random() * (maxY - 210));
        const isBig = random() > 0.8;
        context.fillStyle = `rgba(255, 248, 231, ${isBig ? 0.55 : 0.3})`;
        context.fillRect(x, y, isBig ? 2 : 1, isBig ? 2 : 1);
    }
}

function heart(context: Context, x: number, y: number, size: number): void {
    const rows = [
        '.##.##.',
        '#######',
        '#######',
        '.#####.',
        '..###..',
        '...#...',
    ];
    rows.forEach((row, rowIndex) => {
        [...row].forEach((char, column) => {
            if (char === '#') {
                context.fillRect(
                    x + column * size,
                    y + rowIndex * size,
                    size,
                    size,
                );
            }
        });
    });
}

/** Dessine une petite grille de caractères ('.' = vide), chaque case faisant `size` pixels. */
function pixelArt(
    context: Context,
    rows: string[],
    x: number,
    y: number,
    size: number,
    palette: Record<string, string>,
): void {
    rows.forEach((row, rowIndex) => {
        [...row].forEach((char, column) => {
            if (palette[char]) {
                context.fillStyle = palette[char];
                context.fillRect(
                    x + column * size,
                    y + rowIndex * size,
                    size,
                    size,
                );
            }
        });
    });
}

/* ---------- Décors ---------- */

const decorPainters: Record<
    DecorName,
    (context: Context, baseColor: string, random: () => number) => void
> = {
    /** Papier peint à rayures, fenêtre de nuit, poster et parquet. */
    chambre(context, baseColor) {
        context.fillStyle = 'rgba(255, 248, 231, 0.04)';
        for (let x = 8; x < WIDTH; x += 28) {
            context.fillRect(x, 0, 10, 470);
        }

        // Fenêtre
        context.fillStyle = shade(baseColor, -0.45);
        context.fillRect(236, 236, 84, 72);
        context.fillStyle = '#1B1B2F';
        context.fillRect(242, 242, 72, 60);
        context.fillStyle = 'rgba(255, 248, 231, 0.75)';
        disc(context, 296, 258, 7);
        context.fillStyle = '#1B1B2F';
        disc(context, 299, 255, 6);
        context.fillStyle = shade(baseColor, -0.45);
        context.fillRect(276, 242, 4, 60);
        context.fillRect(242, 270, 72, 4);

        // Poster
        context.fillStyle = 'rgba(242, 177, 52, 0.35)';
        context.fillRect(40, 250, 52, 68);
        context.fillStyle = 'rgba(27, 27, 47, 0.35)';
        context.fillRect(48, 260, 36, 30);
        context.fillRect(48, 296, 36, 4);
        context.fillRect(48, 304, 24, 4);

        // Parquet
        context.fillStyle = shade(baseColor, -0.35);
        context.fillRect(0, 470, WIDTH, HEIGHT - 470);
        context.fillStyle = 'rgba(0, 0, 0, 0.2)';
        for (let y = 470; y < HEIGHT; y += 14) {
            context.fillRect(0, y, WIDTH, 2);
            const offset = ((y - 470) / 14) % 2 === 0 ? 0 : 30;
            for (let x = offset; x < WIDTH; x += 60) {
                context.fillRect(x, y, 2, 14);
            }
        }
    },

    /** Ville la nuit, lampadaires et route à pointillés. */
    bsr(context, baseColor, random) {
        stars(context, random, 40, 330);

        let x = 0;
        while (x < WIDTH) {
            const buildingWidth = 30 + Math.floor(random() * 30);
            const buildingHeight = 70 + Math.floor(random() * 90);
            const top = 470 - buildingHeight;
            context.fillStyle = shade(baseColor, -0.5);
            context.fillRect(x, top, buildingWidth - 4, buildingHeight);
            for (let wy = top + 8; wy < 460; wy += 14) {
                for (let wx = x + 5; wx < x + buildingWidth - 10; wx += 10) {
                    if (random() > 0.6) {
                        context.fillStyle = 'rgba(242, 177, 52, 0.4)';
                        context.fillRect(wx, wy, 4, 6);
                    }
                }
            }
            x += buildingWidth;
        }

        for (const lampX of [60, 300]) {
            context.fillStyle = '#1B1B2F';
            context.fillRect(lampX, 400, 3, 70);
            context.fillRect(lampX - 6, 400, 12, 4);
            context.fillStyle = 'rgba(242, 177, 52, 0.12)';
            context.fillRect(lampX - 16, 404, 34, 66);
            context.fillStyle = 'rgba(242, 177, 52, 0.8)';
            context.fillRect(lampX - 4, 404, 8, 3);
        }

        context.fillStyle = '#26262F';
        context.fillRect(0, 470, WIDTH, HEIGHT - 470);
        context.fillStyle = 'rgba(242, 177, 52, 0.5)';
        for (let dashX = 6; dashX < WIDTH; dashX += 36) {
            context.fillRect(dashX, 540, 18, 4);
        }
    },

    /** Discothèque : boule à facettes, faisceaux colorés, cabine du DJ, enceintes et piste lumineuse. */
    capCaval(context, baseColor, random) {
        const beamColors = [
            'rgba(247, 168, 184, 0.13)',
            'rgba(42, 157, 143, 0.13)',
            'rgba(242, 177, 52, 0.12)',
            'rgba(179, 156, 208, 0.14)',
        ];

        // Faisceaux qui partent de la boule vers la piste
        [-150, -70, 20, 110, 170].forEach((spread, index) => {
            context.fillStyle = beamColors[index % beamColors.length];
            for (let y = 216; y < 500; y += 2) {
                const progress = (y - 216) / 284;
                const center = 180 + spread * progress;
                const half = 3 + progress * 16;
                context.fillRect(
                    Math.round(center - half),
                    y,
                    Math.round(half * 2),
                    2,
                );
            }
        });

        // Boule à facettes
        context.fillStyle = 'rgba(201, 195, 176, 0.5)';
        context.fillRect(179, 170, 2, 30);
        for (let dy = -16; dy <= 16; dy += 4) {
            const half = Math.floor(Math.sqrt(16 * 16 - dy * dy) / 4) * 4;
            for (let dx = -half; dx < half; dx += 4) {
                const isLight = ((dx + dy) / 4) % 2 === 0;
                context.fillStyle = isLight ? '#E4E0F0' : '#8D89A6';
                context.fillRect(180 + dx, 216 + dy, 4, 4);
            }
        }
        context.fillStyle = '#FFFFFF';
        context.fillRect(172, 206, 4, 4);

        // Reflets de la boule sur les murs
        for (let index = 0; index < 45; index++) {
            context.fillStyle = `rgba(255, 248, 231, ${0.15 + random() * 0.35})`;
            context.fillRect(
                Math.floor(random() * WIDTH),
                200 + Math.floor(random() * 220),
                2,
                2,
            );
        }

        // Enceintes
        for (const speakerX of [12, 306]) {
            context.fillStyle = shade(baseColor, -0.5);
            context.fillRect(speakerX, 340, 42, 92);
            context.fillStyle = '#1B1B2F';
            disc(context, speakerX + 21, 362, 10);
            disc(context, speakerX + 21, 404, 15);
            context.fillStyle = 'rgba(141, 137, 166, 0.6)';
            disc(context, speakerX + 21, 362, 4);
            disc(context, speakerX + 21, 404, 6);
        }

        // Cabine du DJ et platines
        context.fillStyle = shade(baseColor, -0.45);
        context.fillRect(120, 392, 120, 40);
        context.fillStyle = 'rgba(247, 168, 184, 0.6)';
        context.fillRect(120, 392, 120, 3);
        context.fillStyle = '#1B1B2F';
        disc(context, 150, 410, 9);
        disc(context, 210, 410, 9);
        context.fillStyle = 'rgba(228, 87, 46, 0.8)';
        context.fillRect(149, 409, 2, 2);
        context.fillRect(209, 409, 2, 2);
        context.fillStyle = 'rgba(42, 157, 143, 0.8)';
        for (let index = 0; index < 4; index++) {
            context.fillRect(
                172 + index * 5,
                404 + (index % 2) * 3,
                3,
                8 - (index % 2) * 3,
            );
        }

        // Piste lumineuse
        const tileColors = ['#F7A8B8', '#2A9D8F', '#F2B134', '#B39CD0'];
        for (let y = 440; y < HEIGHT; y += 20) {
            for (let x = 0; x < WIDTH; x += 30) {
                const colorIndex =
                    (x / 30 + ((y - 440) / 20) * 3) % tileColors.length;
                context.globalAlpha =
                    0.18 + ((x / 30 + (y - 440) / 20) % 2) * 0.12;
                context.fillStyle = tileColors[colorIndex];
                context.fillRect(x + 1, y + 1, 28, 18);
            }
        }
        context.globalAlpha = 1;
    },

    /** Les toilettes de l'anecdote brésilienne : faïence, drapeau, cuvette, papier et ventouse. */
    bresil(context, baseColor) {
        // Faïence murale
        context.fillStyle = 'rgba(255, 248, 231, 0.07)';
        context.fillRect(0, 200, WIDTH, 290);
        context.fillStyle = 'rgba(0, 0, 0, 0.16)';
        for (let y = 200; y < 490; y += 14) {
            context.fillRect(0, y, WIDTH, 1);
        }
        for (let x = 0; x < WIDTH; x += 14) {
            context.fillRect(x, 200, 1, 290);
        }
        context.fillStyle = 'rgba(42, 157, 143, 0.35)';
        context.fillRect(0, 396, WIDTH, 6);

        // Drapeau du Brésil
        context.fillStyle = '#1B1B2F';
        context.fillRect(28, 236, 76, 52);
        context.fillStyle = '#3E8E41';
        context.fillRect(30, 238, 72, 48);
        context.fillStyle = '#F2B134';
        for (let row = 0; row < 20; row++) {
            const half = 30 - Math.abs(row - 10) * 3;
            context.fillRect(66 - half, 252 + row, half * 2, 1);
        }
        context.fillStyle = '#3A5A8C';
        disc(context, 66, 262, 8);
        context.fillStyle = '#FFF8E7';
        context.fillRect(59, 261, 14, 2);

        // Dérouleur de papier toilette
        context.fillStyle = '#8D89A6';
        context.fillRect(212, 404, 22, 3);
        context.fillStyle = '#FFF8E7';
        context.fillRect(214, 407, 18, 14);
        context.fillRect(222, 421, 10, 20);
        context.fillStyle = 'rgba(0, 0, 0, 0.15)';
        context.fillRect(214, 418, 18, 3);

        // Cuvette
        const toilet = [
            '....kkkkkkkkkkkk....',
            '....kwwwwwwwwwwk....',
            '....kwwwwwwwwwwk....',
            '....kwwwwwwwwwwk....',
            '....kkkkkkkkkkkk....',
            '.kkkkkkkkkkkkkkkkkk.',
            'kwwwwwwwwwwwwwwwwwwk',
            'kwwbbbbbbbbbbbbbbwwk',
            '.kwwbbbbbbbbbbbbwwk.',
            '..kwwwwwwwwwwwwwwk..',
            '...kwwwwwwwwwwwwk...',
            '....kkwwwwwwwwkk....',
            '......kwwwwwwk......',
            '......kwwwwwwk......',
            '.....kkkkkkkkkk.....',
        ];
        pixelArt(context, toilet, 262, 430, 4, {
            k: '#1B1B2F',
            w: '#E8E4D8',
            b: '#8ECAE6',
        });

        // Ventouse posée contre le mur
        const plunger = [
            '...b...',
            '...b...',
            '...b...',
            '...b...',
            '...b...',
            '...b...',
            '.rrrrr.',
            'rrrrrrr',
        ];
        pixelArt(context, plunger, 186, 458, 4, { b: '#8B5A2B', r: '#C8553D' });

        // Sol carrelé
        context.fillStyle = shade(baseColor, -0.4);
        context.fillRect(0, 490, WIDTH, HEIGHT - 490);
        for (let y = 490; y < HEIGHT; y += 14) {
            for (let x = 0; x < WIDTH; x += 14) {
                if ((x / 14 + (y - 490) / 14) % 2 === 0) {
                    context.fillStyle = 'rgba(255, 248, 231, 0.12)';
                    context.fillRect(x, y, 14, 14);
                }
            }
        }
    },

    /** Faïence de snack, comptoir et carrelage en damier. */
    falafel(context, baseColor) {
        context.fillStyle = 'rgba(255, 248, 231, 0.06)';
        context.fillRect(0, 220, WIDTH, 220);
        context.fillStyle = 'rgba(0, 0, 0, 0.18)';
        for (let y = 220; y < 440; y += 20) {
            context.fillRect(0, y, WIDTH, 2);
        }
        for (let x = 0; x < WIDTH; x += 20) {
            context.fillRect(x, 220, 2, 220);
        }

        // Enseigne
        context.fillStyle = 'rgba(42, 157, 143, 0.55)';
        context.fillRect(100, 250, 160, 34);
        context.fillStyle = 'rgba(242, 177, 52, 0.75)';
        for (let index = 0; index < 5; index++) {
            disc(context, 128 + index * 26, 267, 7);
        }

        // Comptoir
        context.fillStyle = shade(baseColor, -0.45);
        context.fillRect(0, 440, WIDTH, 26);
        context.fillStyle = 'rgba(255, 248, 231, 0.15)';
        context.fillRect(0, 440, WIDTH, 3);

        // Damier
        for (let y = 466; y < HEIGHT; y += 16) {
            for (let x = 0; x < WIDTH; x += 16) {
                const isDark = (x / 16 + (y - 466) / 16) % 2 === 0;
                context.fillStyle = isDark
                    ? 'rgba(0, 0, 0, 0.3)'
                    : 'rgba(255, 248, 231, 0.08)';
                context.fillRect(x, y, 16, 16);
            }
        }
    },

    /** Guirlandes de fanions, lampions et cœurs. */
    mariage(context, _baseColor, random) {
        const flagColors = ['#F7A8B8', '#FFF8E7', '#B39CD0', '#F2B134'];

        for (const [baseY, sag] of [
            [226, 22],
            [300, 16],
        ]) {
            for (let x = 0; x < WIDTH; x += 2) {
                const progress = x / WIDTH;
                const y =
                    baseY + Math.round(Math.sin(progress * Math.PI) * sag);
                context.fillStyle = 'rgba(255, 248, 231, 0.35)';
                context.fillRect(x, y, 2, 1);

                if (x % 24 === 0 && x > 0) {
                    context.fillStyle =
                        flagColors[(x / 24) % flagColors.length];
                    context.globalAlpha = 0.5;
                    for (let row = 0; row < 10; row++) {
                        const half = Math.max(0, 6 - Math.floor(row * 0.6));
                        context.fillRect(x - half, y + 1 + row, half * 2, 1);
                    }
                    context.globalAlpha = 1;
                }
            }
        }

        for (let index = 0; index < 9; index++) {
            context.fillStyle = `rgba(247, 168, 184, ${0.15 + random() * 0.2})`;
            heart(
                context,
                20 + Math.floor(random() * 300),
                340 + Math.floor(random() * 140),
                random() > 0.5 ? 3 : 2,
            );
        }

        // Piste de danse
        context.fillStyle = 'rgba(255, 248, 231, 0.05)';
        context.fillRect(0, 500, WIDTH, HEIGHT - 500);
        context.fillStyle = 'rgba(179, 156, 208, 0.12)';
        for (let x = 0; x < WIDTH; x += 30) {
            context.fillRect(x, 500, 15, HEIGHT - 500);
        }
    },
};
