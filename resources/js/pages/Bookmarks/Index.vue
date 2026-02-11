<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Plus, Bookmark as BookmarkIcon } from 'lucide-vue-next';
import { ref, computed } from 'vue';

import BookmarkCard from '@/components/bookmarks/BookmarkCard.vue';
import BookmarkFormDialog from '@/components/bookmarks/BookmarkFormDialog.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Bookmark, BookmarkCategory, BookmarkCategoryOption } from '@/types/bookmark';

const props = defineProps<{
    bookmarks: Bookmark[];
    categories: BookmarkCategoryOption[];
}>();

const showDialog = ref(false);
const editingBookmark = ref<Bookmark | undefined>();
const selectedCategory = ref<BookmarkCategory | null>(null);

const filteredBookmarks = computed(() => {
    if (!selectedCategory.value) return props.bookmarks;
    return props.bookmarks.filter((b) => b.category === selectedCategory.value);
});

function selectCategory(value: BookmarkCategory | null): void {
    selectedCategory.value = selectedCategory.value === value ? null : value;
}

function openCreate(): void {
    editingBookmark.value = undefined;
    showDialog.value = true;
}

function openEdit(bookmark: Bookmark): void {
    editingBookmark.value = bookmark;
    showDialog.value = true;
}
</script>

<template>
    <Head title="Bookmarks" />

    <AppLayout title="Bookmarks">
        <template #header-right>
            <Button v-if="bookmarks.length > 0" variant="ghost" size="icon" @click="openCreate">
                <Plus :size="20" />
            </Button>
        </template>

        <div class="p-4">
            <!-- Category filter -->
            <div v-if="bookmarks.length > 0" class="mb-4 flex gap-2 overflow-x-auto">
                <Button
                    size="sm"
                    :variant="selectedCategory === null ? 'default' : 'outline'"
                    @click="selectCategory(null)"
                >
                    Tous
                </Button>
                <Button
                    v-for="cat in categories"
                    :key="cat.value"
                    size="sm"
                    :variant="selectedCategory === cat.value ? 'default' : 'outline'"
                    @click="selectCategory(cat.value)"
                >
                    {{ cat.label }}
                </Button>
            </div>

            <EmptyState
                v-if="filteredBookmarks.length === 0 && bookmarks.length === 0"
                title="Aucun bookmark"
                description="Sauvegarde des liens utiles pour les retrouver facilement."
                :icon="BookmarkIcon"
            >
                <template #action>
                    <Button @click="openCreate">Ajouter un bookmark</Button>
                </template>
            </EmptyState>

            <EmptyState
                v-else-if="filteredBookmarks.length === 0"
                title="Aucun bookmark dans cette catégorie"
                description="Essaie un autre filtre ou ajoute un bookmark."
                :icon="BookmarkIcon"
            />

            <div v-else class="space-y-3">
                <BookmarkCard
                    v-for="bookmark in filteredBookmarks"
                    :key="bookmark.id"
                    :bookmark="bookmark"
                    @edit="openEdit"
                />
            </div>
        </div>

        <BookmarkFormDialog
            v-model:open="showDialog"
            :bookmark="editingBookmark"
            :categories="categories"
        />
    </AppLayout>
</template>
