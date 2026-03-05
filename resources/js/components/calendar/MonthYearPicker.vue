<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps<{
    currentMonth: string; // 'YYYY-MM'
}>();

const isOpen = defineModel<boolean>('open');

const [yearStr, monthStr] = props.currentMonth.split('-');
const pickerYear = ref(parseInt(yearStr));
const pickerMonth = ref(parseInt(monthStr) - 1); // 0-indexed, only used for highlighting

const MONTHS = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

function select(monthIndex: number): void {
    const m = `${pickerYear.value}-${String(monthIndex + 1).padStart(2, '0')}`;
    isOpen.value = false;
    router.get('/calendar', { month: m }, { preserveState: false });
}

function isSelected(monthIndex: number): boolean {
    return pickerYear.value === parseInt(yearStr) && monthIndex === pickerMonth.value;
}
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-200"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition-all duration-150"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="isOpen"
            class="absolute left-1/2 top-full z-50 mt-2 w-72 -translate-x-1/2 rounded-xl bg-card p-4 shadow-xl"
        >
            <!-- Navigation année -->
            <div class="mb-4 flex items-center justify-between">
                <button
                    class="flex size-8 items-center justify-center rounded-full text-muted-foreground active:bg-muted"
                    @click="pickerYear--"
                >
                    ‹
                </button>
                <span class="text-base font-semibold">{{ pickerYear }}</span>
                <button
                    class="flex size-8 items-center justify-center rounded-full text-muted-foreground active:bg-muted"
                    @click="pickerYear++"
                >
                    ›
                </button>
            </div>

            <!-- Grille mois 4x3 -->
            <div class="grid grid-cols-4 gap-2">
                <button
                    v-for="(label, i) in MONTHS"
                    :key="i"
                    class="rounded-lg py-2 text-sm font-medium transition-colors"
                    :class="isSelected(i)
                        ? 'bg-primary text-primary-foreground'
                        : 'text-foreground active:bg-muted'"
                    @click="select(i)"
                >
                    {{ label }}
                </button>
            </div>
        </div>
    </Transition>

    <!-- Overlay pour fermer -->
    <div v-if="isOpen" class="fixed inset-0 z-40" @click="isOpen = false" />
</template>
