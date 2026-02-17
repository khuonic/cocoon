<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    href?: string;
}>();

function handleBack(): void {
    if (props.href) {
        router.visit(props.href);
    } else {
        router.back();
    }
}
</script>

<template>
    <Button variant="ghost" size="icon-xl" @click="handleBack">
        <ArrowLeft :size="22" />
    </Button>
</template>
