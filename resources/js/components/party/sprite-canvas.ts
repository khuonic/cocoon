import { basePalette, sprites, type SpriteName } from './sprites';

const cache = new Map<SpriteName, HTMLCanvasElement>();

/**
 * Rend un sprite pixel art dans un canvas hors écran (1 pixel = 1 case), mis en cache.
 * À dessiner avec imageSmoothingEnabled = false pour garder les pixels nets.
 */
export function spriteCanvas(name: SpriteName): HTMLCanvasElement {
    const cached = cache.get(name);
    if (cached) {
        return cached;
    }

    const sprite = sprites[name];
    const palette: Record<string, string> = {
        ...basePalette,
        ...('palette' in sprite ? sprite.palette : {}),
    };
    const canvas = document.createElement('canvas');
    canvas.width = sprite.rows[0].length;
    canvas.height = sprite.rows.length;
    const context = canvas.getContext('2d')!;

    sprite.rows.forEach((row, y) => {
        [...row].forEach((char, x) => {
            if (char !== '.' && palette[char]) {
                context.fillStyle = palette[char];
                context.fillRect(x, y, 1, 1);
            }
        });
    });

    cache.set(name, canvas);

    return canvas;
}
