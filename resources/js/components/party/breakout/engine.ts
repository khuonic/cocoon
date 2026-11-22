import { spriteCanvas } from '../sprite-canvas';
import type { SpriteName } from '../sprites';
import { renderBackground, shade } from './backgrounds';
import { levels } from './levels';
import type { SoundName } from './sounds';

/** Couleur métal des briques dures. */
const HARD_BRICK_COLOR = '#8D89A6';

/** Points d'un bonus attrapé (ou retirés par le caca), multipliés par le numéro du niveau. */
export const BONUS_POINTS_PER_LEVEL = 20;

/** Monde logique fixe, agrandi à l'écran : la physique est identique sur tous les téléphones. */
export const WORLD_WIDTH = 360;
export const WORLD_HEIGHT = 560;

type BonusKind = 'V' | 'F' | 'P' | 'S' | 'N' | 'C';

export const bonuses: Record<
    BonusKind,
    { sprite: SpriteName; title: string; text: string; isMalus: boolean }
> = {
    V: {
        sprite: 'ventouse',
        title: 'VENTOUSE',
        text: 'La balle colle à la barre (3 fois), touche pour la relancer. +20 pts × niveau.',
        isMalus: false,
    },
    F: {
        sprite: 'falafel',
        title: 'FALAFEL',
        text: 'Trois balles en jeu. +20 pts × niveau.',
        isMalus: false,
    },
    P: {
        sprite: 'pinte',
        title: 'PINTE',
        text: 'Barre XL pendant 15 secondes. +20 pts × niveau.',
        isMalus: false,
    },
    S: {
        sprite: 'scooter',
        title: 'SCOOTER',
        text: 'Balle turbo pendant 10 secondes : points doublés, mais ça va vite. +20 pts × niveau.',
        isMalus: false,
    },
    N: {
        sprite: 'crotte_nez',
        title: 'CROTTE DE NEZ',
        text: 'La barre tire des crottes de nez par deux pendant 8 secondes. Plus rare. +20 pts × niveau.',
        isMalus: false,
    },
    C: {
        sprite: 'caca_pq',
        title: 'CACA (MALUS)',
        text: 'Barre mini et mouches qui gênent la vue pendant 10 secondes. −20 pts × niveau.',
        isMalus: true,
    },
};

export type HudState = {
    score: number;
    lives: number;
    level: number;
    levelName: string;
};

export type EngineEvents = {
    onHud: (state: HudState) => void;
    onToast: (text: string) => void;
    onEnd: (result: { score: number; level: number; won: boolean }) => void;
    onSound: (name: SoundName) => void;
};

type Brick = {
    x: number;
    y: number;
    hp: number;
    maxHp: number;
    color: string;
    bonus: BonusKind | null;
};
/** Éclat de brique ou texte de points flottant, qui disparaît au bout de `life` secondes. */
type Particle = {
    x: number;
    y: number;
    vx: number;
    vy: number;
    life: number;
    color: string;
};
type Floater = {
    x: number;
    y: number;
    life: number;
    text: string;
    color: string;
};
type Ball = {
    x: number;
    y: number;
    vx: number;
    vy: number;
    stuck: boolean;
    offset: number;
};
type Item = { x: number; y: number; kind: BonusKind };
/** Crotte de nez tirée par la barre, qui monte jusqu'à toucher une brique. */
type Shot = { x: number; y: number };
type Fly = { x: number; y: number; vx: number; vy: number };

const BRICK_COLORS: Record<string, string> = {
    '1': '#E4572E',
    '2': '#F2B134',
    '3': '#2A9D8F',
    '4': '#8ECAE6',
    '5': '#F7A8B8',
    '6': '#B39CD0',
};
const BRICK_W = 40;
const BRICK_H = 16;
const GAP = 4;
const LEFT = 6;
const TOP = 56;
const PADDLE_Y = 520;
const PADDLE_H = 10;
const PADDLE_WIDTHS = { normal: 64, wide: 100, mini: 40 };
const BALL = 7;
const ITEM_SIZE = 18;
const SHOT_SIZE = 7;
const SHOT_SPEED = 420;
/** Bonus crotte de nez : durée et cadence de tir (une paire par salve). */
const SHOOTER_DURATION = 8;
const SHOOTER_FIRE_INTERVAL = 0.45;
const STEP = 1 / 120;
const MAX_BOUNCE_ANGLE = Math.PI / 3;

