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
    <div class="flex min-h-dvh flex-col bg-background">
        <!-- Header -->
        <header v-if="title" class="sticky top-0 z-40 border-b border-border bg-card/95 backdrop-blur-sm safe-area-top">
            <div class="flex h-14 items-center px-4">
                <h1 class="text-lg font-semibold text-foreground">{{ title }}</h1>
                <div class="ml-auto">
                    <slot name="header-right" />
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="flex-1 pb-20">
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomNav />
    </div>
</template>

<style scoped>
.safe-area-top {
    padding-top: env(safe-area-inset-top, 0px);
}
</style>
