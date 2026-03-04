<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import JokeWidget from '@/components/dashboard/JokeWidget.vue';
import SweetMessageWidget from '@/components/dashboard/SweetMessageWidget.vue';
import TodayWidget from '@/components/dashboard/TodayWidget.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import type { SweetMessage } from '@/types/sweet-message';

type TodayItem = {
    type: 'event' | 'birthday';
    title: string;
    time: string | null;
    color: string;
    age?: number;
};

defineProps<{
    sweetMessage: SweetMessage | null;
    mySweetMessage: SweetMessage | null;
    todayItems: TodayItem[];
    todayItemsCount: number;
    joke: { id: number; content: string } | null;
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

            <TodayWidget :items="todayItems" :total-count="todayItemsCount" />

            <JokeWidget :joke="joke" />
        </div>
    </AppLayout>
</template>
