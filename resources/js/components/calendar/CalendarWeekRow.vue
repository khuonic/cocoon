<script setup lang="ts">
import type { CalendarEvent, CalendarBirthday } from '@/types/calendar';

type CalendarDay = { day: number | null; date: string | null; isOtherMonth?: boolean };

type Badge =
    | { kind: 'event'; event: CalendarEvent; isStart: boolean; isEnd: boolean; showTitle: boolean }
    | { kind: 'birthday'; birthday: CalendarBirthday };

const props = defineProps<{
    days: CalendarDay[];
    events: CalendarEvent[];
    birthdays: CalendarBirthday[];
    todayStr: string;
    activeCategory: string;
}>();

defineEmits<{
    openDay: [date: string];
    openEdit: [event: CalendarEvent];
}>();

const weekFirstDate = props.days.find(d => d.date)?.date ?? '';

function badgesForDate(date: string | null): Badge[] {
    if (!date) { return []; }
    const result: Badge[] = [];

    if (props.activeCategory !== 'birthday') {
        for (const e of props.events) {
            const eStart = e.starts_at.slice(0, 10);
            const eEnd = e.ends_at ? e.ends_at.slice(0, 10) : eStart;
            if (date < eStart || date > eEnd) { continue; }
            const isStart = date === eStart;
            const isEnd = date === eEnd;
            const showTitle = isStart || date === weekFirstDate;
            result.push({ kind: 'event', event: e, isStart, isEnd, showTitle });
        }
    }

    if (props.activeCategory === 'all' || props.activeCategory === 'birthday') {
        const day = parseInt(date.split('-')[2]);
        for (const b of props.birthdays.filter(b => b.day === day)) {
            result.push({ kind: 'birthday', birthday: b });
        }
    }

    return result;
}
</script>

<template>
    <div class="grid grid-cols-7 border-t border-border">
        <div
            v-for="(cell, i) in days"
            :key="i"
            class="relative min-h-[78px] cursor-pointer overflow-hidden border-b border-r border-border p-1 last:border-r-0"
            :class="[
                cell.isOtherMonth ? 'bg-muted/20' : '',
                cell.date === todayStr ? 'bg-primary/5' : '',
            ]"
            @click="cell.date && $emit('openDay', cell.date)"
        >
            <!-- Numéro du jour -->
            <span
                v-if="cell.day"
                class="mb-1 flex size-6 items-center justify-center rounded-full text-xs font-semibold"
                :class="todayStr === cell.date
                    ? 'bg-primary text-primary-foreground'
                    : cell.isOtherMonth ? 'text-muted-foreground/40' : 'text-foreground'"
            >
                {{ cell.day }}
            </span>

            <!-- Badges -->
            <template v-if="cell.date">
                <template v-for="(badge, bi) in badgesForDate(cell.date).slice(0, 3)" :key="bi">
                    <!-- Événement -->
                    <div
                        v-if="badge.kind === 'event'"
                        class="mb-px py-px text-[10px] font-medium leading-4 text-white"
                        :class="[
                            badge.isStart ? 'rounded-l-sm pl-1' : '-ml-1 pl-0.5',
                            badge.isEnd   ? 'rounded-r-sm pr-1' : '-mr-1 pr-0.5',
                            badge.showTitle ? 'truncate' : '',
                        ]"
                        :style="{ backgroundColor: badge.event.category_color }"
                        @click.stop="$emit('openEdit', badge.event)"
                    >
                        <span v-if="badge.showTitle">{{ badge.event.title }}</span>
                        <span v-else>&nbsp;</span>
                    </div>
                    <!-- Anniversaire -->
                    <div
                        v-else
                        class="mb-px truncate rounded-sm px-1 py-px text-[10px] font-medium leading-4 text-white"
                        style="background-color: #EC4899"
                    >
                        🎂 {{ badge.birthday.name }}
                    </div>
                </template>
                <span
                    v-if="badgesForDate(cell.date).length > 3"
                    class="text-[10px] text-muted-foreground"
                >
                    +{{ badgesForDate(cell.date).length - 3 }}
                </span>
            </template>
        </div>
    </div>
</template>
