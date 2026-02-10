<script setup lang="ts">
import BottomNav from '@/components/BottomNav.vue';

type Props = {
    title?: string;
};

withDefaults(defineProps<Props>(), {
    title: undefined,
});
</script>

<template>
    <div class="flex h-dvh flex-col overflow-hidden bg-background">
        <!-- Header -->
        <header v-if="title" class="shrink-0 border-b border-border bg-card safe-area-top">
            <div class="flex h-14 items-center px-4">
                <h1 class="text-lg font-semibold text-foreground">{{ title }}</h1>
                <div class="ml-auto">
                    <slot name="header-right" />
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 overflow-y-auto pb-20">
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomNav />
    </div>
</template>

<style scoped>
.safe-area-top {
    padding-top: var(--inset-top, env(safe-area-inset-top, 0px));
}
</style>
