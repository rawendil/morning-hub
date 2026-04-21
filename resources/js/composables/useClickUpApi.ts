function getCsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

function baseHeaders(): HeadersInit {
    return {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrfToken(),
    };
}

export function useClickUpApi() {
    async function fetchJson<T>(url: string): Promise<T> {
        const response = await fetch(url, {
            headers: baseHeaders(),
            credentials: 'same-origin',
        });
        if (!response.ok) throw new Error(`Request failed: ${response.status}`);
        const json = await response.json();
        return json.data;
    }

    async function postJson<T>(
        url: string,
        body: Record<string, unknown>,
    ): Promise<T> {
        const response = await fetch(url, {
            method: 'POST',
            headers: { ...baseHeaders(), 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(
                error.message || `Request failed: ${response.status}`,
            );
        }
        const json = await response.json();
        return json.data;
    }

    async function putJson<T>(
        url: string,
        body: Record<string, unknown>,
    ): Promise<T> {
        const response = await fetch(url, {
            method: 'PUT',
            headers: { ...baseHeaders(), 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        });
        if (!response.ok) {
            const error = await response.json().catch(() => ({}));
            throw new Error(
                error.message || `Request failed: ${response.status}`,
            );
        }
        const json = await response.json();
        return json.data;
    }

    return { fetchJson, postJson, putJson };
}
