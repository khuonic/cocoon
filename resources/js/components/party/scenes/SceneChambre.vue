<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();
</script>

<template>
    <SceneStage background="#B8A9C9">
        <!-- Poster moto cross -->
        <div
            class="absolute"
            style="
                left: 104px;
                top: 14px;
                width: 44px;
                height: 54px;
                background: #f4a261;
            "
        >
            <div
                class="absolute"
                style="
                    left: 12px;
                    top: 26px;
                    width: 22px;
                    height: 8px;
                    background: #e4572e;
                "
            />
            <div
                class="absolute"
                style="
                    left: 6px;
                    top: 34px;
                    width: 10px;
                    height: 10px;
                    background: #1b1b2f;
                "
            />
            <div
                class="absolute"
                style="
                    left: 28px;
                    top: 34px;
                    width: 10px;
                    height: 10px;
                    background: #1b1b2f;
                "
            />
        </div>
        <!-- Poster anime -->
        <div
            class="absolute"
            style="
                left: 40px;
                top: 12px;
                width: 40px;
                height: 52px;
                background: #f7a8b8;
            "
        >
            <div
                class="absolute"
                style="
                    left: 0;
                    top: 0;
                    width: 40px;
                    height: 12px;
                    background: #6d597a;
                "
            />
            <div
                class="absolute"
                style="
                    left: 7px;
                    top: 20px;
                    width: 10px;
                    height: 12px;
                    background: #fff8e7;
                "
            />
            <div
                class="absolute"
                style="
                    left: 23px;
                    top: 20px;
                    width: 10px;
                    height: 12px;
                    background: #fff8e7;
                "
            />
            <div
                class="absolute"
                style="
                    left: 11px;
                    top: 24px;
                    width: 4px;
                    height: 6px;
                    background: #1b1b2f;
                "
            />
            <div
                class="absolute"
                style="
                    left: 27px;
                    top: 24px;
                    width: 4px;
                    height: 6px;
                    background: #1b1b2f;
                "
            />
        </div>
        <!-- Sol -->
        <div
            class="absolute inset-x-0 bottom-0"
            style="
                height: 38px;
                background: #8b5a3c;
                border-top: 6px solid #6b4433;
            "
        />
        <!-- Lit -->
        <div
            class="absolute"
            style="
                left: 0;
                bottom: 38px;
                width: 10px;
                height: 64px;
                background: #5b3a29;
            "
        />
        <template v-if="action">
            <div
                class="px-pop absolute"
                style="
                    left: 3px;
                    bottom: 74px;
                    width: 4px;
                    height: 4px;
                    background: #7fb800;
                "
            />
            <div
                class="px-pop absolute"
                style="
                    left: 4px;
                    bottom: 86px;
                    width: 4px;
                    height: 4px;
                    background: #7fb800;
                    animation-delay: 120ms;
                "
            />
            <div
                class="px-pop absolute"
                style="
                    left: 2px;
                    bottom: 92px;
                    width: 4px;
                    height: 4px;
                    background: #7fb800;
                    animation-delay: 240ms;
                "
            />
        </template>
        <div
            class="absolute"
            style="
                left: 10px;
                bottom: 38px;
                width: 64px;
                height: 18px;
                background: #fff8e7;
            "
        />
        <div
            class="absolute"
            style="
                left: 26px;
                bottom: 38px;
                width: 48px;
                height: 14px;
                background: #2a9d8f;
            "
        />
        <!-- Antoine -->
        <div
            v-if="action"
            class="px-walk-in absolute"
            style="left: 10px; bottom: 38px"
        >
            <PixelSprite name="enfant_crotte" :px="4" />
        </div>
        <div v-else class="absolute" style="right: 20px; bottom: 38px">
            <PixelSprite name="enfant" :px="4" />
        </div>
    </SceneStage>
</template>
