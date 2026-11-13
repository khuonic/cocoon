<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();
</script>

<template>
    <SceneStage background="#22223B">
        <template v-if="!action">
            <!-- Guirlande -->
            <div
                class="absolute inset-x-0"
                style="top: 18px; height: 4px; background: #4a4e69"
            />
            <div
                class="px-blink absolute"
                style="
                    left: 14px;
                    top: 22px;
                    width: 6px;
                    height: 6px;
                    background: #f2b134;
                "
            />
            <div
                class="absolute"
                style="
                    left: 54px;
                    top: 22px;
                    width: 6px;
                    height: 6px;
                    background: #f2b134;
                "
            />
            <div
                class="px-blink absolute"
                style="
                    left: 94px;
                    top: 22px;
                    width: 6px;
                    height: 6px;
                    background: #f2b134;
                    animation-delay: 400ms;
                "
            />
            <div
                class="absolute"
                style="
                    left: 134px;
                    top: 22px;
                    width: 6px;
                    height: 6px;
                    background: #f2b134;
                "
            />
            <!-- Pelouse -->
            <div
                class="absolute inset-x-0 bottom-0"
                style="height: 34px; background: #3e8e41"
            />
            <div class="px-bounce absolute" style="left: 56px; bottom: 34px">
                <PixelSprite name="adulte" :px="4" />
            </div>
            <p
                class="font-pixel absolute m-0"
                style="
                    left: 116px;
                    top: 40px;
                    font-size: 9px;
                    background: #fff8e7;
                    color: #1b1b2f;
                    padding: 5px;
                "
            >
                HIC
            </p>
        </template>
        <template v-else>
            <!-- Parking -->
            <div
                class="absolute inset-x-0 bottom-0"
                style="height: 40px; background: #555b6e"
            />
            <!-- La voiture garée encaisse le choc -->
            <div
                class="px-knock absolute"
                style="left: 124px; bottom: 32px; transform-origin: bottom left"
            >
                <PixelSprite name="voiture_garee" :px="4" />
            </div>
            <!-- La voiture des parents arrive lancée -->
            <div class="px-drive-in absolute" style="left: 44px; bottom: 32px">
                <PixelSprite name="voiture" :px="4" />
            </div>
            <div
                class="px-pop absolute"
                style="left: 110px; bottom: 54px; animation-delay: 700ms"
            >
                <PixelSprite name="boum" :px="4" />
            </div>
            <p
                class="font-pixel px-pop absolute m-0"
                style="
                    left: 8px;
                    bottom: 6px;
                    font-size: 8px;
                    background: #1b1b2f;
                    color: #f2b134;
                    padding: 5px;
                    animation-delay: 1200ms;
                "
            >
                « C'ÉTAIT PAS MOI »
            </p>
        </template>
    </SceneStage>
</template>
