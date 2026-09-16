let csrfRequest;

async function ensureCsrfCookie() {
    if (! csrfRequest) {
        csrfRequest = fetch('/sanctum/csrf-cookie', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        }).finally(() => {
            csrfRequest = null;
        });
    }

    await csrfRequest;
}

function csrfToken() {
    const cookie = document.cookie
        .split('; ')
        .find((value) => value.startsWith('XSRF-TOKEN='));

    return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : null;
}

export async function fetchJson(url, options = {}) {
    const method = (options.method ?? 'GET').toUpperCase();
    const headers = new Headers({
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(options.headers ?? {}),
    });

    if (! ['GET', 'HEAD', 'OPTIONS'].includes(method)) {
        await ensureCsrfCookie();

        if (csrfToken()) {
            headers.set('X-XSRF-TOKEN', csrfToken());
        }
    }

    const response = await fetch(url, {
        ...options,
        method,
        headers,
        credentials: 'same-origin',
    });
    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        const error = new Error(payload.message ?? `Request failed with status ${response.status}.`);
        error.status = response.status;
        error.errors = payload.errors ?? {};
        throw error;
    }

    return payload;
}

export function formatDate(value, locale = 'de') {
    return value
        ? new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(new Date(`${value}T00:00:00`))
        : '–';
}

export function formatSize(bytes, locale = 'de') {
    if (! bytes) {
        return '–';
    }

    if (bytes < 1_000) {
        return `${new Intl.NumberFormat(locale).format(bytes)} B`;
    }

    const unit = bytes >= 1_000_000 ? 'megabyte' : 'kilobyte';
    const divisor = bytes >= 1_000_000 ? 1_000_000 : 1_000;

    return new Intl.NumberFormat(locale, {
        style: 'unit',
        unit,
        unitDisplay: 'short',
        maximumFractionDigits: 1,
    }).format(bytes / divisor);
}
