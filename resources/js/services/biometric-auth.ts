type SavedUser = {
    id: number;
    name: string;
    email: string;
};

const TOKEN_KEY = 'cocoon_auth_token';
const USER_KEY = 'cocoon_auth_user';

let nativephpModule: typeof import('#nativephp') | null = null;

async function getNativePHP(): Promise<typeof import('#nativephp') | null> {
    if (nativephpModule) return nativephpModule;

    try {
        nativephpModule = await import('#nativephp');
        return nativephpModule;
    } catch {
        return null;
    }
}

export async function isNativePHP(): Promise<boolean> {
    return (await getNativePHP()) !== null;
}

export async function hasSavedCredentials(): Promise<boolean> {
    const native = await getNativePHP();
    if (!native) return false;

    try {
        const result = await native.SecureStorage.get(TOKEN_KEY);
        return !!result.value;
    } catch {
        return false;
    }
}

export async function saveCredentials(
    token: string,
    user: SavedUser,
): Promise<void> {
    const native = await getNativePHP();
    if (!native) return;

    try {
        await native.SecureStorage.set(TOKEN_KEY, token);
        await native.SecureStorage.set(USER_KEY, JSON.stringify(user));
    } catch {
        console.warn('[BiometricAuth] Failed to save credentials');
    }
}

export async function authenticate(): Promise<{
    token: string;
    user: SavedUser;
} | null> {
    const native = await getNativePHP();
    if (!native) return null;

    return new Promise((resolve) => {
        const handler = async (payload: { success: boolean }) => {
            native.Off(native.Events.Biometric.Completed, handler);

            if (!payload.success) {
                resolve(null);
                return;
            }

            try {
                const tokenResult = await native.SecureStorage.get(TOKEN_KEY);
                const userResult = await native.SecureStorage.get(USER_KEY);

                if (!tokenResult.value || !userResult.value) {
                    resolve(null);
                    return;
                }

                resolve({
                    token: tokenResult.value,
                    user: JSON.parse(userResult.value) as SavedUser,
                });
            } catch {
                resolve(null);
            }
        };

        native.On(native.Events.Biometric.Completed, handler);
        native.Biometric.prompt();
    });
}

export async function clearCredentials(): Promise<void> {
    const native = await getNativePHP();
    if (!native) return;

    try {
        await native.SecureStorage.delete(TOKEN_KEY);
        await native.SecureStorage.delete(USER_KEY);
    } catch {
        console.warn('[BiometricAuth] Failed to clear credentials');
    }
}
