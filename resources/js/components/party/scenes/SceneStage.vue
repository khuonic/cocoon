<script setup lang="ts">
import { useElementSize } from '@vueuse/core';
import { computed, useTemplateRef } from 'vue';

/**
 * Scène dessinée dans un repère fixe de 180×150, agrandie à la largeur disponible.
 */
const props = withDefaults(
    defineProps<{ background: string; maxScale?: number }>(),
    { maxScale: 3 },
);

const STAGE_WIDTH = 180;
const STAGE_HEIGHT = 150;

const container = useTemplateRef<HTMLElement>('container');
const { width } = useElementSize(container);

const scale = computed(() =>
    Math.min(props.maxScale, Math.max(1, width.value / STAGE_WIDTH)),
);
</script>

<template>
    <div
        ref="container"
        class="relative w-full overflow-hidden"
        :style="{ height: `${STAGE_HEIGHT * scale}px`, background }"
    >
        <div
            class="absolute top-0 left-1/2"
            :style="{
                width: `${STAGE_WIDTH}px`,
                height: `${STAGE_HEIGHT}px`,
                transform: `translateX(-50%) scale(${scale})`,
                transformOrigin: 'top center',
            }"
        >
            <slot />
        </div>
    </div>
</template>
