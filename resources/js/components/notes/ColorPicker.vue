<script setup lang="ts">
import type { NoteColor } from '@/types/note';

defineProps<{
    modelValue: NoteColor | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: NoteColor | null];
}>();

const colors: { value: NoteColor | null; bg: string; ring: string }[] = [
    { value: null, bg: 'bg-card border border-border', ring: 'ring-foreground' },
    { value: 'yellow', bg: 'bg-yellow-100', ring: 'ring-yellow-400' },
    { value: 'green', bg: 'bg-green-100', ring: 'ring-green-400' },
    { value: 'blue', bg: 'bg-blue-100', ring: 'ring-blue-400' },
    { value: 'pink', bg: 'bg-pink-100', ring: 'ring-pink-400' },
    { value: 'purple', bg: 'bg-purple-100', ring: 'ring-purple-400' },
];
</script>

<template>
    <div class="flex gap-2">
        <button
            v-for="color in colors"
            :key="color.value ?? 'default'"
            type="button"
            class="h-7 w-7 rounded-full transition-all"
            :class="[
                color.bg,
                modelValue === color.value ? `ring-2 ring-offset-2 ${color.ring}` : '',
            ]"
            @click="emit('update:modelValue', color.value)"
        />
    </div>
</template>