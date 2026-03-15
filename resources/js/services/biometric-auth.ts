const TOKEN_KEY = 'cocoon_auth_token';
const SYNC_TOKEN_KEY = 'cocoon_sync_token';
const HAS_CREDENTIALS_KEY = 'cocoon_has_credentials';

const BIOMETRIC_EVENT = 'Native\\Mobile\\Events\\Biometrics\\BiometricCompleted';

type NativeBridge = {
    on: (event: string, cb: (payload: unknown) => void) => void;
    off: (event: string, cb: (payload: unknown) => void) => void;
};

function getNative(): NativeBridge | null {
    if (typeof window !== 'undefined' && (window as Record<string, unknown>).Native) {
        return (window as Record<string, unknown>).Native as NativeBridge;
    }
    return null;
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

    const json = await response.json() as Record<string, unknown>;
    console.log('[BiometricAuth] nativeCall result:', method, json);

    if (!response.ok) {
        throw new Error(`Native call failed: ${response.status} — ${JSON.stringify(json)}`);
    }

    return json;
}

export function isNativePHP(): boolean {
    const native = getNative();
    console.log('[BiometricAuth] isNativePHP:', native !== null, 'window.Native:', (window as Record<string, unknown>).Native);
    return native !== null;
}

// ─── Credentials flag (localStorage) ────────────────────────────────────────

export function hasSavedCredentials(): boolean {
    return localStorage.getItem(HAS_CREDENTIALS_KEY) === '1';
}

export function markCredentialsSaved(): void {
    localStorage.setItem(HAS_CREDENTIALS_KEY, '1');
}

export function clearCredentialsFlag(): void {
    localStorage.removeItem(HAS_CREDENTIALS_KEY);
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(SYNC_TOKEN_KEY);
}

// ─── App token (localStorage — pour update checker) ─────────────────────────

export function saveToken(token: string): void {
    localStorage.setItem(TOKEN_KEY, token);
}

export function getToken(): string | null {
    return localStorage.getItem(TOKEN_KEY);
}

// ─── Sync token (localStorage) ───────────────────────────────────────────────

export function saveSyncToken(token: string): void {
    localStorage.setItem(SYNC_TOKEN_KEY, token);
}

export function getSyncToken(): string | null {
    return localStorage.getItem(SYNC_TOKEN_KEY);
}

// ─── Biometric prompt ────────────────────────────────────────────────────────

export async function authenticate(): Promise<boolean> {
    const native = getNative();
    if (!native) {
        console.warn('[BiometricAuth] window.Native non disponible');
        return false;
    }

    return new Promise((resolve) => {
        const handler = (payload: unknown) => {
            console.log('[BiometricAuth] Biometric event received:', payload);
            native.off(BIOMETRIC_EVENT, handler);
            resolve((payload as { success: boolean }).success === true);
        };

        console.log('[BiometricAuth] Starting biometric prompt, listening for:', BIOMETRIC_EVENT);
        native.on(BIOMETRIC_EVENT, handler);

        nativeCall('Biometric.Prompt', {}).catch((e) => {
            console.error('[BiometricAuth] Biometric.Prompt call failed:', e);
            native.off(BIOMETRIC_EVENT, handler);
            resolve(false);
        });
    });
}