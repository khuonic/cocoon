type UpdateInfo = {
    available: boolean;
    version?: string;
    changelog?: string;
    downloadUrl?: string;
};

export async function checkForUpdate(
    apiUrl: string,
    currentVersionCode: number,
    token: string,
): Promise<UpdateInfo> {
    try {
        const response = await fetch(`${apiUrl}/api/app/version`, {
            headers: {
                Authorization: `Bearer ${token}`,
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            return { available: false };
        }

        const data = await response.json();

        if (data.version_code > currentVersionCode) {
            return {
                available: true,
                version: data.version,
                changelog: data.changelog,
                downloadUrl: data.download_url,
            };
        }

        return { available: false };
    } catch {
        return { available: false };
    }
}
