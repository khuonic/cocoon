<script setup lang="ts">
import { computed } from 'vue';
import type { CalendarEvent, CalendarBirthday } from '@/types/calendar';

type CalendarDay = { day: number | null; date: string | null; isOtherMonth?: boolean };

type MultiDayBar = {
    event: CalendarEvent;
    startCol: number;
    endCol: number;
    lane: number;
    showTitle: boolean;
    startsThisWeek: boolean;
    endsThisWeek: boolean;
};

type Badge =
    | { kind: 'event'; event: CalendarEvent }
    | { kind: 'birthday'; birthday: CalendarBirthday };

const MAX_LANES = 2;
const LANE_H = 20; // px per lane

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

// ─── Multi-day bars (spanning) ───────────────────────────────────────────────

const weekBars = computed((): MultiDayBar[] => {
    if (props.activeCategory === 'birthday') { return []; }

    const laneEndCols: number[] = [];
    const bars: MultiDayBar[] = [];

    for (const e of props.events) {
        if (!e.ends_at) { continue; }
        const eStart = e.starts_at.slice(0, 10);
        const eEnd = e.ends_at.slice(0, 10);
        if (eStart === eEnd) { continue; }

        let startCol = -1;
        let endCol = -1;
        for (let i = 0; i < props.days.length; i++) {
            const date = props.days[i].date;
            if (!date) { continue; }
            if (date >= eStart && date <= eEnd) {
                if (startCol === -1) { startCol = i; }
                endCol = i;
            }
        }
        if (startCol === -1) { continue; }

        // Greedy lane assignment: find first lane where endCol < startCol of this bar
        let lane = 0;
        while (lane < MAX_LANES && laneEndCols[lane] !== undefined && laneEndCols[lane] >= startCol) {
            lane++;
        }
        if (lane >= MAX_LANES) { continue; }
        laneEndCols[lane] = endCol;

        bars.push({
            event: e,
            startCol,
            endCol,
            lane,
            showTitle: props.days[startCol].date === eStart || startCol === 0,
            startsThisWeek: props.days[startCol].date === eStart,
            endsThisWeek: props.days[endCol].date === eEnd,
        });
    }

    return bars;
});

const barsHeight = computed((): number => {
    if (weekBars.value.length === 0) { return 0; }
    return (Math.max(...weekBars.value.map(b => b.lane)) + 1) * LANE_H + 2;
});

// ─── Single-day badges per cell ───────────────────────────────────────────────

function badgesForDate(date: string | null): Badge[] {
    if (!date) { return []; }
    const result: Badge[] = [];

    if (props.activeCategory !== 'birthday') {
        for (const e of props.events) {
            const eStart = e.starts_at.slice(0, 10);
            const eEnd = e.ends_at ? e.ends_at.slice(0, 10) : eStart;
            if (eStart !== eEnd) { continue; } // multi-day handled by bars
            if (eStart !== date) { continue; }
            result.push({ kind: 'event', event: e });
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
    <div class="border-t border-border">
        <!-- Barres multi-jours continues -->
        <div v-if="weekBars.length > 0" class="relative" :style="{ height: `${barsHeight}px` }">
            <div
                v-for="bar in weekBars"
                :key="bar.event.id"
                class="absolute flex cursor-pointer items-center overflow-hidden text-[10px] font-medium text-white"
                :class="[
                    bar.startsThisWeek ? 'rounded-l-sm' : '',
                    bar.endsThisWeek ? 'rounded-r-sm' : '',
                ]"
                :style="{
                    left: `calc(${bar.startCol} / 7 * 100% + 1px)`,
                    width: `calc(${bar.endCol - bar.startCol + 1} / 7 * 100% - 2px)`,
                    top: `${bar.lane * LANE_H + 2}px`,
                    height: `${LANE_H - 3}px`,
                    backgroundColor: bar.event.category_color,
                }"
                @click.stop="$emit('openEdit', bar.event)"
            >
                <span v-if="bar.showTitle" class="truncate px-1.5">{{ bar.event.title }}</span>
            </div>
        </div>

        <!-- Grille des jours -->
        <div class="grid grid-cols-7">
            <div
                v-for="(cell, i) in days"
                :key="i"
                class="relative min-h-[68px] cursor-pointer border-b border-r border-border p-1 last:border-r-0"
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

                <!-- Badges événements 1 jour + anniversaires -->
                <template v-if="cell.date">
                    <template v-for="(badge, bi) in badgesForDate(cell.date).slice(0, 2)" :key="bi">
                        <div
                            v-if="badge.kind === 'event'"
                            class="mb-px truncate rounded-sm px-1 py-px text-[10px] font-medium leading-4 text-white"
                            :style="{ backgroundColor: badge.event.category_color }"
                            @click.stop="$emit('openEdit', badge.event)"
                        >
                            {{ badge.event.title }}
                        </div>
                        <div
                            v-else
                            class="mb-px truncate rounded-sm px-1 py-px text-[10px] font-medium leading-4 text-white"
                            style="background-color: #EC4899"
                        >
                            🎂 {{ badge.birthday.name }}
                        </div>
                    </template>
                    <span
                        v-if="badgesForDate(cell.date).length > 2"
                        class="text-[10px] text-muted-foreground"
                    >
                        +{{ badgesForDate(cell.date).length - 2 }}
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
