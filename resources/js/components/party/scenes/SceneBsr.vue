<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();

/** Gouttes de sang : direction (vers la gauche / le haut) et décalage pour un jet continu. */
const bloodDrops = [
    { dx: '-10px', dy: '-14px', delay: '0ms' },
    { dx: '-16px', dy: '-6px', delay: '270ms' },
    { dx: '-6px', dy: '-20px', delay: '530ms' },
];
</script>

<template>
    <SceneStage background="#8ECAE6">
        <!-- Piste -->
        <div
            class="absolute inset-x-0 bottom-0"
            style="height: 56px; background: #555b6e"
        />
        <div
            class="absolute"
            style="
                left: 10px;
                bottom: 22px;
                width: 24px;
                height: 4px;
                background: #fff8e7;
            "
        />
        <div
            class="absolute"
            style="
                left: 78px;
                bottom: 22px;
                width: 24px;
                height: 4px;
                background: #fff8e7;
            "
        />
        <div
            class="absolute"
            style="
                left: 146px;
                bottom: 22px;
                width: 24px;
                height: 4px;
                background: #fff8e7;
            "
        />
        <!-- Plot -->
        <div
            class="absolute"
            style="
                left: 156px;
                bottom: 56px;
                width: 6px;
                height: 14px;
                background: #f4a261;
            "
        />
        <div
            class="absolute"
            style="
                left: 152px;
                bottom: 56px;
                width: 14px;
                height: 4px;
                background: #e4572e;
            "
        />
        <!-- Scooter + Antoine -->
        <div class="absolute" style="left: 14px; bottom: 36px">
            <PixelSprite name="scooter" :px="4" />
        </div>
        <template v-if="action">
            <!-- Antoine remonte sur le scooter, lunettes thug life -->
            <div class="px-walk-in absolute" style="left: 4px; bottom: 58px">
                <PixelSprite name="ado_scooter" :px="4" />
            </div>
            <!-- Le genou gicle -->
            <div
                v-for="drop in bloodDrops"
                :key="drop.delay"
                class="px-squirt absolute"
                :style="{
                    left: '18px',
                    bottom: '60px',
                    width: '4px',
                    height: '4px',
                    background: '#b3122e',
                    '--dx': drop.dx,
                    '--dy': drop.dy,
                    animationDelay: drop.delay,
                }"
            />
        </template>
        <div v-else class="absolute" style="left: 88px; bottom: 36px">
            <PixelSprite name="ado" :px="4" />
        </div>
        <p
            v-if="action"
            class="font-pixel px-pop absolute m-0"
            style="
                left: 8px;
                bottom: 3px;
                font-size: 8px;
                background: #1b1b2f;
                color: #f2b134;
                padding: 5px;
            "
        >
            MÊME PAS MAL
        </p>
    </SceneStage>
</template>
