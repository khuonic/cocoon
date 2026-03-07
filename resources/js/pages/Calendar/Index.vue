<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, nextTick } from 'vue';
import CalendarWeekRow from '@/components/calendar/CalendarWeekRow.vue';
import EventFormDialog from '@/components/calendar/EventFormDialog.vue';
import MonthYearPicker from '@/components/calendar/MonthYearPicker.vue';
import BirthdayFormDialog from '@/components/birthdays/BirthdayFormDialog.vue';
import { Button } from '@/components/ui/button';
import { CalendarPlus, Cake, Plus } from 'lucide-vue-next';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import type { CalendarBirthday, CalendarEvent, CalendarUser } from '@/types/calendar';

const DAYS_SHORT = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

const CATEGORIES = [
    { value: 'all', label: 'Tout' },
    { value: 'Conges', label: 'Congés', color: '#10B981' },
    { value: 'Pro', label: 'Pro', color: '#3B82F6' },
    { value: 'Loisir', label: 'Loisirs', color: '#8B5CF6' },
    { value: 'Rdv', label: 'RDV', color: '#F59E0B' },
    { value: 'birthday', label: '🎂', color: '#EC4899' },
];

const BIRTHDAY_COLOR = '#EC4899';

const props = defineProps<{
    events: CalendarEvent[];
    birthdays: CalendarBirthday[];
    users: CalendarUser[];
    currentMonth: string; // 'YYYY-MM'
}>();

// ─── Navigation ────────────────────────────────────────────────────────────
const [yearStr, monthStr] = props.currentMonth.split('-');
const year = parseInt(yearStr);
const month = parseInt(monthStr) - 1; // 0-indexed

const monthLabel = computed(() => {
    return new Date(year, month, 1).toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' });
});

function navigate(direction: -1 | 1): void {
    const d = new Date(year, month + direction, 1);
    const newMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
    router.get('/calendar', { month: newMonth }, { preserveState: false });
}

const showMonthPicker = ref(false);

// ─── Filtres ───────────────────────────────────────────────────────────────
const activeCategory = ref('all');
const activeUserIds = ref<number[]>([]); // empty = tous visibles

function toggleUser(userId: number): void {
    const idx = activeUserIds.value.indexOf(userId);
    if (idx === -1) {
        activeUserIds.value.push(userId);
    } else {
        activeUserIds.value.splice(idx, 1);
    }
}

function isUserActive(userId: number): boolean {
    return activeUserIds.value.includes(userId);
}

// ─── Grille mensuelle ──────────────────────────────────────────────────────
const daysInMonth = computed(() => new Date(year, month + 1, 0).getDate());
const firstDayOfWeek = computed(() => {
    const day = new Date(year, month, 1).getDay();
    return (day + 6) % 7; // 0=Mon, 6=Sun
});

type CalendarDay = { day: number | null; date: string | null; isOtherMonth?: boolean };

const calendarGrid = computed<CalendarDay[]>(() => {
    const cells: CalendarDay[] = [];

    // Jours du mois précédent
    const prevMonthLastDay = new Date(year, month, 0);
    for (let i = firstDayOfWeek.value - 1; i >= 0; i--) {
        const d = prevMonthLastDay.getDate() - i;
        const pm = month === 0 ? 12 : month;
        const py = month === 0 ? year - 1 : year;
        cells.push({ day: d, date: `${py}-${String(pm).padStart(2, '0')}-${String(d).padStart(2, '0')}`, isOtherMonth: true });
    }

    // Jours du mois courant
    for (let d = 1; d <= daysInMonth.value; d++) {
        const date = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({ day: d, date, isOtherMonth: false });
    }

    // Jours du mois suivant pour atteindre 42 cases
    let nd = 1;
    const nm = month === 11 ? 1 : month + 2;
    const ny = month === 11 ? year + 1 : year;
    while (cells.length < 42) {
        cells.push({ day: nd, date: `${ny}-${String(nm).padStart(2, '0')}-${String(nd).padStart(2, '0')}`, isOtherMonth: true });
        nd++;
    }

    return cells;
});

const calendarWeeks = computed<CalendarDay[][]>(() => {
    const weeks: CalendarDay[][] = [];
    for (let i = 0; i < calendarGrid.value.length; i += 7) {
        weeks.push(calendarGrid.value.slice(i, i + 7));
    }
    return weeks;
});

const today = new Date();
const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

// ─── Événements filtrés ────────────────────────────────────────────────────
const filteredEvents = computed(() => {
    return props.events.filter((e) => {
        if (activeCategory.value !== 'all' && activeCategory.value !== 'birthday') {
            if (e.category !== activeCategory.value) { return false; }
        }
        if (activeCategory.value === 'birthday') { return false; }
        if (activeUserIds.value.length > 0 && e.is_personal) {
            if (!activeUserIds.value.includes(e.user_id ?? 0)) { return false; }
        }
        return true;
    });
});

