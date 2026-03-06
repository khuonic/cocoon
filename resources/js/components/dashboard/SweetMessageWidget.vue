<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Heart } from 'lucide-vue-next';
import type { SweetMessage } from '@/types/sweet-message';

defineProps<{
    sweetMessage: SweetMessage | null;
}>();

const page = usePage();
const userName = computed(() => (page.props.auth as any).user.name.split(' ')[0]);

const greeting = computed(() => {
    const h = new Date().getHours();
    if (h >= 5 && h < 12) { return `Bonjour ${userName.value} ☀️`; }
    if (h >= 12 && h < 18) { return `Bon après-midi ${userName.value} 🌤️`; }
    if (h >= 18 && h < 22) { return `Bonsoir ${userName.value} 🌙`; }
    return `Bonne nuit ${userName.value} 🌙`;
});
</script>

<template>
    <div class="pb-1 pt-2">
        <h1 class="text-2xl font-bold text-foreground">{{ greeting }}</h1>
        <p v-if="sweetMessage" class="mt-1.5 flex items-start gap-1.5 text-sm text-muted-foreground italic">
            <Heart :size="14" class="mt-0.5 shrink-0 fill-pink-400 text-pink-400" />
            <span>"{{ sweetMessage.content }}"</span>
        </p>
    </div>
</template>
