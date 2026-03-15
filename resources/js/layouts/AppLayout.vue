<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import BottomNav from '@/components/BottomNav.vue';
import UpdateDialog from '@/components/UpdateDialog.vue';
import { clearCredentialsFlag, getToken, getSyncToken, isNativePHP, markCredentialsSaved, saveToken, saveSyncToken } from '@/services/biometric-auth';
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
    flash?: { api_token?: string; sync_token?: string; logged_out?: boolean };
    auth?: { user?: { id: number; name: string; email: string } };
}>();

const updateDialogOpen = ref(false);
const updateInfo = ref<{ version: string; changelog?: string; downloadUrl: string } | null>(null);

onMounted(async () => {
    const syncApiUrl = page.props.syncApiUrl;

    // Nettoyer les credentials au logout
    if (page.props.flash?.logged_out) {
        clearCredentialsFlag();
    }

    // Stocker le token cloud de sync si flashé au login
    const flashedSyncToken = page.props.flash?.sync_token;
    if (flashedSyncToken) {
        saveSyncToken(flashedSyncToken);
    }

    // Configurer le client de sync avec le token cloud persisté
    if (syncApiUrl) {
        const syncToken = flashedSyncToken ?? getSyncToken();
        if (syncToken) {
            configureSyncClient(syncApiUrl, syncToken);
        }
        sync();
    }

    // Stocker le token local dans localStorage (pour update checker + flag biométrie)
    const token = page.props.flash?.api_token;
    if (token) {
        saveToken(token);
        markCredentialsSaved();
    }

    // Vérifier les mises à jour APK
    if (syncApiUrl && isNativePHP()) {
        const storedToken = getToken();
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
            <div class="flex h-14 items-center gap-1 px-2">
                <slot name="header-left" />
                <h1
                    class="flex-1 truncate text-lg font-semibold text-foreground"
                    :class="$slots['header-left'] ? '' : 'pl-2'"
                >
                    {{ title }}
                </h1>
                <slot name="header-right" />
            </div>
        </header>

        <!-- Main content -->
        <main class="min-h-0 flex-1 overflow-y-auto pb-24">
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