export class BreakoutEngine {
    private context: CanvasRenderingContext2D;
    private scale = 1;
    private pixelRatio = 1;

    private bricks: Brick[] = [];
    private balls: Ball[] = [];
    private items: Item[] = [];
    private flies: Fly[] = [];
    private particles: Particle[] = [];
    private floaters: Floater[] = [];
    private shots: Shot[] = [];
    private background: HTMLCanvasElement | null = null;

    private paddleX = WORLD_WIDTH / 2;
    private paddleWidth = PADDLE_WIDTHS.normal;
    private sizeTimer = 0;
    private turboTimer = 0;
    private cacaTimer = 0;
    private shooterTimer = 0;
    private fireCooldown = 0;
    private stickyCatches = 0;
    private keyboardDirection = 0;

    private score = 0;
    private lives = 3;
    private levelIndex = 0;
    private message: string | null = null;

    private isRunning = false;
    private isPaused = false;
    private lastTime = 0;
    private accumulator = 0;
    private frame = 0;

    constructor(
        private canvas: HTMLCanvasElement,
        private events: EngineEvents,
    ) {
        this.context = canvas.getContext('2d')!;
        canvas.addEventListener('pointerdown', this.handlePointerDown);
        canvas.addEventListener('pointermove', this.handlePointerMove);
        window.addEventListener('keydown', this.handleKeyDown);
        window.addEventListener('keyup', this.handleKeyUp);
    }

    /** Adapte le canvas à la place disponible en gardant les proportions du monde. */
    resize(availableWidth: number, availableHeight: number): void {
        this.scale = Math.max(
            0.1,
            Math.min(
                availableWidth / WORLD_WIDTH,
                availableHeight / WORLD_HEIGHT,
            ),
        );
        this.pixelRatio = window.devicePixelRatio || 1;
        this.canvas.style.width = `${Math.floor(WORLD_WIDTH * this.scale)}px`;
        this.canvas.style.height = `${Math.floor(WORLD_HEIGHT * this.scale)}px`;
        this.canvas.width = Math.round(
            WORLD_WIDTH * this.scale * this.pixelRatio,
        );
        this.canvas.height = Math.round(
            WORLD_HEIGHT * this.scale * this.pixelRatio,
        );
        this.render();
    }

    start(): void {
        this.score = 0;
        this.lives = 3;
        this.loadLevel(0);
        this.isRunning = true;
        this.lastTime = performance.now();
        this.frame = requestAnimationFrame(this.loop);
    }

    pause(): void {
        this.isPaused = true;
    }

    resume(): void {
        this.isPaused = false;
        this.lastTime = performance.now();
    }

    destroy(): void {
        this.isRunning = false;
        cancelAnimationFrame(this.frame);
        this.canvas.removeEventListener('pointerdown', this.handlePointerDown);
        this.canvas.removeEventListener('pointermove', this.handlePointerMove);
        window.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('keyup', this.handleKeyUp);
    }

    /* ---------- Boucle ---------- */

    private loop = (time: number): void => {
        if (!this.isRunning) {
            return;
        }

        const delta = Math.min((time - this.lastTime) / 1000, 0.1);
        this.lastTime = time;

        if (!this.isPaused) {
            this.accumulator += delta;
            while (this.accumulator >= STEP && this.isRunning) {
                this.update(STEP);
                this.accumulator -= STEP;
            }
        }

        this.render();
        this.frame = requestAnimationFrame(this.loop);
    };

    private update(dt: number): void {
        this.updateTimers(dt);

        if (this.keyboardDirection !== 0) {
            this.movePaddleTo(this.paddleX + this.keyboardDirection * 380 * dt);
        }

        for (const ball of this.balls) {
            this.updateBall(ball, dt);
        }

        this.balls = this.balls.filter((ball) => ball.y < WORLD_HEIGHT);
        if (this.balls.length === 0) {
            this.loseLife();
            return;
        }

        this.updateItems(dt);
        this.updateShots(dt);
        this.updateFlies(dt);
        this.updateEffects(dt);

        if (this.bricks.length === 0) {
            this.clearLevel();
        }
    }

    private updateTimers(dt: number): void {
        if (this.sizeTimer > 0) {
            this.sizeTimer -= dt;
            if (this.sizeTimer <= 0) {
                this.setPaddleWidth(PADDLE_WIDTHS.normal);
            }
        }

        this.turboTimer = Math.max(0, this.turboTimer - dt);
        this.shooterTimer = Math.max(0, this.shooterTimer - dt);

        if (this.cacaTimer > 0) {
            this.cacaTimer -= dt;
            if (this.cacaTimer <= 0) {
                this.flies = [];
            }
        }
    }

