<script setup lang="ts">
import { Button } from '@/components/ui/button';
import type { TagOption, MealTag } from '@/types/meal';

defineProps<{
    availableTags: TagOption[];
}>();

const selectedTags = defineModel<MealTag[]>('selectedTags', { default: [] });

function toggle(tag: MealTag): void {
    const index = selectedTags.value.indexOf(tag);
    if (index === -1) {
        selectedTags.value = [...selectedTags.value, tag];
    } else {
        selectedTags.value = selectedTags.value.filter((t) => t !== tag);
    }
}
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            v-for="tag in availableTags"
            :key="tag.value"
            type="button"
            size="sm"
            :variant="selectedTags.includes(tag.value) ? 'default' : 'outline'"
            @click="toggle(tag.value)"
        >
            {{ tag.label }}
        </Button>
    </div>
</template>
