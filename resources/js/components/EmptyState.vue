<script setup lang="ts">
import type { LucideIcon } from 'lucide-vue-next';
import { PackageOpen } from 'lucide-vue-next';

type Props = {
    title: string;
    description?: string;
    icon?: LucideIcon;
};

withDefaults(defineProps<Props>(), {
    description: undefined,
    icon: () => PackageOpen,
});
</script>

<template>
    <div class="flex flex-col items-center justify-center px-6 py-16 text-center">
        <div class="mb-4 rounded-2xl bg-muted p-4">
            <component :is="icon" :size="32" class="text-muted-foreground" />
        </div>
        <h3 class="text-lg font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="mt-1 max-w-xs text-sm text-muted-foreground">
            {{ description }}
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
