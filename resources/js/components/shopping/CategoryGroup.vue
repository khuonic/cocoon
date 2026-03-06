<script setup lang="ts">
import { ref } from 'vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { ChevronDown } from 'lucide-vue-next';

defineProps<{
    label: string;
}>();

const isOpen = ref(true);
</script>

<template>
    <Collapsible v-model:open="isOpen">
        <CollapsibleTrigger class="flex w-full items-center gap-2 rounded-lg px-1 py-2">
            <span class="text-xs font-medium uppercase tracking-wider text-muted-foreground">
                {{ label }}
            </span>
            <ChevronDown
                :size="14"
                class="ml-auto text-muted-foreground transition-transform"
                :class="isOpen ? 'rotate-0' : '-rotate-90'"
            />
        </CollapsibleTrigger>
        <CollapsibleContent>
            <div class="grid grid-cols-3 gap-2">
                <slot />
            </div>
        </CollapsibleContent>
    </Collapsible>
</template>
