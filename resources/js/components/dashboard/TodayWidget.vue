<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, ChevronRight } from 'lucide-vue-next';

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
    <Link href="/calendar" class="block">
        <div class="rounded-xl bg-card p-4 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2 text-sm font-semibold text-muted-foreground">
                    <CalendarDays :size="16" />
                    Aujourd'hui
                </div>
                <ChevronRight :size="16" class="text-muted-foreground" />
            </div>

            <div v-if="items.length === 0" class="flex items-center gap-2 text-base text-muted-foreground">
                <span>Rien de prévu 🎉</span>
            </div>

            <ul v-else class="space-y-2.5">
                <li
                    v-for="(item, i) in items.slice(0, 5)"
                    :key="i"
                    class="flex items-center gap-3"
                >
                    <span
                        class="size-3 shrink-0 rounded-full"
                        :style="{ backgroundColor: item.color }"
                    />
                    <span class="flex-1 truncate text-base text-foreground">
                        {{ item.title }}
                        <span v-if="item.age !== undefined" class="text-muted-foreground">
                            — {{ item.age }} ans
                        </span>
                    </span>
                    <span v-if="item.time" class="shrink-0 text-sm text-muted-foreground">
                        {{ item.time }}
                    </span>
                </li>
            </ul>

            <p v-if="totalCount > 5" class="mt-3 text-xs text-primary">
                +{{ totalCount - 5 }} de plus
            </p>
        </div>
    </Link>
</template>
