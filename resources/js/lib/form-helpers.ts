import { router } from '@inertiajs/vue3';
import type { InertiaForm } from '@inertiajs/vue3';
import type { VisitOptions } from '@inertiajs/core';

/**
 * Workaround for NativePHP Android webview where PUT/PATCH requests
 * don't transmit form data correctly. Uses POST with _method spoofing.
 */
export function mobilePut<T extends Record<string, unknown>>(
    form: InertiaForm<T>,
    url: string,
    options: Partial<VisitOptions> = {},
): void {
    router.post(url, { _method: 'put', ...form.data() }, {
        ...options,
        onError: (errors) => {
            form.clearErrors();
            Object.entries(errors).forEach(([key, value]) => form.setError(key as keyof T, value as string));
            options.onError?.(errors);
        },
    });
}

export function mobilePatch(url: string, data: Record<string, unknown> = {}, options: Partial<VisitOptions> = {}): void {
    router.post(url, { _method: 'patch', ...data }, options);
}
