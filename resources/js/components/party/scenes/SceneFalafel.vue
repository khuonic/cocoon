<script setup lang="ts">
import PixelSprite from '../PixelSprite.vue';
import SceneStage from './SceneStage.vue';

defineProps<{ action: boolean }>();
</script>

<template>
    <SceneStage background="#F4EBD0">
        <!-- Menu -->
        <div
            class="absolute"
            style="
                left: 14px;
                top: 10px;
                width: 100px;
                height: 30px;
                background: #22223b;
            "
        >
            <p
                class="font-pixel m-0"
                style="padding: 10px 8px; font-size: 8px; color: #f2b134"
            >
                FALAFEL
            </p>
        </div>
        <!-- Antoine derrière le comptoir -->
        <div v-if="action" class="absolute" style="left: 34px; bottom: 30px">
            <PixelSprite name="adulte_croque" :px="4" />
        </div>
        <div v-else class="absolute" style="left: 34px; bottom: 30px">
            <PixelSprite name="adulte" :px="4" />
        </div>
        <!-- Main du patron -->
        <div
            v-if="action"
            class="px-walk-in absolute"
            style="left: 64px; bottom: 90px"
        >
            <PixelSprite name="falafel_main" :px="4" />
        </div>
        <div v-else class="px-bounce absolute" style="right: 0; bottom: 74px">
            <PixelSprite name="falafel_main" :px="4" />
        </div>
        <p
            v-if="action"
            class="font-pixel px-pop absolute m-0"
            style="right: 8px; top: 52px; font-size: 9px; color: #c8553d"
        >
            AÏE !
        </p>
        <!-- Comptoir -->
        <div
            class="absolute inset-x-0 bottom-0"
            style="
                height: 50px;
                background: #8b5a3c;
                border-top: 6px solid #6b4433;
            "
        />
    </SceneStage>
</template>