    private updateBall(ball: Ball, dt: number): void {
        if (ball.stuck) {
            ball.x = this.paddleX + ball.offset - BALL / 2;
            ball.y = PADDLE_Y - BALL;
            return;
        }

        const speedFactor = this.turboTimer > 0 ? 1.4 : 1;

        ball.x += ball.vx * speedFactor * dt;
        if (ball.x < 0) {
            ball.x = 0;
            ball.vx = Math.abs(ball.vx);
        } else if (ball.x + BALL > WORLD_WIDTH) {
            ball.x = WORLD_WIDTH - BALL;
            ball.vx = -Math.abs(ball.vx);
        }
        this.collideBricks(ball, 'x');

        ball.y += ball.vy * speedFactor * dt;
        if (ball.y < 0) {
            ball.y = 0;
            ball.vy = Math.abs(ball.vy);
        }
        this.collideBricks(ball, 'y');
        this.collidePaddle(ball);
    }

    private collideBricks(ball: Ball, axis: 'x' | 'y'): void {
        const brick = this.bricks.find(
            (candidate) =>
                ball.x < candidate.x + BRICK_W &&
                ball.x + BALL > candidate.x &&
                ball.y < candidate.y + BRICK_H &&
                ball.y + BALL > candidate.y,
        );

        if (!brick) {
            return;
        }

        if (axis === 'x') {
            ball.x = ball.vx > 0 ? brick.x - BALL : brick.x + BRICK_W;
            ball.vx = -ball.vx;
        } else {
            ball.y = ball.vy > 0 ? brick.y - BALL : brick.y + BRICK_H;
            ball.vy = -ball.vy;
        }

        this.hitBrick(brick);
    }

    private collidePaddle(ball: Ball): void {
        const left = this.paddleX - this.paddleWidth / 2;
        const isOverPaddle =
            ball.x + BALL >= left && ball.x <= left + this.paddleWidth;
        const isAtPaddleHeight =
            ball.y + BALL >= PADDLE_Y &&
            ball.y + BALL <= PADDLE_Y + PADDLE_H + 6;

        if (ball.vy <= 0 || !isOverPaddle || !isAtPaddleHeight) {
            return;
        }

        const relative = Math.max(
            -1,
            Math.min(
                1,
                (ball.x + BALL / 2 - this.paddleX) / (this.paddleWidth / 2),
            ),
        );
        const angle = relative * MAX_BOUNCE_ANGLE;
        const speed = this.baseSpeed();

        ball.vx = speed * Math.sin(angle);
        ball.vy = -speed * Math.cos(angle);
        ball.y = PADDLE_Y - BALL;
        this.events.onSound('bounce');

        if (this.stickyCatches > 0) {
            this.stickyCatches--;
            ball.stuck = true;
            ball.offset = ball.x + BALL / 2 - this.paddleX;
        }
    }

    private hitBrick(brick: Brick): void {
        brick.hp--;
        if (brick.hp > 0) {
            this.events.onSound('hardBrick');
            return;
        }

        this.events.onSound('brick');

        this.bricks = this.bricks.filter((candidate) => candidate !== brick);
        this.shatter(brick);
        this.score +=
            (10 + 5 * this.levelIndex) * (this.turboTimer > 0 ? 2 : 1);

        if (brick.bonus) {
            this.items.push({
                x: brick.x + BRICK_W / 2 - ITEM_SIZE / 2,
                y: brick.y,
                kind: brick.bonus,
            });
        }

        this.emitHud();
    }

    private updateItems(dt: number): void {
        const left = this.paddleX - this.paddleWidth / 2;

        this.items = this.items.filter((item) => {
            item.y += 110 * dt;

            const isCaught =
                item.y + ITEM_SIZE >= PADDLE_Y &&
                item.y <= PADDLE_Y + PADDLE_H &&
                item.x + ITEM_SIZE >= left &&
                item.x <= left + this.paddleWidth;

            if (isCaught) {
                this.applyBonus(item.kind);
                return false;
            }

            return item.y < WORLD_HEIGHT;
        });
    }

