<script setup lang="ts">
defineProps<{
    label: string;
}>();
</script>

<template>
    <div class="space-y-1">
        <h3 class="px-1 text-xs font-medium uppercase tracking-wider text-muted-foreground">
            {{ label }}
        </h3>
        <div class="space-y-1">
            <slot />
        </div>
    </div>
</template>
