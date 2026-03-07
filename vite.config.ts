import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { nativephpMobile, nativephpHotFile } from './vendor/nativephp/mobile/resources/js/vite-plugin.js';

const isNative = process.argv.includes('--mode=android') || process.argv.includes('--mode=ios');

const nativephpStub = {
    name: 'nativephp-stub',
    enforce: 'pre' as const,
    resolveId(id: string) {
        if (!isNative && id === '#nativephp') {
            return '\0nativephp-stub';
        }
    },
    load(id: string) {
        if (id === '\0nativephp-stub') {
            // Stub qui throw pour que le try/catch de biometric-auth.ts retourne null (fallback web)
            return 'throw new Error("Not in NativePHP context");';
        }
    },
};

export default defineConfig({
    build: {
        rollupOptions: {
            external: ['#nativephp'],
        },
    },
    plugins: [
        nativephpStub,
        laravel({
            input: ['resources/js/app.ts'],
            ssr: 'resources/js/ssr.ts',
            refresh: true,
            hotFile: nativephpHotFile(),
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
        nativephpMobile(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