    /**
     * Bonus crotte de nez : la barre tire seule une paire de crottes depuis ses deux bouts
     * à intervalle régulier ; chaque crotte touche la première brique sur son chemin.
     */
    private updateShots(dt: number): void {
        if (this.shooterTimer > 0) {
            this.fireCooldown -= dt;
            if (this.fireCooldown <= 0) {
                this.fireCooldown = SHOOTER_FIRE_INTERVAL;
                const left = this.paddleX - this.paddleWidth / 2;
                for (const x of [
                    left + 3,
                    left + this.paddleWidth - 3 - SHOT_SIZE,
                ]) {
                    this.shots.push({ x, y: PADDLE_Y - SHOT_SIZE });
                }
                this.events.onSound('shot');
            }
        }

        this.shots = this.shots.filter((shot) => {
            shot.y -= SHOT_SPEED * dt;

            const brick = this.bricks.find(
                (candidate) =>
                    shot.x < candidate.x + BRICK_W &&
                    shot.x + SHOT_SIZE > candidate.x &&
                    shot.y < candidate.y + BRICK_H &&
                    shot.y + SHOT_SIZE > candidate.y,
            );

            if (brick) {
                this.hitBrick(brick);

                return false;
            }

            return shot.y + SHOT_SIZE > 0;
        });
    }

    /** Éclats projetés à la casse d'une brique. */
    private shatter(brick: Brick): void {
        for (let index = 0; index < 6; index++) {
            this.particles.push({
                x: brick.x + 4 + Math.random() * (BRICK_W - 8),
                y: brick.y + 4 + Math.random() * (BRICK_H - 8),
                vx: (Math.random() - 0.5) * 160,
                vy: -40 - Math.random() * 80,
                life: 0.45 + Math.random() * 0.25,
                color: brick.color,
            });
        }
    }

    private updateEffects(dt: number): void {
        this.particles = this.particles.filter((particle) => {
            particle.vy += 600 * dt;
            particle.x += particle.vx * dt;
            particle.y += particle.vy * dt;
            particle.life -= dt;

            return particle.life > 0;
        });

        this.floaters = this.floaters.filter((floater) => {
            floater.y -= 34 * dt;
            floater.life -= dt;

            return floater.life > 0;
        });
    }

    private updateFlies(dt: number): void {
        for (const fly of this.flies) {
            fly.vx += (Math.random() - 0.5) * 900 * dt;
            fly.vy += (Math.random() - 0.5) * 900 * dt;
            fly.vx = Math.max(-90, Math.min(90, fly.vx));
            fly.vy = Math.max(-90, Math.min(90, fly.vy));
            fly.x = Math.max(
                10,
                Math.min(WORLD_WIDTH - 10, fly.x + fly.vx * dt),
            );
            fly.y = Math.max(300, Math.min(PADDLE_Y - 10, fly.y + fly.vy * dt));
        }
    }

    private applyBonus(kind: BonusKind): void {
        switch (kind) {
            case 'V':
                this.stickyCatches = 3;
                break;
            case 'F':
                this.splitBalls();
                break;
            case 'P':
                this.setPaddleWidth(PADDLE_WIDTHS.wide);
                this.sizeTimer = 15;
                break;
            case 'S':
                this.turboTimer = 10;
                break;
            case 'N':
                this.shooterTimer = SHOOTER_DURATION;
                this.fireCooldown = 0;
                break;
            case 'C':
                this.setPaddleWidth(PADDLE_WIDTHS.mini);
                this.sizeTimer = 10;
                this.cacaTimer = 10;
                this.flies = Array.from({ length: 9 }, () => ({
                    x: Math.random() * WORLD_WIDTH,
                    y: 320 + Math.random() * 180,
                    vx: 0,
                    vy: 0,
                }));
                break;
        }

        const isMalus = bonuses[kind].isMalus;
        const points = BONUS_POINTS_PER_LEVEL * (this.levelIndex + 1);
        this.score = Math.max(0, this.score + (isMalus ? -points : points));
        const label = `${isMalus ? '−' : '+'}${points}`;

        this.floaters.push({
            x: this.paddleX,
            y: PADDLE_Y - 14,
            life: 1,
            text: label,
            color: isMalus ? '#E4572E' : '#F2B134',
        });
        this.events.onToast(`${bonuses[kind].title} ! ${label}`);
        this.events.onSound(isMalus ? 'malus' : 'bonus');
        this.emitHud();
    }

