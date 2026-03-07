type SavedUser = {
    id: number;
    name: string;
    email: string;
};

const TOKEN_KEY = 'cocoon_auth_token';
const USER_KEY = 'cocoon_auth_user';
const SYNC_TOKEN_KEY = 'cocoon_sync_token';

const BIOMETRIC_EVENT = 'Native\\Mobile\\Events\\Biometrics\\BiometricCompleted';

function getNative(): { on: (e: string, cb: (p: unknown) => void) => void; off: (e: string, cb: (p: unknown) => void) => void } | null {
    if (typeof window !== 'undefined' && (window as Record<string, unknown>).Native) {
        return (window as Record<string, unknown>).Native as ReturnType<typeof getNative>;
    }
    return null;
}

export async function isNativePHP(): Promise<boolean> {
    const native = getNative();
    console.log('[BiometricAuth] isNativePHP:', native !== null, 'window.Native:', (window as Record<string, unknown>).Native);
    return native !== null;
}

async function nativeCall(method: string, params: Record<string, unknown> = {}): Promise<Record<string, unknown>> {
    console.log('[BiometricAuth] nativeCall:', method, params);
    const response = await fetch('/_native/api/call', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ method, params }),
    });

    if (!response.ok) {
        throw new Error(`Native call failed: ${response.status}`);
    }

    const json = await response.json() as Record<string, unknown>;
    console.log('[BiometricAuth] nativeCall result:', method, json);
    return json;
}

export async function hasSavedCredentials(): Promise<boolean> {
    try {
        const result = await nativeCall('SecureStorage.Get', { key: TOKEN_KEY });
        const data = result.data as Record<string, unknown> | undefined;
        return !!data?.value;
    } catch {
        return false;
    }
}

export async function saveCredentials(token: string, user: SavedUser): Promise<void> {
    try {
        await nativeCall('SecureStorage.Set', { key: TOKEN_KEY, value: token });
        await nativeCall('SecureStorage.Set', { key: USER_KEY, value: JSON.stringify(user) });
    } catch {
        console.warn('[BiometricAuth] Failed to save credentials');
    }
}

export async function authenticate(): Promise<{ token: string; user: SavedUser } | null> {
    const native = getNative();
    if (!native) return null;

    return new Promise((resolve) => {
        const handler = async (payload: unknown) => {
            console.log('[BiometricAuth] Biometric event received:', payload);
            native.off(BIOMETRIC_EVENT, handler);

            const p = payload as { success: boolean };
            if (!p.success) {
                resolve(null);
                return;
            }

            try {
                const tokenResult = await nativeCall('SecureStorage.Get', { key: TOKEN_KEY });
                const userResult = await nativeCall('SecureStorage.Get', { key: USER_KEY });

                const token = (tokenResult.data as Record<string, unknown>)?.value as string | undefined;
                const userStr = (userResult.data as Record<string, unknown>)?.value as string | undefined;

                if (!token || !userStr) {
                    resolve(null);
                    return;
                }

                resolve({ token, user: JSON.parse(userStr) as SavedUser });
            } catch {
                resolve(null);
            }
        };

        console.log('[BiometricAuth] Starting biometric prompt, listening for:', BIOMETRIC_EVENT);
        native.on(BIOMETRIC_EVENT, handler);

        nativeCall('Biometric.Prompt', {}).catch((e) => {
            console.error('[BiometricAuth] Biometric.Prompt call failed:', e);
            native.off(BIOMETRIC_EVENT, handler);
            resolve(null);
        });
    });
}

export async function getToken(): Promise<string | null> {
    try {
        const result = await nativeCall('SecureStorage.Get', { key: TOKEN_KEY });
        const data = result.data as Record<string, unknown> | undefined;
        return (data?.value as string) ?? null;
    } catch {
        return null;
    }
}

export async function saveSyncToken(token: string): Promise<void> {
    try {
        await nativeCall('SecureStorage.Set', { key: SYNC_TOKEN_KEY, value: token });
    } catch {
        console.warn('[BiometricAuth] Failed to save sync token');
    }
}

export async function getSyncToken(): Promise<string | null> {
    try {
        const result = await nativeCall('SecureStorage.Get', { key: SYNC_TOKEN_KEY });
        const data = result.data as Record<string, unknown> | undefined;
        return (data?.value as string) ?? null;
    } catch {
        return null;
    }
}

export async function clearCredentials(): Promise<void> {
    try {
        await nativeCall('SecureStorage.Delete', { key: TOKEN_KEY });
        await nativeCall('SecureStorage.Delete', { key: USER_KEY });
    } catch {
        console.warn('[BiometricAuth] Failed to clear credentials');
    }
}
