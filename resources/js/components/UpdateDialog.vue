<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    version: string;
    changelog?: string;
    downloadUrl: string;
}>();

const emit = defineEmits<{
    close: [];
}>();

async function openDownload(): Promise<void> {
    try {
        const { Browser } = await import('#nativephp');
        await Browser.open(props.downloadUrl);
    } catch {
        window.open(props.downloadUrl, '_blank');
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="!$event && emit('close')">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Mise à jour disponible</DialogTitle>
                <DialogDescription>
                    La version {{ version }} est disponible.
                </DialogDescription>
            </DialogHeader>

            <p v-if="changelog" class="text-sm text-muted-foreground">
                {{ changelog }}
            </p>

            <DialogFooter>
                <Button type="button" variant="outline" @click="emit('close')">
                    Plus tard
                </Button>
                <Button type="button" @click="openDownload">
                    Mettre à jour
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
