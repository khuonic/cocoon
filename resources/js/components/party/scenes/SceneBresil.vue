<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();
</script>

<template>
    <SceneStage background="#2A9D8F">
        <!-- Drapeau -->
        <div
            class="absolute"
            style="
                left: 14px;
                top: 14px;
                width: 44px;
                height: 30px;
                background: #3e8e41;
            "
        >
            <div
                class="absolute"
                style="
                    left: 12px;
                    top: 7px;
                    width: 20px;
                    height: 16px;
                    background: #f2b134;
                "
            />
            <div
                class="absolute"
                style="
                    left: 18px;
                    top: 11px;
                    width: 8px;
                    height: 8px;
                    background: #3a5a8c;
                "
            />
        </div>
        <!-- Sol -->
        <div
            class="absolute inset-x-0 bottom-0"
            style="height: 30px; background: #e3d7b6"
        />
        <div class="absolute" style="right: 14px; bottom: 30px">
            <PixelSprite name="toilettes" :px="4" />
        </div>
        <!-- Antoine -->
        <div class="absolute" style="left: 32px; bottom: 30px">
            <PixelSprite name="adulte" :px="4" />
        </div>
        <template v-if="action">
            <div class="px-walk-in absolute" style="left: 18px; bottom: 46px">
                <PixelSprite name="ventouse" :px="4" />
            </div>
            <div class="px-pop absolute" style="left: 78px; bottom: 66px">
                <PixelSprite name="caca_pq" :px="4" />
            </div>
            <p
                class="font-pixel px-pop absolute m-0"
                style="
                    left: 8px;
                    bottom: 7px;
                    font-size: 7px;
                    background: #1b1b2f;
                    color: #f2b134;
                    padding: 5px;
                "
            >
                DIRECTION LA POCHE
            </p>
        </template>
        <p
            v-else
            class="font-pixel absolute m-0"
            style="
                left: 92px;
                top: 26px;
                font-size: 11px;
                background: #fff8e7;
                color: #1b1b2f;
                padding: 5px 7px;
            "
        >
            !?
        </p>
    </SceneStage>
</template>
