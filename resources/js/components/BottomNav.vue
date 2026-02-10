<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Home,
    Wallet,
    ShoppingCart,
    CheckSquare,
    MoreHorizontal,
} from 'lucide-vue-next';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const { isCurrentUrl } = useCurrentUrl();

const tabs = [
    { label: 'Accueil', href: '/', icon: Home },
    { label: 'Budget', href: '/expenses', icon: Wallet },
    { label: 'Courses', href: '/shopping-lists', icon: ShoppingCart },
    { label: 'Tâches', href: '/todos', icon: CheckSquare },
    { label: 'Plus', href: '/more', icon: MoreHorizontal },
];

function isActive(href: string): boolean {
    if (href === '/') {
        return isCurrentUrl('/');
    }
    return isCurrentUrl(href) || new URL(window.location.href).pathname.startsWith(href);
}
</script>

<template>
    <nav class="shrink-0 border-t border-border bg-card safe-area-bottom">
        <div class="flex items-center justify-around">
            <Link
                v-for="tab in tabs"
                :key="tab.href"
                :href="tab.href"
                class="flex flex-1 flex-col items-center gap-0.5 py-2 text-xs transition-colors"
                :class="isActive(tab.href)
                    ? 'text-primary'
                    : 'text-muted-foreground'"
            >
                <component :is="tab.icon" :size="22" :stroke-width="isActive(tab.href) ? 2.5 : 2" />
                <span class="font-medium">{{ tab.label }}</span>
            </Link>
        </div>
    </nav>
</template>

<style scoped>
.safe-area-bottom {
    padding-bottom: var(--inset-bottom, env(safe-area-inset-bottom, 0px));
}
</style>
