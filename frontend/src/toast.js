/**
 * Tiny toast notification service — no framework, no dependencies.
 *
 * Why not a Vue plugin: the public surface is a single `notify()`
 * function that can be imported anywhere (composables, plain JS,
 * event handlers) without injection ceremony. The ToastContainer
 * component just subscribes to the event stream.
 *
 * Usage:
 *   import { notify } from '@/toast.js';
 *   notify('Saved.', 'success');
 *   notify('Could not save.', 'error');
 *   notify({ text: 'Heads up', kind: 'info', durationMs: 6000 });
 *
 * The container is mounted once at the root of App.vue and listens
 * for `buttercup:toast` window events. Toasts dismiss themselves on
 * a timer, plus a per-item close button.
 */

const EVENT = 'buttercup:toast';

/**
 * @param {string|object} message  Either the toast text, or an
 *   options object: { text, kind, durationMs }.
 * @param {'success'|'error'|'info'|'warn'} [kind] - When the first
 *   argument is a string, the toast kind. Defaults to 'info'.
 */
export function notify(message, kind = 'info') {
    const detail = typeof message === 'string'
        ? { text: message, kind }
        : {
            text:       message?.text       ?? '',
            kind:       message?.kind       ?? 'info',
            durationMs: message?.durationMs,
          };

    if (!detail.text) return;

    // Default duration: errors stick around longer so users can
    // actually read them; success/info dismiss faster to feel snappy.
    if (typeof detail.durationMs !== 'number') {
        detail.durationMs = detail.kind === 'error' ? 6000 : 3500;
    }

    try {
        window.dispatchEvent(new CustomEvent(EVENT, { detail }));
    } catch {
        // SSR / non-browser environments — silently no-op.
    }
}

export const TOAST_EVENT = EVENT;

// Convenience helpers — pure sugar on top of notify().
export const toastSuccess = (text, opts = {}) => notify({ text, kind: 'success', ...opts });
export const toastError   = (text, opts = {}) => notify({ text, kind: 'error',   ...opts });
export const toastInfo    = (text, opts = {}) => notify({ text, kind: 'info',    ...opts });
export const toastWarn    = (text, opts = {}) => notify({ text, kind: 'warn',    ...opts });

export default notify;