// ─── Day Modal ─────────────────────────────────────────────────────────────
const selectedDate = ref<string | null>(null);
const showDayModal = ref(false);
const showEventForm = ref(false);
const editingEvent = ref<CalendarEvent | undefined>();
const eventFormDefaultDate = ref<string>('');

function openDay(date: string): void {
    selectedDate.value = date;
    showDayModal.value = true;
}

const selectedDayLabel = computed(() => {
    if (!selectedDate.value) { return ''; }
    return new Date(selectedDate.value + 'T12:00:00').toLocaleDateString('fr-FR', {
        weekday: 'long', day: 'numeric', month: 'long',
    });
});

function eventsForSelectedDay(): CalendarEvent[] {
    if (!selectedDate.value || activeCategory.value === 'birthday') { return []; }
    return filteredEvents.value.filter(e => {
        const eStart = e.starts_at.slice(0, 10);
        const eEnd = e.ends_at ? e.ends_at.slice(0, 10) : eStart;
        return selectedDate.value! >= eStart && selectedDate.value! <= eEnd;
    });
}

function birthdaysForSelectedDay(): CalendarBirthday[] {
    if (!selectedDate.value || (activeCategory.value !== 'all' && activeCategory.value !== 'birthday')) { return []; }
    const day = parseInt(selectedDate.value.split('-')[2]);
    return props.birthdays.filter(b => b.day === day);
}

async function openCreateFromDay(): Promise<void> {
    editingEvent.value = undefined;
    eventFormDefaultDate.value = selectedDate.value ?? '';
    showDayModal.value = false;
    await nextTick();
    showEventForm.value = true;
}

function openEdit(event: CalendarEvent): void {
    editingEvent.value = event;
    showDayModal.value = false;
    showEventForm.value = true;
}

function openCreate(): void {
    editingEvent.value = undefined;
    eventFormDefaultDate.value = '';
    fabOpen.value = false;
    showEventForm.value = true;
}

// ─── FAB speed dial ─────────────────────────────────────────────────────────
const fabOpen = ref(false);
const showBirthdayForm = ref(false);

function openCreateBirthday(): void {
    fabOpen.value = false;
    showBirthdayForm.value = true;
}

// ─── Swipe pour navigation ───────────────────────────────────────────────
let touchStartX = 0;

function onTouchStart(e: TouchEvent): void {
    touchStartX = e.touches[0].clientX;
}

function onTouchEnd(e: TouchEvent): void {
    const delta = e.changedTouches[0].clientX - touchStartX;
    if (Math.abs(delta) > 60) {
        navigate(delta < 0 ? 1 : -1);
    }
}
</script>

