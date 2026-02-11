<script setup lang="ts">
import { Smile } from 'lucide-vue-next';

defineProps<{
    joke: { id: number; content: string } | null;
}>();
</script>

<template>
    <div v-if="joke" class="rounded-xl bg-card p-4 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-medium text-indigo-500">
            <Smile :size="14" />
            Blague du jour
        </div>
        <p class="mt-2 text-sm text-foreground">{{ joke.content }}</p>
    </div>
</template>
