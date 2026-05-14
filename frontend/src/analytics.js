

import { getApiBase, getTrackingKey } from './config.js';

const endpointFor = (path) => `${getApiBase()}${path}`;

const HEARTBEAT_INTERVAL_MS = 30 * 1000;

const SESSION_ID_KEY = 'buttercup_session_id';

function generateSessionId() {
    if (window.crypto?.randomUUID) {
        return window.crypto.randomUUID().replace(/-/g, '');
    }
    return Array.from({ length: 32 }, () =>
        Math.floor(Math.random() * 36).toString(36)
    ).join('');
}

function getSessionId() {
    try {
        const stored = sessionStorage.getItem(SESSION_ID_KEY);
        if (stored) return stored;

        const id = generateSessionId();
        sessionStorage.setItem(SESSION_ID_KEY, id);
        return id;
    } catch {
        return generateSessionId();
    }
}

let warnedAboutMissingKey = false;
function warnMissingKey() {
    if (warnedAboutMissingKey) return;
    warnedAboutMissingKey = true;
    // Surface the most common analytics breakage (empty TRACKING_KEY in the
    // container env) instead of failing silently — the dashboard stays at 0
    // and there is otherwise no visible signal. Logged once per page load.
    console.warn(
        '[analytics] TRACKING_KEY is empty — events will not be sent. ' +
        'Set TRACKING_KEY in the docker .env (or VITE_TRACKING_API_KEY for dev) ' +
        'and rebuild/restart the web container.'
    );
}

async function send(eventType, payload = {}) {
    const trackingKey = getTrackingKey();
    if (!trackingKey) { warnMissingKey(); return; }

    const body = {
        session_id: getSessionId(),
        event_type: eventType,
        page_url:   payload.page_url || window.location.pathname + window.location.search,
        element_selector: payload.element_selector ?? null,
        duration_seconds: payload.duration_seconds ?? null,
        product_id:       payload.product_id ?? null,
        meta:             payload.meta ?? null,
    };

    try {
        await fetch(endpointFor('/analytics/track'), {
            method:  'POST',
            headers: {
                'Content-Type':   'application/json',
                'Accept':         'application/json',
                'X-Tracking-Key': trackingKey,
            },
            body: JSON.stringify(body),
            keepalive: true,
        });
    } catch {
    }
}

export function trackPageview(pageUrl, meta = null, productId = null) {
    return send('pageview', { page_url: pageUrl, meta, product_id: productId });
}

export function trackClick(selector, meta = null, productId = null) {
    return send('click', { element_selector: selector, meta, product_id: productId });
}

export function trackAddToCart(productId, meta = null) {
    return send('add_to_cart', { product_id: productId, meta });
}

export function trackRemoveFromCart(productId, meta = null) {
    return send('remove_from_cart', { product_id: productId, meta });
}

export function trackCheckout(meta = null) {
    return send('checkout', { meta });
}

export function trackTimeSpent(seconds, pageUrl = null) {
    if (!seconds || seconds < 1) return;
    return send('time_spent', {
        duration_seconds: Math.round(seconds),
        page_url: pageUrl || undefined,
    });
}

export function startTimeTracker(pageUrl) {
    let start = Date.now();
    let stopped = false;

    const flush = () => {
        if (stopped) return;
        stopped = true;
        const elapsed = (Date.now() - start) / 1000;
        trackTimeSpent(elapsed, pageUrl);
    };

    const onVisibility = () => {
        if (document.visibilityState === 'hidden') flush();
    };

    document.addEventListener('visibilitychange', onVisibility);
    window.addEventListener('pagehide', flush);

    return () => {
        document.removeEventListener('visibilitychange', onVisibility);
        window.removeEventListener('pagehide', flush);
        flush();
    };
}

async function sendPing() {
    const trackingKey = getTrackingKey();
    if (!trackingKey) { warnMissingKey(); return; }
    try {
        await fetch(endpointFor('/analytics/ping'), {
            method: 'POST',
            headers: {
                'Content-Type':   'application/json',
                'Accept':         'application/json',
                'X-Tracking-Key': trackingKey,
            },
            body: JSON.stringify({ session_id: getSessionId() }),
            keepalive: true,
        });
    } catch {
    }
}

export function startHeartbeat() {
    let timerId = null;

    const start = () => {
        if (timerId !== null) return;
        sendPing();
        timerId = window.setInterval(sendPing, HEARTBEAT_INTERVAL_MS);
    };

    const stop = () => {
        if (timerId !== null) {
            window.clearInterval(timerId);
            timerId = null;
        }
    };

    const onVisibility = () => {
        if (document.visibilityState === 'visible') start();
        else stop();
    };

    document.addEventListener('visibilitychange', onVisibility);
    if (document.visibilityState === 'visible') start();

    return () => {
        document.removeEventListener('visibilitychange', onVisibility);
        stop();
    };
}

export default {
    trackPageview,
    trackClick,
    trackAddToCart,
    trackRemoveFromCart,
    trackCheckout,
    trackTimeSpent,
    startTimeTracker,
    startHeartbeat,
};
