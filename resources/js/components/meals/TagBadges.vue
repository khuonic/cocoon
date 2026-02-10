<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { MealTag } from '@/types/meal';

defineProps<{
    tags: MealTag[] | null;
}>();

const tagLabels: Record<MealTag, string> = {
    rapide: 'Rapide',
    vege: 'Végé',
    comfort: 'Comfort',
    leger: 'Léger',
    gourmand: 'Gourmand',
};
</script>

<template>
    <div v-if="tags && tags.length > 0" class="flex flex-wrap gap-1">
        <Badge v-for="tag in tags" :key="tag" variant="secondary">
            {{ tagLabels[tag] ?? tag }}
        </Badge>
    </div>
</template>
