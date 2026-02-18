<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import JokeWidget from '@/components/dashboard/JokeWidget.vue';
import PinnedBookmarksWidget from '@/components/dashboard/PinnedBookmarksWidget.vue';
import SweetMessageWidget from '@/components/dashboard/SweetMessageWidget.vue';
import TodayBirthdaysWidget from '@/components/dashboard/TodayBirthdaysWidget.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { TodayBirthday } from '@/types/birthday';
import type { Bookmark } from '@/types/bookmark';
import type { SweetMessage } from '@/types/sweet-message';

defineProps<{
    sweetMessage: SweetMessage | null;
    mySweetMessage: SweetMessage | null;
    todayBirthdays: TodayBirthday[];
    joke: { id: number; content: string } | null;
    pinnedBookmarks: Bookmark[];
}>();
</script>

<template>
    <Head title="Accueil" />

    <AppLayout title="Cocon">
        <div class="space-y-4 p-4">
            <SweetMessageWidget
                :sweet-message="sweetMessage"
                :my-sweet-message="mySweetMessage"
            />

            <TodayBirthdaysWidget :birthdays="todayBirthdays" />

            <PinnedBookmarksWidget :bookmarks="pinnedBookmarks" />

            <JokeWidget :joke="joke" />
        </div>
    </AppLayout>
</template>
