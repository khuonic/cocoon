<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import UpdateDialog from '@/components/UpdateDialog.vue';
import { getToken, isNativePHP, saveCredentials } from '@/services/biometric-auth';
import { configureSyncClient, sync } from '@/services/sync-client';
import { checkForUpdate } from '@/services/update-checker';

type Props = {
    title?: string;
};

withDefaults(defineProps<Props>(), {
    title: undefined,
});

const page = usePage<{
    syncApiUrl?: string;
    appVersionCode?: number;
    flash?: { api_token?: string };
    auth?: { user?: { id: number; name: string; email: string } };
}>();

const updateDialogOpen = ref(false);
const updateInfo = ref<{ version: string; changelog?: string; downloadUrl: string } | null>(null);

onMounted(async () => {
    const syncApiUrl = page.props.syncApiUrl;
    if (syncApiUrl) {
        configureSyncClient(syncApiUrl);
        sync();
    }

    const token = page.props.flash?.api_token;
    const user = page.props.auth?.user;
    if (token && user) {
        saveCredentials(token, {
            id: user.id,
            name: user.name,
            email: user.email,
        });
    }

    if (syncApiUrl && (await isNativePHP())) {
        const storedToken = await getToken();
        if (storedToken) {
            const currentVersionCode = page.props.appVersionCode ?? 0;
            const result = await checkForUpdate(syncApiUrl, currentVersionCode, storedToken);
            if (result.available && result.version && result.downloadUrl) {
                updateInfo.value = {
                    version: result.version,
                    changelog: result.changelog,
                    downloadUrl: result.downloadUrl,
                };
                updateDialogOpen.value = true;
            }
        }
    }
});
</script>

<template>
    <div class="flex h-dvh flex-col overflow-hidden bg-background">
        <!-- Header -->
        <header v-if="title" class="shrink-0 border-b border-border bg-card safe-area-top">
            <div class="flex h-14 items-center px-4">
                <h1 class="text-lg font-semibold text-foreground">{{ title }}</h1>
                <div class="ml-auto">
                    <slot name="header-right" />
                </div>
            </div>
        </header>

        <!-- Main content -->
        <main class="min-h-0 flex-1 overflow-y-auto">
            <slot />
        </main>

        <!-- Bottom navigation -->
        <BottomNav />

        <!-- Update dialog -->
        <UpdateDialog
            v-if="updateInfo"
            :open="updateDialogOpen"
            :version="updateInfo.version"
            :changelog="updateInfo.changelog"
            :download-url="updateInfo.downloadUrl"
            @close="updateDialogOpen = false"
        />
    </div>
</template>

<style scoped>
.safe-area-top {
    padding-top: var(--inset-top, env(safe-area-inset-top, 0px));
}
</style>
