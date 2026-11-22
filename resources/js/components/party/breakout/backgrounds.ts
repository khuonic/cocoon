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

    /** Ciel étoilé, lune, falaise et vagues. */
    capCaval(context, baseColor, random) {
        stars(context, random, 70, 400);

        context.fillStyle = 'rgba(255, 248, 231, 0.7)';
        disc(context, 286, 270, 18);
        context.fillStyle = 'rgba(201, 195, 176, 0.5)';
        context.fillRect(278, 262, 5, 5);
        context.fillRect(290, 276, 4, 4);

        // Falaise
        context.fillStyle = shade(baseColor, -0.55);
        const cliff = [380, 372, 360, 352, 348, 350, 358, 372, 390, 404];
        cliff.forEach((top, index) => {
            context.fillRect(index * 10, top, 10, HEIGHT - top);
        });

        // Mer
        context.fillStyle = '#1D3557';
        context.fillRect(100, 410, WIDTH - 100, HEIGHT - 410);
        context.fillStyle = 'rgba(142, 202, 230, 0.3)';
        for (let y = 420; y < HEIGHT; y += 16) {
            const offset = ((y / 16) % 2) * 12;
            for (let waveX = 104 + offset; waveX < WIDTH; waveX += 26) {
                context.fillRect(waveX, y, 10, 2);
                context.fillRect(waveX + 2, y - 2, 6, 2);
            }
        }
        context.fillStyle = 'rgba(255, 248, 231, 0.25)';
        for (let y = 414; y < 500; y += 6) {
            context.fillRect(278 + ((y * 7) % 10), y, 14, 2);
        }
    },

    /** Coucher de soleil, mer et palmiers. */
    bresil(context, baseColor) {
        context.fillStyle = 'rgba(242, 177, 52, 0.25)';
        context.fillRect(0, 330, WIDTH, 90);
        context.fillStyle = 'rgba(228, 87, 46, 0.25)';
        context.fillRect(0, 380, WIDTH, 40);

        context.fillStyle = '#F2B134';
        disc(context, 180, 420, 40);
        context.fillStyle = 'rgba(228, 87, 46, 0.6)';
        for (let y = 396; y < 420; y += 7) {
            context.fillRect(138, y, 84, 2);
        }

        context.fillStyle = shade(baseColor, -0.35);
        context.fillRect(0, 420, WIDTH, HEIGHT - 420);
        context.fillStyle = 'rgba(242, 177, 52, 0.45)';
        for (let y = 426; y < 520; y += 8) {
            const width = 60 - (y - 426) / 2;
            context.fillRect(180 - width / 2 + ((y * 3) % 8), y, width, 2);
        }

        // Plage
        context.fillStyle = '#E3D7B6';
        context.fillRect(0, 526, WIDTH, HEIGHT - 526);
        context.fillStyle = 'rgba(0, 0, 0, 0.1)';
        context.fillRect(0, 526, WIDTH, 2);

        // Palmiers
        for (const [trunkX, lean] of [
            [30, 1],
            [326, -1],
        ] as const) {
            context.fillStyle = '#3D2C2E';
            for (let segment = 0; segment < 12; segment++) {
                context.fillRect(
                    trunkX + lean * Math.floor(segment * segment * 0.12),
                    526 - segment * 12,
                    8,
                    11,
                );
            }
            const topX = trunkX + lean * 17 + 4;
            context.fillStyle = '#1F4A45';
            for (const [dx, dy] of [
                [-34, 6],
                [-22, -4],
                [-8, -8],
                [8, -8],
                [22, -4],
                [34, 6],
            ]) {
                const steps = 6;
                for (let step = 0; step < steps; step++) {
                    context.fillRect(
                        topX + (dx * step) / steps - 3,
                        386 + (dy * step) / steps + (step * step) / 3,
                        7,
                        5,
                    );
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