<template>
    <Head title="Calendrier" />

    <AppLayout title="Calendrier">
        <div class="flex flex-col gap-3 p-4 pb-6 pb-safe">
            <!-- Navigation mois -->
            <div class="relative flex items-center justify-between">
                <Button variant="ghost" size="icon" @click="navigate(-1)">
                    <span class="text-lg">‹</span>
                </Button>
                <button
                    class="text-base font-semibold capitalize active:opacity-70"
                    @click="showMonthPicker = !showMonthPicker"
                >
                    {{ monthLabel }}
                </button>
                <Button variant="ghost" size="icon" @click="navigate(1)">
                    <span class="text-lg">›</span>
                </Button>

                <MonthYearPicker
                    v-model:open="showMonthPicker"
                    :current-month="currentMonth"
                />
            </div>

            <!-- Filtres catégories -->
            <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar">
                <button
                    v-for="cat in CATEGORIES"
                    :key="cat.value"
                    class="shrink-0 rounded-full border px-3 py-1 text-xs font-medium transition-all"
                    :style="activeCategory === cat.value && cat.color
                        ? { backgroundColor: cat.color, borderColor: cat.color, color: '#fff' }
                        : cat.color
                            ? { borderColor: cat.color, color: cat.color }
                            : {}"
                    :class="activeCategory === cat.value && !cat.color
                        ? 'bg-primary text-primary-foreground border-primary'
                        : !cat.color ? 'border-border text-muted-foreground' : ''"
                    @click="activeCategory = cat.value"
                >
                    {{ cat.label }}
                </button>
            </div>

            <!-- Filtres utilisateurs -->
            <div v-if="users.length > 1" class="flex gap-2">
                <button
                    v-for="user in users"
                    :key="user.id"
                    class="rounded-full border px-3 py-1 text-xs font-medium transition-all"
                    :class="isUserActive(user.id)
                        ? 'bg-primary text-primary-foreground border-primary'
                        : 'border-border text-muted-foreground'"
                    @click="toggleUser(user.id)"
                >
                    {{ user.name }}
                </button>
            </div>

            <!-- Grille calendrier -->
            <div class="overflow-hidden rounded-xl border border-border bg-card shadow-sm" @touchstart.passive="onTouchStart" @touchend.passive="onTouchEnd">
                <!-- En-têtes jours -->
                <div class="grid grid-cols-7 border-b border-border bg-muted/30">
                    <span
                        v-for="(day, i) in DAYS_SHORT"
                        :key="i"
                        class="py-1.5 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground"
                    >
                        {{ day }}
                    </span>
                </div>

                <!-- Semaines avec multi-day events -->
                <CalendarWeekRow
                    v-for="(week, wi) in calendarWeeks"
                    :key="wi"
                    :days="week"
                    :events="filteredEvents"
                    :birthdays="birthdays"
                    :today-str="todayStr"
                    :active-category="activeCategory"
                    @open-day="openDay"
                    @open-edit="openEdit"
                />
            </div>
        </div>

        <!-- FAB speed dial -->
        <div class="fixed z-40 flex flex-col items-end" style="bottom: calc(var(--inset-bottom, env(safe-area-inset-bottom, 0px)) + 84px); right: 1rem;">
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition-all duration-150"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4"
            >
                <div v-if="fabOpen" class="mb-3 flex flex-col items-end gap-2">
                    <button
                        class="flex items-center gap-2 rounded-full bg-card px-4 py-2 shadow-lg"
                        @click="openCreateBirthday"
                    >
                        <span class="text-sm font-medium text-foreground">Anniversaire</span>
                        <span class="flex size-8 items-center justify-center rounded-full bg-pink-100">
                            <Cake :size="16" class="text-pink-500" />
                        </span>
                    </button>
                    <button
                        class="flex items-center gap-2 rounded-full bg-card px-4 py-2 shadow-lg"
                        @click="openCreate"
                    >
                        <span class="text-sm font-medium text-foreground">Événement</span>
                        <span class="flex size-8 items-center justify-center rounded-full bg-primary/10">
                            <CalendarPlus :size="16" class="text-primary" />
                        </span>
                    </button>
                </div>
            </Transition>

            <button
                class="flex size-14 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform active:scale-95"
                :class="fabOpen ? 'rotate-45' : ''"
                style="transition: transform 0.2s"
                @click="fabOpen = !fabOpen"
            >
                <Plus :size="26" />
            </button>
        </div>

        <div v-if="fabOpen" class="fixed inset-0 z-30" @click="fabOpen = false" />

        <!-- Day Modal -->
        <Dialog :open="showDayModal" @update:open="showDayModal = $event">
            <DialogContent position="center">
                <DialogHeader>
                    <DialogTitle class="capitalize">{{ selectedDayLabel }}</DialogTitle>
                </DialogHeader>

                <div class="space-y-4">
                    <!-- Événements du jour -->
                    <div v-if="eventsForSelectedDay().length > 0" class="space-y-2">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Événements</p>
                        <div
                            v-for="event in eventsForSelectedDay()"
                            :key="event.id"
                            class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors active:bg-accent"
                            @click="openEdit(event)"
                        >
                            <span
                                class="size-3 shrink-0 rounded-full"
                                :style="{ backgroundColor: event.category_color }"
                            />
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ event.title }}</p>
                                <p v-if="!event.all_day" class="text-xs text-muted-foreground">
                                    {{ new Date(event.starts_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                                    <template v-if="event.ends_at">
                                        → {{ new Date(event.ends_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
                                    </template>
                                </p>
                                <p v-if="event.location" class="truncate text-xs text-muted-foreground">📍 {{ event.location }}</p>
                            </div>
                            <span
                                v-if="event.is_personal"
                                class="shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                            >
                                Personnel
                            </span>
                        </div>
                    </div>

                    <!-- Anniversaires du jour -->
                    <div v-if="birthdaysForSelectedDay().length > 0" class="space-y-2">
                        <p class="text-xs font-medium uppercase text-muted-foreground">Anniversaires</p>
                        <div
                            v-for="birthday in birthdaysForSelectedDay()"
                            :key="birthday.id"
                            class="flex items-center gap-3 rounded-lg border p-3"
                            :style="{ borderColor: BIRTHDAY_COLOR + '40' }"
                        >
                            <span class="text-lg">🎂</span>
                            <div>
                                <p class="text-sm font-medium">{{ birthday.name }}</p>
                                <p class="text-xs text-muted-foreground">{{ birthday.age + 1 }} ans</p>
                            </div>
                        </div>
                    </div>

                    <!-- État vide -->
                    <p
                        v-if="eventsForSelectedDay().length === 0 && birthdaysForSelectedDay().length === 0"
                        class="py-4 text-center text-sm text-muted-foreground"
                    >
                        Rien ce jour-là.
                    </p>

                    <Button class="w-full" @click="openCreateFromDay">
                        + Ajouter un événement
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <!-- Event Form Dialog -->
        <EventFormDialog
            v-model:open="showEventForm"
            :event="editingEvent"
            :default-date="eventFormDefaultDate"
        />

        <!-- Birthday Form Dialog -->
        <BirthdayFormDialog v-model:open="showBirthdayForm" />
    </AppLayout>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
