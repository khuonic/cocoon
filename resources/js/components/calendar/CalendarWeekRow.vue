<script setup lang="ts">
import { computed } from 'vue';
import type { CalendarEvent, CalendarBirthday } from '@/types/calendar';

type CalendarDay = { day: number | null; date: string | null };

interface SpanItem {
    event: CalendarEvent;
    colStart: number; // 1-based
    colEnd: number;   // 1-based
    lane: number;     // 0 or 1
}

const BIRTHDAY_COLOR = '#EC4899';
const MAX_DOTS = 3;

const props = defineProps<{
    days: CalendarDay[];
    events: CalendarEvent[];
    birthdays: CalendarBirthday[];
    todayStr: string;
    activeCategory: string;
}>();

const emit = defineEmits<{
    openDay: [date: string];
    openEdit: [event: CalendarEvent];
}>();

// ─── Spanning events ───────────────────────────────────────────────────────

const validDates = computed(() => props.days.filter(d => d.date).map(d => d.date!));
const weekFirst = computed(() => validDates.value[0] ?? '');
const weekLast = computed(() => validDates.value[validDates.value.length - 1] ?? '');

const spanningEvents = computed<SpanItem[]>(() => {
    if (!weekFirst.value || !weekLast.value) { return []; }

    const spanners = props.events.filter(e => {
        if (!e.ends_at) { return false; }
        const eStart = e.starts_at.slice(0, 10);
        const eEnd = e.ends_at.slice(0, 10);
        if (eStart === eEnd) { return false; }
        return eStart <= weekLast.value && eEnd >= weekFirst.value;
    });

    // Assign lanes (greedy, max 2)
    const laneEnds: number[] = []; // last colEnd per lane
    const result: SpanItem[] = [];

    for (const e of spanners) {
        const eStart = e.starts_at.slice(0, 10);
        const eEnd = e.ends_at!.slice(0, 10);

        let colStart = 1;
        for (let i = 0; i < props.days.length; i++) {
            if (props.days[i].date && props.days[i].date! >= eStart) {
                colStart = i + 1;
                break;
            }
        }

        let colEnd = 7;
        for (let i = props.days.length - 1; i >= 0; i--) {
            if (props.days[i].date && props.days[i].date! <= eEnd) {
                colEnd = i + 1;
                break;
            }
        }

        // Find available lane
        let lane = laneEnds.findIndex(end => end < colStart);
        if (lane === -1) {
            lane = laneEnds.length;
        }
        if (lane < 2) {
            laneEnds[lane] = colEnd;
            result.push({ event: e, colStart, colEnd, lane });
        }
        // Max 2 lanes; skip events beyond that
    }

    return result;
});

// ─── Single-day events per cell ────────────────────────────────────────────

function singleDayEventsForDate(date: string | null): CalendarEvent[] {
    if (!date) { return []; }
    if (props.activeCategory === 'birthday') { return []; }
    return props.events.filter(e => {
        const eStart = e.starts_at.slice(0, 10);
        const eEnd = e.ends_at ? e.ends_at.slice(0, 10) : eStart;
        // Single-day = same start and end date, OR show on start date for non-spanning
        return eStart === eEnd && eStart === date;
    });
}

function birthdaysForDate(date: string | null): CalendarBirthday[] {
    if (!date || (props.activeCategory !== 'all' && props.activeCategory !== 'birthday')) { return []; }
    const day = parseInt(date.split('-')[2]);
    return props.birthdays.filter(b => b.day === day);
}

function dotsForDate(date: string | null): Array<{ color: string }> {
    const dots: Array<{ color: string }> = [];
    for (const e of singleDayEventsForDate(date)) {
        dots.push({ color: e.category_color });
        if (dots.length >= MAX_DOTS) { break; }
    }
    for (const _b of birthdaysForDate(date)) {
        if (dots.length < MAX_DOTS) {
            dots.push({ color: BIRTHDAY_COLOR });
        }
    }
    return dots;
}

function hasOverflow(date: string | null): boolean {
    if (!date) { return false; }
    return singleDayEventsForDate(date).length + birthdaysForDate(date).length > MAX_DOTS;
}

// ─── Lane rendering helpers ────────────────────────────────────────────────

const maxLanes = computed(() => {
    if (spanningEvents.value.length === 0) { return 0; }
    return Math.max(...spanningEvents.value.map(s => s.lane)) + 1;
});

function getLaneItems(lane: number): SpanItem[] {
    return spanningEvents.value.filter(s => s.lane === lane);
}
</script>

<template>
    <div>
        <!-- Lane rows for multi-day events -->
        <template v-if="maxLanes > 0">
            <div
                v-for="lane in maxLanes"
                :key="lane"
                class="mb-0.5 grid h-5 grid-cols-7 gap-x-0.5"
            >
                <div
                    v-for="item in getLaneItems(lane - 1)"
                    :key="item.event.id"
                    class="flex cursor-pointer items-center overflow-hidden rounded-sm px-1 text-[10px] font-medium text-white"
                    :style="{
                        gridColumn: `${item.colStart} / ${item.colEnd + 1}`,
                        backgroundColor: item.event.category_color,
                    }"
                    @click="$emit('openEdit', item.event)"
                >
                    <span class="truncate">{{ item.event.title }}</span>
                </div>
            </div>
        </template>

        <!-- Day cells -->
        <div class="grid grid-cols-7">
            <div
                v-for="(cell, i) in days"
                :key="i"
                class="flex flex-col items-center gap-0.5 py-1"
                :class="cell.day ? 'cursor-pointer' : ''"
                @click="cell.date && $emit('openDay', cell.date)"
            >
                <span
                    v-if="cell.day"
                    class="flex size-8 items-center justify-center rounded-full text-sm font-medium"
                    :class="todayStr === cell.date
                        ? 'bg-primary text-primary-foreground'
                        : 'text-foreground'"
                >
                    {{ cell.day }}
                </span>
                <span v-else class="size-8" />

                <!-- Dots -->
                <div class="flex gap-0.5">
                    <span
                        v-for="(dot, di) in dotsForDate(cell.date)"
                        :key="di"
                        class="size-1.5 rounded-full"
                        :style="{ backgroundColor: dot.color }"
                    />
                    <span
                        v-if="hasOverflow(cell.date)"
                        class="text-[9px] leading-none text-muted-foreground"
                    >+</span>
                </div>
            </div>
        </div>
    </div>
</template>
