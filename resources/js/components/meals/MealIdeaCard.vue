<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import TagBadges from '@/components/meals/TagBadges.vue';
import { Button } from '@/components/ui/button';
import { ExternalLink, Trash2 } from 'lucide-vue-next';
import type { MealIdea } from '@/types/meal';
import { destroy } from '@/actions/App/Http/Controllers/MealIdeaController';

const props = defineProps<{
    idea: MealIdea;
}>();

const emit = defineEmits<{
    edit: [idea: MealIdea];
}>();

function handleDelete(): void {
    router.delete(destroy.url(props.idea.id), {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="flex items-start gap-3 rounded-lg border border-border bg-card p-3">
        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', idea)">
            <div class="flex items-center gap-2">
                <h3 class="truncate text-sm font-medium text-foreground">{{ idea.name }}</h3>
                <a
                    v-if="idea.url"
                    :href="idea.url"
                    target="_blank"
                    class="shrink-0 text-muted-foreground hover:text-foreground"
                    @click.stop
                >
                    <ExternalLink :size="14" />
                </a>
            </div>
            <p v-if="idea.description" class="mt-0.5 truncate text-xs text-muted-foreground">
                {{ idea.description }}
            </p>
            <div class="mt-1.5">
                <TagBadges :tags="idea.tags" />
            </div>
        </div>
        <Button
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 text-muted-foreground hover:text-destructive"
            @click="handleDelete"
        >
            <Trash2 :size="16" />
        </Button>
    </div>
</template>
