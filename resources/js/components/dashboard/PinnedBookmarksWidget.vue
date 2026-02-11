<script setup lang="ts">
import { Bookmark, ExternalLink } from 'lucide-vue-next';
import type { Bookmark as BookmarkType } from '@/types/bookmark';

defineProps<{
    bookmarks: BookmarkType[];
}>();

function truncateUrl(url: string): string {
    try {
        return new URL(url).hostname;
    } catch {
        return url;
    }
}
</script>

<template>
    <div v-if="bookmarks.length > 0" class="rounded-xl bg-card p-4 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-medium text-blue-500">
            <Bookmark :size="14" />
            Liens rapides
        </div>
        <div class="mt-2 space-y-1.5">
            <a
                v-for="bookmark in bookmarks"
                :key="bookmark.id"
                :href="bookmark.url"
                target="_blank"
                class="flex items-center gap-2 text-sm text-foreground hover:text-primary"
            >
                <ExternalLink :size="12" class="shrink-0 text-muted-foreground" />
                <span class="truncate">{{ bookmark.title }}</span>
                <span class="truncate text-[10px] text-muted-foreground">{{ truncateUrl(bookmark.url) }}</span>
            </a>
        </div>
    </div>
</template>