    private splitBalls(): void {
        const source = this.balls.find((ball) => !ball.stuck) ?? this.balls[0];
        if (!source) {
            return;
        }

        if (source.stuck) {
            this.launch();
        }

        const speed = this.baseSpeed();
        const baseAngle = Math.atan2(source.vx, -source.vy);

        for (const offset of [-0.5, 0.5]) {
            const angle = Math.max(
                -MAX_BOUNCE_ANGLE,
                Math.min(MAX_BOUNCE_ANGLE, baseAngle + offset),
            );
            this.balls.push({
                x: source.x,
                y: source.y,
                vx: speed * Math.sin(angle),
                vy: -speed * Math.cos(angle),
                stuck: false,
                offset: 0,
            });
        }
    }

    private setPaddleWidth(width: number): void {
        this.paddleWidth = width;
        this.movePaddleTo(this.paddleX);
    }

    private movePaddleTo(x: number): void {
        const half = this.paddleWidth / 2;
        this.paddleX = Math.max(half, Math.min(WORLD_WIDTH - half, x));
    }

    private baseSpeed(): number {
        return 250 + this.levelIndex * 18;
    }

    private launch(): void {
        const speed = this.baseSpeed();
        let launched = false;

        for (const ball of this.balls) {
            if (!ball.stuck) {
                continue;
            }
            const angle =
                (ball.offset / (this.paddleWidth / 2)) * 0.6 +
                (Math.random() - 0.5) * 0.2;
            ball.stuck = false;
            ball.vx = speed * Math.sin(angle);
            ball.vy = -speed * Math.cos(angle);
            launched = true;
        }

        if (launched) {
            this.message = null;
            this.events.onSound('launch');
        }
    }

    private loseLife(): void {
        this.lives--;
        this.resetEffects();

        if (this.lives <= 0) {
            this.end(false);
            return;
        }

        this.spawnStuckBall();
        this.message = 'TOUCHE POUR LANCER';
        this.events.onToast('UNE VIE EN MOINS');
        this.events.onSound('lifeLost');
        this.emitHud();
    }

    private clearLevel(): void {
        this.score += 100 * (this.levelIndex + 1);

        if (this.levelIndex >= levels.length - 1) {
            this.end(true);
            return;
        }

        this.loadLevel(this.levelIndex + 1);
        this.events.onSound('levelClear');
        this.events.onToast(
            `NIVEAU ${this.levelIndex + 1} : ${levels[this.levelIndex].name}`,
        );
    }

    private loadLevel(index: number): void {
        this.levelIndex = index;
        this.bricks = [];

        levels[index].rows.forEach((row, rowIndex) => {
            [...row].forEach((char, column) => {
                if (char === '.') {
                    return;
                }

                const hp = char === '#' ? 2 : 1;
                this.bricks.push({
                    x: LEFT + column * (BRICK_W + GAP),
                    y: TOP + rowIndex * (BRICK_H + GAP),
                    hp,
                    maxHp: hp,
                    color: BRICK_COLORS[char] ?? HARD_BRICK_COLOR,
                    bonus: null,
                });
            });
        });

        this.background = renderBackground(
            levels[index].decor,
            levels[index].background,
        );

        this.hideBonuses(levels[index].bonusCount);
        this.resetEffects();
        this.spawnStuckBall();
        this.message = 'TOUCHE POUR LANCER';
        this.emitHud();
    }

    /**
     * Cache des bonus au hasard dans les briques du niveau, sans indice visuel.
     * Le malus (caca) et la crotte de nez sortent deux fois moins souvent que les autres bonus.
     */
    private hideBonuses(count: number): void {
        const pool: BonusKind[] = [
            'V',
            'V',
            'F',
            'F',
            'P',
            'P',
            'S',
            'S',
            'N',
            'C',
        ];
        const candidates = [...this.bricks];

        for (let index = 0; index < count && candidates.length > 0; index++) {
            const [brick] = candidates.splice(
                Math.floor(Math.random() * candidates.length),
                1,
            );
            brick.bonus = pool[Math.floor(Math.random() * pool.length)];
        }
    }

    private resetEffects(): void {
        this.items = [];
        this.flies = [];
        this.particles = [];
        this.shots = [];
        this.sizeTimer = 0;
        this.turboTimer = 0;
        this.cacaTimer = 0;
        this.shooterTimer = 0;
        this.fireCooldown = 0;
        this.stickyCatches = 0;
        this.setPaddleWidth(PADDLE_WIDTHS.normal);
    }

