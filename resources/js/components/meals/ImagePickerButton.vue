<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import { Camera, X, ImagePlus } from 'lucide-vue-next';

const emit = defineEmits<{
    change: [file: File];
    remove: [];
}>();

defineProps<{
    preview: string | null;
    label?: string;
}>();

// ── Detect NativePHP context ──────────────────────────────────────────────────

function isNativePHP(): boolean {
    return typeof window !== 'undefined' && !!window.Native;
}

// ── NativePHP camera API (dynamic import to avoid web build errors) ───────────

let nativeCameraApi: {
    Camera: { getPhoto: () => { id: (s: string) => unknown }; pickImages: () => { images: () => unknown } };
    On: (event: string, cb: (payload: unknown) => void) => void;
    Off: (event: string, cb: (payload: unknown) => void) => void;
    Events: { Camera: { PhotoTaken: string; PhotoCancelled: string }; Gallery: { MediaSelected: string } };
} | null = null;

async function getNativeApi() {
    if (nativeCameraApi) return nativeCameraApi;
    try {
        nativeCameraApi = await import(/* @vite-ignore */ '#nativephp') as typeof nativeCameraApi;
    } catch {
        nativeCameraApi = null;
    }
    return nativeCameraApi;
}

// ── Upload native path to Laravel storage ────────────────────────────────────

async function uploadNativePath(nativePath: string): Promise<{ storedPath: string; url: string } | null> {
    const csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
    const response = await fetch('/native-image', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ native_path: nativePath }),
    });
    if (!response.ok) return null;
    const data = await response.json();
    return { storedPath: data.stored_path, url: data.url };
}

async function storedUrlToFile(url: string): Promise<File | null> {
    try {
        const response = await fetch(url);
        const blob = await response.blob();
        const filename = url.split('/').pop() ?? 'photo.jpg';
        return new File([blob], filename, { type: blob.type || 'image/jpeg' });
    } catch {
        return null;
    }
}

// ── Native photo capture ──────────────────────────────────────────────────────

const isLoading = ref(false);

async function handleNativePhoto(): Promise<void> {
    const api = await getNativeApi();
    if (!api) return;

    isLoading.value = true;

    return new Promise((resolve) => {
        const onTaken = async (payload: unknown) => {
            api.Off(api.Events.Camera.PhotoTaken, onTaken);
            api.Off(api.Events.Camera.PhotoCancelled, onCancelled);

            const path = (payload as { path: string }).path;
            if (path) {
                const uploaded = await uploadNativePath(path);
                if (uploaded) {
                    const file = await storedUrlToFile(uploaded.url);
                    if (file) emit('change', file);
                }
            }

            isLoading.value = false;
            resolve();
        };

        const onCancelled = () => {
            api.Off(api.Events.Camera.PhotoTaken, onTaken);
            api.Off(api.Events.Camera.PhotoCancelled, onCancelled);
            isLoading.value = false;
            resolve();
        };

        api.On(api.Events.Camera.PhotoTaken, onTaken);
        api.On(api.Events.Camera.PhotoCancelled, onCancelled);

        (api.Camera.getPhoto() as { id: (s: string) => unknown }).id('recipe-photo');
    });
}

async function handleNativeGallery(): Promise<void> {
    const api = await getNativeApi();
    if (!api) return;

    isLoading.value = true;

    return new Promise((resolve) => {
        const onSelected = async (payload: unknown) => {
            api.Off(api.Events.Gallery.MediaSelected, onSelected);

            const { files } = payload as { success: boolean; files: string[]; count: number };
            if (files?.length) {
                const uploaded = await uploadNativePath(files[0]);
                if (uploaded) {
                    const file = await storedUrlToFile(uploaded.url);
                    if (file) emit('change', file);
                }
            }

            isLoading.value = false;
            resolve();
        };

        api.On(api.Events.Gallery.MediaSelected, onSelected);

        (api.Camera.pickImages() as { images: () => unknown }).images();
    });
}

// ── Web file input fallback ───────────────────────────────────────────────────

function handleFileInput(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    emit('change', file);
}

// ── Bottom sheet (NativePHP only) ─────────────────────────────────────────────

const showSheet = ref(false);
</script>

<template>
    <!-- Preview -->
    <div v-if="preview" class="relative">
        <img :src="preview" class="h-48 w-full rounded-xl object-cover" alt="" />
        <button
            type="button"
            class="absolute right-2 top-2 flex h-7 w-7 items-center justify-center rounded-full bg-black/50 text-white"
            @click="$emit('remove')"
        >
            <X :size="14" />
        </button>
    </div>

    <!-- Picker — NativePHP -->
    <template v-else-if="isNativePHP()">
        <button
            type="button"
            class="flex h-32 w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/30 bg-muted/50"
            :disabled="isLoading"
            @click="showSheet = true"
        >
            <div class="text-center">
                <Camera v-if="!isLoading" :size="24" class="mx-auto mb-1 text-muted-foreground" />
                <div v-else class="mx-auto mb-1 h-6 w-6 animate-spin rounded-full border-2 border-muted-foreground border-t-transparent" />
                <span class="text-sm text-muted-foreground">{{ label ?? 'Ajouter une photo' }}</span>
            </div>
        </button>

        <!-- Bottom sheet choice -->
        <Teleport to="body">
            <div
                v-if="showSheet"
                class="fixed inset-0 z-50 flex items-end"
                @click.self="showSheet = false"
            >
                <div class="absolute inset-0 bg-black/40" @click="showSheet = false" />
                <div class="relative w-full rounded-t-2xl bg-background p-4 pb-8 shadow-xl">
                    <p class="mb-3 text-center text-sm font-semibold text-muted-foreground">Ajouter une photo</p>
                    <button
                        type="button"
                        class="mb-2 flex w-full items-center gap-3 rounded-xl bg-muted/60 px-4 py-3 text-left text-sm font-medium"
                        @click="showSheet = false; handleNativePhoto()"
                    >
                        <Camera :size="18" />
                        Prendre une photo
                    </button>
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-xl bg-muted/60 px-4 py-3 text-left text-sm font-medium"
                        @click="showSheet = false; handleNativeGallery()"
                    >
                        <ImagePlus :size="18" />
                        Choisir dans la galerie
                    </button>
                </div>
            </div>
        </Teleport>
    </template>

    <!-- Picker — Web fallback -->
    <label
        v-else
        class="flex h-32 cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-muted-foreground/30 bg-muted/50"
    >
        <input type="file" accept="image/*" class="hidden" @change="handleFileInput" />
        <div class="text-center">
            <Camera :size="24" class="mx-auto mb-1 text-muted-foreground" />
            <span class="text-sm text-muted-foreground">{{ label ?? 'Ajouter une photo' }}</span>
        </div>
    </label>
</template>