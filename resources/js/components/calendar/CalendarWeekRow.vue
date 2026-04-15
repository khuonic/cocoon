<script setup lang="ts">
import { computed } from 'vue';
import type { CalendarEvent, CalendarBirthday } from '@/types/calendar';

type CalendarDay = { day: number | null; date: string | null; isOtherMonth?: boolean };

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

const MAX_LANES = 2;

const weekDates = props.days
    .map(d => d.date)
    .filter((d): d is string => d !== null);

const weekStart = weekDates[0] ?? '';
const weekEnd = weekDates[weekDates.length - 1] ?? '';

// ─── Events filtrés pour cette semaine ───────────────────────────────────────

const weekEvents = computed(() => {
    if (props.activeCategory === 'birthday') return [];
    return props.events.filter(e => {
        const s = e.starts_at.slice(0, 10);
        const end = (e.ends_at ?? e.starts_at).slice(0, 10);
        return s <= weekEnd && end >= weekStart;
    });
});

const multiDayEvents = computed(() =>
    weekEvents.value
        .filter(e => {
            const s = e.starts_at.slice(0, 10);
            const end = (e.ends_at ?? e.starts_at).slice(0, 10);
            return s !== end;
        })
        .sort((a, b) => a.starts_at.localeCompare(b.starts_at)),
);

const singleDayEvents = computed(() =>
    weekEvents.value.filter(e => {
        const s = e.starts_at.slice(0, 10);
        const end = (e.ends_at ?? e.starts_at).slice(0, 10);
        return s === end;
    }),
);

// ─── Lane assignment (greedy) ─────────────────────────────────────────────────
// Chaque event multi-jours reçoit une lane fixe pour toute la semaine.
// Garantit que les barres ne sont jamais "cassées" entre les jours.

const laneMap = computed(() => {
    const map = new Map<number, number>(); // eventId → lane
    const laneEnd: string[] = [];
    for (const e of multiDayEvents.value) {
        const s = e.starts_at.slice(0, 10);
        const end = (e.ends_at ?? e.starts_at).slice(0, 10);
        let lane = laneEnd.findIndex(le => le < s);
        if (lane === -1) {
            lane = laneEnd.length;
            laneEnd.push(end);
        } else {
            laneEnd[lane] = end;
        }
        map.set(e.id, lane);
    }
    return map;
});

const numLanes = computed(() =>
    laneMap.value.size === 0 ? 0 : Math.max(...laneMap.value.values()) + 1,
);

// ─── Helpers par date ────────────────────────────────────────────────────────

function eventForLane(date: string, lane: number): CalendarEvent | null {
    return (
        multiDayEvents.value.find(e => {
            if (laneMap.value.get(e.id) !== lane) return false;
            const s = e.starts_at.slice(0, 10);
            const end = (e.ends_at ?? e.starts_at).slice(0, 10);
            return date >= s && date <= end;
        }) ?? null
    );
}

function singlesForDate(date: string): CalendarEvent[] {
    return singleDayEvents.value.filter(e => e.starts_at.slice(0, 10) === date);
}

function birthdaysForDate(date: string): CalendarBirthday[] {
    if (props.activeCategory !== 'all' && props.activeCategory !== 'birthday') return [];
    const day = parseInt(date.split('-')[2], 10);
    return props.birthdays.filter(b => b.day === day);
}

function isEventStart(e: CalendarEvent, date: string): boolean {
    return e.starts_at.slice(0, 10) === date;
}

function isEventEnd(e: CalendarEvent, date: string): boolean {
    return (e.ends_at ?? e.starts_at).slice(0, 10) === date;
}

function showEventTitle(e: CalendarEvent, date: string): boolean {
    return isEventStart(e, date) || date === weekStart;
}

// ─── Données précalculées par cellule ────────────────────────────────────────

type Extra =
    | { kind: 'event'; item: CalendarEvent }
    | { kind: 'birthday'; item: CalendarBirthday };