    private spawnStuckBall(): void {
        this.balls = [
            {
                x: this.paddleX - BALL / 2,
                y: PADDLE_Y - BALL,
                vx: 0,
                vy: 0,
                stuck: true,
                offset: 0,
            },
        ];
    }

    private end(won: boolean): void {
        this.isRunning = false;
        this.message = won ? 'TOUT EST CASSÉ !' : 'PLUS DE PINTES…';
        this.events.onSound(won ? 'win' : 'gameOver');
        this.render();
        this.events.onEnd({
            score: this.score,
            level: this.levelIndex + 1,
            won,
        });
    }

    private emitHud(): void {
        this.events.onHud({
            score: this.score,
            lives: this.lives,
            level: this.levelIndex + 1,
            levelName: levels[this.levelIndex].name,
        });
    }

    /* ---------- Contrôles ---------- */

    private worldX(clientX: number): number {
        const rect = this.canvas.getBoundingClientRect();

        return ((clientX - rect.left) / rect.width) * WORLD_WIDTH;
    }

    private handlePointerDown = (event: PointerEvent): void => {
        if (this.isPaused || !this.isRunning) {
            return;
        }
        this.movePaddleTo(this.worldX(event.clientX));
        this.launch();
    };

    private handlePointerMove = (event: PointerEvent): void => {
        if (this.isPaused || !this.isRunning) {
            return;
        }
        this.movePaddleTo(this.worldX(event.clientX));
    };

    private handleKeyDown = (event: KeyboardEvent): void => {
        if (event.key === 'ArrowLeft') {
            this.keyboardDirection = -1;
        } else if (event.key === 'ArrowRight') {
            this.keyboardDirection = 1;
        } else if (event.key === ' ' && this.isRunning && !this.isPaused) {
            event.preventDefault();
            this.launch();
        }
    };

    private handleKeyUp = (event: KeyboardEvent): void => {
        if (
            (event.key === 'ArrowLeft' && this.keyboardDirection === -1) ||
            (event.key === 'ArrowRight' && this.keyboardDirection === 1)
        ) {
            this.keyboardDirection = 0;
        }
    };

    /* ---------- Dessin ---------- */

    private render(): void {
        const context = this.context;
        const ratio = this.scale * this.pixelRatio;

        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        context.imageSmoothingEnabled = false;

        if (this.background) {
            context.drawImage(this.background, 0, 0, WORLD_WIDTH, WORLD_HEIGHT);
        } else {
            context.fillStyle =
                levels[this.levelIndex]?.background ?? '#22223B';
            context.fillRect(0, 0, WORLD_WIDTH, WORLD_HEIGHT);
        }

        for (const brick of this.bricks) {
            this.drawBrick(brick);
        }

        for (const particle of this.particles) {
            context.globalAlpha = Math.min(1, particle.life / 0.3);
            context.fillStyle = particle.color;
            context.fillRect(
                Math.round(particle.x),
                Math.round(particle.y),
                3,
                3,
            );
        }
        context.globalAlpha = 1;

        for (const item of this.items) {
            context.fillStyle = '#F2B134';
            context.fillRect(
                item.x - 2,
                item.y - 2,
                ITEM_SIZE + 4,
                ITEM_SIZE + 4,
            );
            context.fillStyle = bonuses[item.kind].isMalus
                ? '#E4572E'
                : '#FFF8E7';
            context.fillRect(item.x, item.y, ITEM_SIZE, ITEM_SIZE);
            this.drawIcon(
                bonuses[item.kind].sprite,
                item.x + ITEM_SIZE / 2,
                item.y + ITEM_SIZE / 2,
                14,
                14,
            );
        }

        for (const shot of this.shots) {
            this.drawShot(shot);
        }

        this.drawPaddle();

        for (const ball of this.balls) {
            if (this.turboTimer > 0 && !ball.stuck) {
                context.fillStyle = 'rgba(228, 87, 46, 0.6)';
                context.fillRect(
                    ball.x - ball.vx * 0.025,
                    ball.y - ball.vy * 0.025,
                    BALL,
                    BALL,
                );
            }
            this.drawBall(ball);
        }

        context.fillStyle = '#1B1B2F';
        for (const fly of this.flies) {
            context.fillRect(fly.x, fly.y, 4, 4);
            context.fillRect(fly.x - 2, fly.y - 2, 2, 2);
            context.fillRect(fly.x + 4, fly.y - 2, 2, 2);
        }

        this.drawFloaters();

        if (this.message) {
            this.drawMessage(this.message);
        }
    }

