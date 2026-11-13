<script setup lang="ts">
import { computed } from 'vue';
import { basePalette, sprites, type SpriteName } from './sprites';

const props = withDefaults(
    defineProps<{
        name: SpriteName;
        px?: number;
        decorative?: boolean;
    }>(),
    { px: 4, decorative: false },
);

type Run = { x: number; y: number; width: number; color: string };

const sprite = computed(() => sprites[props.name]);
const columns = computed(() => sprite.value.rows[0].length);
const lines = computed(() => sprite.value.rows.length);

/**
 * Regroupe les pixels consécutifs de même couleur pour limiter le nombre de <rect>.
 */
const runs = computed<Run[]>(() => {
    const palette: Record<string, string> = {
        ...basePalette,
        ...('palette' in sprite.value ? sprite.value.palette : {}),
    };
    const result: Run[] = [];

    sprite.value.rows.forEach((row, y) => {
        let x = 0;
        while (x < row.length) {
            const char = row[x];
            let width = 1;
            while (row[x + width] === char) {
                width++;
            }
            if (char !== '.' && palette[char]) {
                result.push({ x, y, width, color: palette[char] });
            }
            x += width;
        }
    });

    return result;
});
</script>

<template>
    <svg
        :width="columns * px"
        :height="lines * px"
        :viewBox="`0 0 ${columns} ${lines}`"
        shape-rendering="crispEdges"
        :role="decorative ? undefined : 'img'"
        :aria-label="decorative ? undefined : sprite.label"
        :aria-hidden="decorative ? 'true' : undefined"
        class="block"
    >
        <rect
            v-for="(run, index) in runs"
            :key="index"
            :x="run.x"
            :y="run.y"
            :width="run.width"
            height="1"
            :fill="run.color"
        />
    </svg>
</template>
