<script setup lang="ts">
import {
    ShoppingCart,
    Utensils,
    Home,
    Gamepad2,
    HeartPulse,
    Car,
    Repeat,
    Ellipsis,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    name: string;
    color?: string;
    size?: number;
}>();

const iconMap: Record<string, unknown> = {
    'shopping-cart': ShoppingCart,
    'utensils': Utensils,
    'home': Home,
    'gamepad': Gamepad2,
    'heart-pulse': HeartPulse,
    'car': Car,
    'repeat': Repeat,
    'ellipsis': Ellipsis,
};

const icon = computed(() => iconMap[props.name] ?? Ellipsis);
</script>

<template>
    <component
        :is="icon"
        :size="size ?? 20"
        :style="color ? { color } : undefined"
    />
</template>
