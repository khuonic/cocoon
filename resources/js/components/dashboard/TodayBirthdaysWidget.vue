<script setup lang="ts">
import { Cake } from 'lucide-vue-next';
import type { TodayBirthday } from '@/types/birthday';

defineProps<{
    birthdays: TodayBirthday[];
}>();
</script>

<template>
    <div v-if="birthdays.length > 0" class="rounded-xl bg-card p-4 shadow-sm">
        <div class="flex items-center gap-2 text-xs font-medium text-amber-600">
            <Cake :size="14" />
            Anniversaires du jour
        </div>
        <div class="mt-2 space-y-1">
            <div
                v-for="birthday in birthdays"
                :key="birthday.id"
                class="flex items-center justify-between text-sm"
            >
                <span class="font-medium text-foreground">{{ birthday.name }}</span>
                <span class="text-xs text-muted-foreground">{{ birthday.age }} ans</span>
            </div>
        </div>
    </div>
</template>
