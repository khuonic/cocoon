<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays } from 'lucide-vue-next';

type TodayItem = {
    type: 'event' | 'birthday';
    title: string;
    time: string | null;
    color: string;
    age?: number;
};

defineProps<{
    items: TodayItem[];
    totalCount: number;
}>();
</script>

<template>
    <div class="rounded-xl bg-card p-4 shadow-sm">
        <div class="mb-3 flex items-center gap-2 text-xs font-medium text-muted-foreground">
            <CalendarDays :size="14" />
            Aujourd'hui
        </div>

        <div v-if="items.length === 0" class="text-sm text-muted-foreground">
            Rien de prévu aujourd'hui 🎉
        </div>

        <ul v-else class="space-y-2">
            <li v-for="(item, i) in items" :key="i" class="flex items-center gap-2.5">
                <span
                    class="size-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: item.color }"
                />
                <span class="flex-1 truncate text-sm text-foreground">
                    {{ item.title }}
                    <span v-if="item.age !== undefined" class="text-muted-foreground">
                        — {{ item.age }} ans
                    </span>
                </span>
                <span v-if="item.time" class="shrink-0 text-xs text-muted-foreground">
                    {{ item.time }}
                </span>
            </li>
        </ul>

        <Link
            v-if="totalCount > 5"
            href="/calendar"
            class="mt-3 block text-xs text-primary"
        >
            +{{ totalCount - 5 }} de plus →
        </Link>
    </div>
</template>