    private drawBall(ball: Ball): void {
        const context = this.context;
        const x = Math.round(ball.x);
        const y = Math.round(ball.y);

        // Ombre portée, puis balle aux coins arrondis avec reflet.
        context.fillStyle = 'rgba(0, 0, 0, 0.35)';
        context.fillRect(x + 1, y + BALL, BALL - 2, 1);
        context.fillStyle = '#FFF8E7';
        context.fillRect(x + 1, y, BALL - 2, BALL);
        context.fillRect(x, y + 1, BALL, BALL - 2);
        context.fillStyle = '#D8CFB4';
        context.fillRect(x + BALL - 3, y + BALL - 3, 2, 2);
        context.fillStyle = '#FFFFFF';
        context.fillRect(x + 1, y + 1, 2, 2);
    }

    private drawFloaters(): void {
        const context = this.context;
        context.font = "8px 'Press Start 2P', monospace";
        context.textAlign = 'center';
        context.textBaseline = 'middle';

        for (const floater of this.floaters) {
            context.globalAlpha = Math.min(1, floater.life / 0.4);
            context.fillStyle = '#1B1B2F';
            context.fillText(floater.text, floater.x + 1, floater.y + 1);
            context.fillStyle = floater.color;
            context.fillText(floater.text, floater.x, floater.y);
        }
        context.globalAlpha = 1;
    }

    private drawBrick(brick: Brick): void {
        const context = this.context;
        const { x, y } = brick;
        const isHard = brick.maxHp >= 2;
        const isCracked = isHard && brick.hp < brick.maxHp;
        const base = isCracked ? shade(brick.color, -0.15) : brick.color;

        // Les bonus sont cachés : une brique bonus ressemble à toutes les autres.
        // Contour sombre, biseau éclairé en haut à gauche, ombre en bas à droite, reflet.
        context.fillStyle = shade(brick.color, -0.6);
        context.fillRect(x, y, BRICK_W, BRICK_H);
        context.fillStyle = base;
        context.fillRect(x + 1, y + 1, BRICK_W - 2, BRICK_H - 2);
        context.fillStyle = shade(base, 0.35);
        context.fillRect(x + 1, y + 1, BRICK_W - 2, 2);
        context.fillRect(x + 1, y + 1, 2, BRICK_H - 2);
        context.fillStyle = shade(base, -0.3);
        context.fillRect(x + 1, y + BRICK_H - 3, BRICK_W - 2, 2);
        context.fillRect(x + BRICK_W - 3, y + 1, 2, BRICK_H - 2);
        context.fillStyle = 'rgba(255, 255, 255, 0.6)';
        context.fillRect(x + 4, y + 4, 4, 2);

        if (!isHard) {
            return;
        }

        // Brique en métal : rivets, puis fissure après le premier coup.
        context.fillStyle = shade(brick.color, -0.45);
        for (const [rivetX, rivetY] of [
            [x + 5, y + 7],
            [x + BRICK_W - 7, y + 7],
        ]) {
            context.fillRect(rivetX, rivetY, 2, 2);
        }

        if (isCracked) {
            context.fillStyle = '#1B1B2F';
            for (const [crackX, crackY, width] of [
                [17, 2, 2],
                [18, 4, 2],
                [20, 6, 2],
                [19, 8, 2],
                [21, 10, 3],
                [23, 12, 2],
                [14, 6, 4],
            ]) {
                context.fillRect(x + crackX, y + crackY, width, 2);
            }
        }
    }