type CellData = {
    lanes: Array<CalendarEvent | null>;
    visibleExtras: Extra[];
    overflow: number;
};

const cellsData = computed((): Array<CellData | null> => {
    return props.days.map(cell => {
        if (!cell.date) return null;
        const date = cell.date;

        // Lanes fixes pour les events multi-jours
        const visLanes = Math.min(numLanes.value, MAX_LANES);
        const lanes: Array<CalendarEvent | null> = Array.from(
            { length: visLanes },
            (_, i) => eventForLane(date, i),
        );

        // Events cachés (lanes > MAX_LANES) présents sur ce jour
        let overflow = 0;
        for (let i = MAX_LANES; i < numLanes.value; i++) {
            if (eventForLane(date, i)) overflow++;
        }

        // Single-day events + anniversaires (pas sur les cases des mois adjacents)
        const singles = singlesForDate(date);
        const bdays = cell.isOtherMonth ? [] : birthdaysForDate(date);
        const extras: Extra[] = [
            ...singles.map(e => ({ kind: 'event' as const, item: e })),
            ...bdays.map(b => ({ kind: 'birthday' as const, item: b })),
        ];

        // Max 2 items visibles au total, multi-jours en priorité
        const remainingSlots = Math.max(0, 2 - visLanes);
        const visibleExtras = extras.slice(0, remainingSlots);
        overflow += extras.length - visibleExtras.length;

        return { lanes, visibleExtras, overflow };
    });
});
</script>

<template>
    <div class="grid grid-cols-7 border-t border-border">
        <div
            v-for="(cell, i) in days"
            :key="i"
            class="relative min-h-[108px] cursor-pointer overflow-hidden border-b border-r border-border p-1 last:border-r-0"
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

            <template v-if="cell.date && cellsData[i]">
                <!-- Lanes multi-jours (positions fixes) -->
                <div
                    v-for="(evt, li) in cellsData[i]!.lanes"
                    :key="`lane-${li}`"
                    class="mb-px h-4"
                >
                    <div
                        v-if="evt"
                        class="h-full py-px text-[10px] font-medium leading-4 text-white"
                        :class="[
                            isEventStart(evt, cell.date) ? 'rounded-l-sm pl-1' : '-ml-1 pl-0.5',
                            isEventEnd(evt, cell.date)   ? 'rounded-r-sm pr-1' : '-mr-1 pr-0.5',
                            showEventTitle(evt, cell.date) ? 'truncate' : '',
                        ]"
                        :style="{ backgroundColor: evt.category_color }"
                        @click.stop="$emit('openEdit', evt)"
                    >
                        <span v-if="showEventTitle(evt, cell.date)">{{ evt.title }}</span>
                        <span v-else>&nbsp;</span>
                    </div>
                    <!-- Spacer invisible pour maintenir la position -->
                </div>

                <!-- Events single-day et anniversaires (2 max) -->
                <template v-for="(extra, ei) in cellsData[i]!.visibleExtras" :key="`extra-${ei}`">
                    <div
                        v-if="extra.kind === 'event'"
                        class="-mx-1 mb-px truncate rounded px-1 py-px text-[10px] font-medium leading-4 text-white"
                        :style="{ backgroundColor: extra.item.category_color }"
                        @click.stop="$emit('openEdit', extra.item)"
                    >
                        {{ extra.item.title }}
                    </div>
                    <div
                        v-else
                        class="-mx-1 mb-px truncate rounded px-1 py-px text-[10px] font-medium leading-4 text-white"
                        style="background-color: #EC4899"
                    >
                        🎂 {{ extra.item.name }}
                    </div>
                </template>

                <!-- Overflow -->
                <span
                    v-if="cellsData[i]!.overflow > 0"
                    class="text-[11px] font-semibold text-foreground"
                >
                    +{{ cellsData[i]!.overflow }}
                </span>
            </template>
        </div>
    </div>
</template>