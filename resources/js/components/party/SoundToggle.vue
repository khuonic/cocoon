<script setup lang="ts">
defineProps<{ muted: boolean }>();
defineEmits<{ toggle: [] }>();
</script>

<template>
    <button
        type="button"
        class="flex size-9 items-center justify-center border-0"
        style="background: var(--px-cream)"
        :aria-label="muted ? 'Remettre le son' : 'Couper le son'"
        :aria-pressed="!muted"
        @click="$emit('toggle')"
    >
        <svg
            width="20"
            height="20"
            viewBox="0 0 10 10"
            shape-rendering="crispEdges"
            aria-hidden="true"
        >
            <rect x="1" y="4" width="2" height="2" fill="#1B1B2F" />
            <rect x="3" y="3" width="1" height="4" fill="#1B1B2F" />
            <rect x="4" y="2" width="1" height="6" fill="#1B1B2F" />
            <template v-if="muted">
                <rect x="6" y="3" width="1" height="1" fill="#E4572E" />
                <rect x="7" y="4" width="1" height="2" fill="#E4572E" />
                <rect x="8" y="3" width="1" height="1" fill="#E4572E" />
                <rect x="6" y="6" width="1" height="1" fill="#E4572E" />
                <rect x="8" y="6" width="1" height="1" fill="#E4572E" />
            </template>
            <template v-else>
                <rect x="6" y="4" width="1" height="2" fill="#1B1B2F" />
                <rect x="8" y="2" width="1" height="6" fill="#1B1B2F" />
            </template>
        </svg>
    </button>
</template>
