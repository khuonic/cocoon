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

function totalForDate(date: string | null): number {
    if (!date) { return 0; }
    return singleDayEventsForDate(date).length + birthdaysForDate(date).length;
}

/** Combien de slots restants pour les anniversaires après les 2 événements affichés */
function overflowSlots(date: string | null): number {
    if (!date) { return 0; }
    const evCount = singleDayEventsForDate(date).length;
    return Math.max(0, 2 - evCount);
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
        <!-- Lane rows pour événements multi-jours -->
        <template v-if="maxLanes > 0">
            <div
                v-for="lane in maxLanes"
                :key="lane"
                class="mb-px grid h-5 grid-cols-7"
            >
                <div
                    v-for="item in getLaneItems(lane - 1)"
                    :key="item.event.id"
                    class="z-10 flex cursor-pointer items-center overflow-hidden rounded-sm px-1.5 text-[10px] font-medium text-white"
                    :style="{
                        gridColumn: `${item.colStart} / ${item.colEnd + 1}`,
                        backgroundColor: item.event.category_color,
                    }"
                    @click.stop="$emit('openEdit', item.event)"
                >
                    <span class="truncate">{{ item.event.title }}</span>
                </div>
            </div>
        </template>

        <!-- Cellules jours -->
        <div class="grid grid-cols-7 border-t border-border">
            <div
                v-for="(cell, i) in days"
                :key="i"
                class="relative min-h-[78px] cursor-pointer border-b border-r border-border p-1 last:border-r-0"
                :class="[
                    cell.date ? '' : 'bg-muted/30',
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
                        : 'text-foreground'"
                >
                    {{ cell.day }}
                </span>

                <!-- Événements simples comme badges -->
                <template v-if="cell.date">
                    <div
                        v-for="e in singleDayEventsForDate(cell.date).slice(0, 2)"
                        :key="e.id"
                        class="mb-px truncate rounded px-1 py-px text-[10px] font-medium leading-4 text-white"
                        :style="{ backgroundColor: e.category_color }"
                        @click.stop="$emit('openEdit', e)"
                    >
                        {{ e.title }}
                    </div>

                    <!-- Anniversaires -->
                    <div
                        v-for="b in birthdaysForDate(cell.date).slice(0, overflowSlots(cell.date))"
                        :key="b.id"
                        class="mb-px truncate rounded px-1 py-px text-[10px] font-medium leading-4 text-white"
                        style="background-color: #EC4899"
                    >
                        🎂 {{ b.name }}
                    </div>

                    <!-- Overflow -->
                    <span
                        v-if="totalForDate(cell.date) > 2"
                        class="text-[10px] text-muted-foreground"
                    >
                        +{{ totalForDate(cell.date) - 2 }}
                    </span>
                </template>
            </div>
        </div>
    </div>
</template>
