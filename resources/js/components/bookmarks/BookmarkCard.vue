<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Star, Trash2, ExternalLink } from 'lucide-vue-next';
import { toggleFavorite, destroy } from '@/actions/App/Http/Controllers/BookmarkController';
import { Button } from '@/components/ui/button';
import { mobilePatch } from '@/lib/form-helpers';
import type { Bookmark, BookmarkCategory } from '@/types/bookmark';

const props = defineProps<{
    bookmark: Bookmark;
}>();

const emit = defineEmits<{
    edit: [bookmark: Bookmark];
}>();

const categoryLabels: Record<BookmarkCategory, string> = {
    resto: 'Resto',
    voyage: 'Voyage',
    shopping: 'Shopping',
    loisirs: 'Loisirs',
    maison: 'Maison',
    autre: 'Autre',
};

function handleToggleFavorite(): void {
    mobilePatch(toggleFavorite.url(props.bookmark.id), {}, {
        preserveScroll: true,
    });
}

function handleDelete(): void {
    router.delete(destroy.url(props.bookmark.id), {
        preserveScroll: true,
    });
}

function truncateUrl(url: string): string {
    try {
        const parsed = new URL(url);
        const display = parsed.hostname + parsed.pathname;
        return display.length > 40 ? display.slice(0, 40) + '...' : display;
    } catch {
        return url.length > 40 ? url.slice(0, 40) + '...' : url;
    }
}
</script>

<template>
    <div class="flex items-start gap-3 rounded-lg border border-border bg-card p-3">
        <div class="min-w-0 flex-1 cursor-pointer" @click="emit('edit', bookmark)">
            <div class="flex items-center gap-2">
                <h3 class="truncate text-sm font-semibold text-foreground">{{ bookmark.title }}</h3>
            </div>
            <a
                :href="bookmark.url"
                target="_blank"
                class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                @click.stop
            >
                <ExternalLink :size="12" class="shrink-0" />
                <span class="truncate">{{ truncateUrl(bookmark.url) }}</span>
            </a>
            <p v-if="bookmark.description" class="mt-1 line-clamp-2 text-xs text-muted-foreground">
                {{ bookmark.description }}
            </p>
            <span
                v-if="bookmark.category"
                class="mt-1.5 inline-block rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
            >
                {{ categoryLabels[bookmark.category] }}
            </span>
        </div>

        <div class="flex shrink-0 flex-col gap-0.5">
            <Button
                variant="ghost"
                size="icon"
                class="h-7 w-7"
                @click="handleToggleFavorite"
            >
                <Star
                    :size="14"
                    :class="bookmark.is_favorite ? 'fill-yellow-400 text-yellow-400' : 'text-muted-foreground'"
                />
            </Button>
            <Button
                variant="ghost"
                size="icon"
                class="h-7 w-7 text-muted-foreground hover:text-destructive"
                @click="handleDelete"
            >
                <Trash2 :size="14" />
            </Button>
        </div>
    </div>
</template>
