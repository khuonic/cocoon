<script setup lang="ts">
import { ref } from 'vue';
import { sync } from '@/services/sync-client';
import { Button } from '@/components/ui/button';
import { RefreshCw } from 'lucide-vue-next';

const syncing = ref(false);

async function handleSync(): Promise<void> {
    if (syncing.value) return;
    syncing.value = true;
    try {
        await sync();
    } finally {
        syncing.value = false;
    }
}
</script>

<template>
    <Button variant="ghost" size="icon-xl" :disabled="syncing" @click="handleSync">
        <RefreshCw :size="20" class="text-muted-foreground" :class="{ 'animate-spin': syncing }" />
    </Button>
</template>