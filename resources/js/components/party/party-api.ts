/**
 * Appels JSON du mini-site (cookies + XSRF), pour les réponses de jeu sans rechargement.
 */
export class PartyApiError extends Error {}

function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function partyPost<T>(
    url: string,
    body: Record<string, unknown> = {},
): Promise<T> {
    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: JSON.stringify(body),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new PartyApiError(
            data?.message ?? 'Oups, quelque chose a coincé. Réessaie.',
        );
    }

    return data as T;
}

export function formatDuration(
    milliseconds: number | null | undefined,
): string {
    if (milliseconds === null || milliseconds === undefined) {
        return '–';
    }

    const totalSeconds = Math.floor(milliseconds / 1000);
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;

    return `${minutes}:${String(seconds).padStart(2, '0')}`;
}
