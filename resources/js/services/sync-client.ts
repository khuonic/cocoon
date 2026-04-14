import { router } from '@inertiajs/vue3';
import { getToken } from './biometric-auth';

type SyncChange = {
    type: string;
    uuid: string;
    action: 'created' | 'updated' | 'deleted';
    data: Record<string, unknown> | null;
    updated_at: string;
};

type PushResponse = {
    applied: number;
    rejected: number;
    server_time: string;
};

type PullResponse = {
    changes: SyncChange[];
    server_time: string;
};

type FullResponse = PushResponse & {
    changes: SyncChange[];
};

type PendingResponse = {
    changes: SyncChange[];
    ids: number[];
};

let syncApiUrl = '';
let authToken = '';
let lastSyncedAt: string | null = null;
let isSyncing = false;

const LAST_SYNCED_KEY = 'cocoon_last_synced_at';

export function configureSyncClient(apiUrl: string, token?: string): void {
    syncApiUrl = apiUrl.replace(/\/$/, '');
    authToken = token ?? '';
    lastSyncedAt = localStorage.getItem(LAST_SYNCED_KEY);
}

export function setSyncToken(token: string): void {
    authToken = token;
}

export function isSyncEnabled(): boolean {
    return syncApiUrl !== '' && authToken !== '';
}

async function fetchApi<T>(
    path: string,
    options: RequestInit = {},
): Promise<T | null> {
    if (!isSyncEnabled()) return null;

    try {
        const response = await fetch(`${syncApiUrl}${path}`, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${authToken}`,
                'Cache-Control': 'no-cache',
                ...(options.headers ?? {}),
            },
        });

        if (!response.ok) {
            console.warn(`[Sync] ${path} failed:`, response.status);
            return null;
        }

        return (await response.json()) as T;
    } catch (error) {
        console.warn('[Sync] Network error:', error);
        return null;
    }
}

async function fetchLocal<T>(
    path: string,
    options: RequestInit = {},
): Promise<T | null> {
    const token = getToken();
    if (!token) return null;

    try {
        const response = await fetch(path, {
            ...options,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                Authorization: `Bearer ${token}`,
                ...(options.headers ?? {}),
            },
        });

        if (!response.ok) {
            console.warn(`[Sync] Local ${path} failed:`, response.status);
            return null;
        }

        return (await response.json()) as T;
    } catch (error) {
        console.warn('[Sync] Local network error:', error);
        return null;
    }
}

export async function push(changes: SyncChange[]): Promise<PushResponse | null> {
    if (changes.length === 0) return null;

    return fetchApi<PushResponse>('/api/sync/push', {
        method: 'POST',
        body: JSON.stringify({ changes }),
    });
}

export async function pull(): Promise<PullResponse | null> {
    if (!lastSyncedAt) return null;

    const result = await fetchApi<PullResponse>(
        `/api/sync/pull?since=${encodeURIComponent(lastSyncedAt)}`,
    );

    if (result) {
        if (result.changes.length > 0) {
            const applied = await applyChangesLocally(result.changes);
            if (applied) {
                lastSyncedAt = result.server_time;
                localStorage.setItem(LAST_SYNCED_KEY, result.server_time);
                router.reload();
            }
            // Si le push local échoue, lastSyncedAt reste inchangé → retry au prochain sync
        } else {
            lastSyncedAt = result.server_time;
            localStorage.setItem(LAST_SYNCED_KEY, result.server_time);
        }
    }

    return result;
}

export async function fullSync(): Promise<FullResponse | null> {
    const pending = await fetchLocal<PendingResponse>('/api/sync/pending');
    const pendingChanges = pending?.changes ?? [];
    const pendingIds = pending?.ids ?? [];

    const result = await fetchApi<FullResponse>('/api/sync/full', {
        method: 'POST',
        body: JSON.stringify({ changes: pendingChanges }),
    });

    if (result) {
        if (pendingIds.length > 0) {
            await fetchLocal('/api/sync/acknowledge', {
                method: 'POST',
                body: JSON.stringify({ ids: pendingIds }),
            });
        }

        if (result.changes.length > 0) {
            const applied = await applyChangesLocally(result.changes);
            if (applied) {
                lastSyncedAt = result.server_time;
                localStorage.setItem(LAST_SYNCED_KEY, result.server_time);
                router.reload();
            }
            // Si le push local échoue, lastSyncedAt reste null → fullSync retenté au prochain lancement
        } else {
            lastSyncedAt = result.server_time;
            localStorage.setItem(LAST_SYNCED_KEY, result.server_time);
        }
    }

    return result;
}

async function applyChangesLocally(changes: SyncChange[]): Promise<boolean> {
    const BATCH_SIZE = 100;
    for (let i = 0; i < changes.length; i += BATCH_SIZE) {
        const batch = changes.slice(i, i + BATCH_SIZE);
        const result = await fetchLocal('/api/sync/push', {
            method: 'POST',
            body: JSON.stringify({ changes: batch }),
        });
        if (result === null) {
            return false;
        }
    }
    return true;
}

async function pushLocalChanges(): Promise<void> {
    if (!isSyncEnabled()) return;

    const pending = await fetchLocal<PendingResponse>('/api/sync/pending');
    if (!pending || pending.changes.length === 0) return;

    const result = await push(pending.changes);
    if (result !== null) {
        await fetchLocal('/api/sync/acknowledge', {
            method: 'POST',
            body: JSON.stringify({ ids: pending.ids }),
        });
    }
}

export async function sync(): Promise<void> {
    if (isSyncing || !isSyncEnabled()) return;

    isSyncing = true;

    try {
        if (!lastSyncedAt) {
            await fullSync();
        } else {
            await pushLocalChanges();
            await pull();
        }
    } finally {
        isSyncing = false;
    }
}