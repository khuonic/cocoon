<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();
</script>

<template>
    <SceneStage background="#1B1B2F">
        <!-- Spots -->
        <div
            class="px-blink absolute"
            style="
                left: 20px;
                top: 14px;
                width: 12px;
                height: 12px;
                background: #f7a8b8;
            "
        />
        <div
            class="absolute"
            style="
                left: 80px;
                top: 26px;
                width: 12px;
                height: 12px;
                background: #2a9d8f;
            "
        />
        <div
            class="px-blink absolute"
            style="
                left: 140px;
                top: 10px;
                width: 12px;
                height: 12px;
                background: #f2b134;
                animation-delay: 500ms;
            "
        />
        <p
            class="font-pixel absolute inset-x-0 m-0 text-center"
            style="top: 8px; font-size: 8px; color: #f7a8b8"
        >
            CAP CAVAL
        </p>
        <!-- Sol de sable -->
        <div
            class="absolute inset-x-0 bottom-0"
            style="
                height: 40px;
                background: #e3c88e;
                border-top: 6px solid #c9a96e;
            "
        />
        <!-- Bar -->
        <div
            class="absolute"
            style="
                right: 0;
                bottom: 40px;
                width: 44px;
                height: 46px;
                background: #5b3a29;
            "
        />
        <div
            class="absolute"
            style="
                right: 30px;
                bottom: 86px;
                width: 6px;
                height: 16px;
                background: #2a9d8f;
            "
        />
        <div
            class="absolute"
            style="
                right: 16px;
                bottom: 86px;
                width: 6px;
                height: 12px;
                background: #f2b134;
            "
        />
        <!-- Antoine -->
        <template v-if="action">
            <div
                class="px-blink absolute"
                style="
                    left: 6px;
                    bottom: 46px;
                    width: 8px;
                    height: 8px;
                    background: #e3c88e;
                "
            />
            <div
                class="px-blink absolute"
                style="
                    left: 14px;
                    bottom: 56px;
                    width: 6px;
                    height: 6px;
                    background: #e3c88e;
                    animation-delay: 300ms;
                "
            />
            <div class="px-walk-in absolute" style="left: 22px; bottom: 40px">
                <PixelSprite name="rambo" :px="4" />
            </div>
        </template>
        <div v-else class="absolute" style="left: 24px; bottom: 40px">
            <PixelSprite name="adulte" :px="4" />
        </div>
    </SceneStage>
</template>