    private drawPaddle(): void {
        const context = this.context;
        const width = this.paddleWidth;
        const left = Math.round(this.paddleX - width / 2);
        const y = PADDLE_Y;

        const color =
            this.cacaTimer > 0
                ? '#9C6B30'
                : width > PADDLE_WIDTHS.normal
                  ? '#F7C948'
                  : '#F2B134';

        // Contour aux coins arrondis en escalier de pixels.
        context.fillStyle = '#1B1B2F';
        context.fillRect(left + 1, y - 2, width - 2, PADDLE_H + 4);
        context.fillRect(left - 2, y + 1, width + 4, PADDLE_H - 2);
        context.fillRect(left - 1, y - 1, width + 2, PADDLE_H + 2);

        context.fillStyle = color;
        context.fillRect(left + 1, y, width - 2, PADDLE_H);
        context.fillRect(left, y + 1, width, PADDLE_H - 2);

        // Embouts plus sombres, reflet en haut, ombre en bas.
        context.fillStyle = shade(color, -0.3);
        context.fillRect(left, y + 1, 5, PADDLE_H - 2);
        context.fillRect(left + width - 5, y + 1, 5, PADDLE_H - 2);
        context.fillStyle = shade(color, 0.45);
        context.fillRect(left + 7, y + 1, width - 14, 2);
        context.fillStyle = shade(color, -0.2);
        context.fillRect(left + 5, y + PADDLE_H - 3, width - 10, 2);

        // Mousse de bière sur la barre XL.
        if (width > PADDLE_WIDTHS.normal && this.cacaTimer <= 0) {
            context.fillStyle = '#FFF8E7';
            for (let foamX = left + 6; foamX < left + width - 8; foamX += 6) {
                context.fillRect(foamX, y - 1, 4, 2);
            }
        }

        if (this.stickyCatches > 0) {
            context.fillStyle = '#7A2E22';
            context.fillRect(this.paddleX - 7, y - 4, 14, 3);
            context.fillStyle = '#C8553D';
            context.fillRect(this.paddleX - 6, y - 4, 12, 2);
            context.fillRect(this.paddleX - 2, y - 7, 4, 3);
        }

        // Bonus crotte de nez : une petite narine verte à chaque bout, d'où partent les tirs.
        if (this.shooterTimer > 0) {
            for (const noseX of [left + 1, left + width - 11]) {
                context.fillStyle = '#1B1B2F';
                context.fillRect(noseX, y - 6, 10, 6);
                context.fillStyle = '#8DB33A';
                context.fillRect(noseX + 2, y - 5, 6, 4);
                context.fillStyle = '#E4F2B8';
                context.fillRect(noseX + 3, y - 4, 2, 1);
            }
        }
    }

    /** Crotte de nez en vol : boulette verte avec un reflet et une petite traînée. */
    private drawShot(shot: Shot): void {
        const context = this.context;
        const x = Math.round(shot.x);
        const y = Math.round(shot.y);

        // Traînée, contour sombre arrondi, boulette verte, ombre et reflet.
        context.fillStyle = 'rgba(141, 179, 58, 0.4)';
        context.fillRect(x + 2, y + SHOT_SIZE, SHOT_SIZE - 4, 6);
        context.fillStyle = '#1B1B2F';
        context.fillRect(x + 1, y - 1, SHOT_SIZE - 2, SHOT_SIZE + 2);
        context.fillRect(x - 1, y + 1, SHOT_SIZE + 2, SHOT_SIZE - 2);
        context.fillRect(x, y, SHOT_SIZE, SHOT_SIZE);
        context.fillStyle = '#8DB33A';
        context.fillRect(x + 1, y, SHOT_SIZE - 2, SHOT_SIZE);
        context.fillRect(x, y + 1, SHOT_SIZE, SHOT_SIZE - 2);
        context.fillStyle = '#5E7F1F';
        context.fillRect(x + 2, y + SHOT_SIZE - 2, SHOT_SIZE - 3, 2);
        context.fillStyle = '#E4F2B8';
        context.fillRect(x + 2, y + 1, 2, 2);
    }

    private drawIcon(
        name: SpriteName,
        centerX: number,
        centerY: number,
        maxHeight: number,
        maxWidth: number,
    ): void {
        const sprite = spriteCanvas(name);
        const size = Math.min(
            maxHeight / sprite.height,
            maxWidth / sprite.width,
        );
        const width = sprite.width * size;
        const height = sprite.height * size;

        this.context.drawImage(
            sprite,
            Math.round(centerX - width / 2),
            Math.round(centerY - height / 2),
            width,
            height,
        );
    }

    private drawMessage(text: string): void {
        const context = this.context;
        context.font = "10px 'Press Start 2P', monospace";
        context.textAlign = 'center';
        context.textBaseline = 'middle';

        const width = context.measureText(text).width + 24;
        const y = 420;
        context.fillStyle = 'rgba(27, 27, 47, 0.85)';
        context.fillRect(WORLD_WIDTH / 2 - width / 2, y - 16, width, 32);
        context.fillStyle = '#F2B134';
        context.fillText(text, WORLD_WIDTH / 2, y + 1);
    }
}
