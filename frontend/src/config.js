

export function getApiBase() {
    if (typeof window !== 'undefined' && window.__CONFIG__?.API_URL) {
        return window.__CONFIG__.API_URL;
    }
    if (import.meta.env?.VITE_API_URL) {
        return import.meta.env.VITE_API_URL;
    }
    return 'http://localhost:8000/api';
}

export function getSentryDsn() {
    if (typeof window !== 'undefined' && window.__CONFIG__?.SENTRY_DSN) {
        return window.__CONFIG__.SENTRY_DSN;
    }
    return import.meta.env?.VITE_SENTRY_DSN ?? '';
}

export function getTrackingKey() {
    if (typeof window !== 'undefined' && window.__CONFIG__?.TRACKING_KEY) {
        return window.__CONFIG__.TRACKING_KEY;
    }
    return import.meta.env?.VITE_TRACKING_API_KEY ?? '';
}
