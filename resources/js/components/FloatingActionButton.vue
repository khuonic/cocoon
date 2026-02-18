<script setup lang="ts">
import { Plus } from 'lucide-vue-next';

defineEmits<{
    click: [];
}>();
</script>

<template>
    <button
        class="fixed right-4 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform active:scale-95"
        style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px)"
        @click="$emit('click')"
    >
        <Plus :size="26" />
    </button>
</template>
