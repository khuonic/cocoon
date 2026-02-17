import { router } from '@inertiajs/vue3';

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
        lastSyncedAt = result.server_time;
        localStorage.setItem(LAST_SYNCED_KEY, result.server_time);

        if (result.changes.length > 0) {
            router.reload();
        }
    }

    return result;
}

export async function fullSync(): Promise<FullResponse | null> {
    const result = await fetchApi<FullResponse>('/api/sync/full', {
        method: 'POST',
        body: JSON.stringify({ changes: [] }),
    });

    if (result) {
        lastSyncedAt = result.server_time;
        localStorage.setItem(LAST_SYNCED_KEY, result.server_time);

        if (result.changes.length > 0) {
            router.reload();
        }
    }

    return result;
}

export async function sync(): Promise<void> {
    if (isSyncing || !isSyncEnabled()) return;

    isSyncing = true;

    try {
        if (!lastSyncedAt) {
            await fullSync();
        } else {
            await pull();
        }
    } finally {
        isSyncing = false;
    }
}
