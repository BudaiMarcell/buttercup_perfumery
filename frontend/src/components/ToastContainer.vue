<template>
  <!--
    Lives at the root of App.vue. Subscribes to window-level toast
    events (see ../toast.js) so any module — Vue or otherwise — can
    queue a notification without a Vue plugin.

    No <style scoped> in this file ON PURPOSE: the styles live in
    style.css under a `.toast-*` namespace. Scoped styles + the
    transition-group's dynamic class injection had an edge case where
    the toasts rendered to the DOM but without their positioning
    rules — they piled up at the bottom of <body>, off-screen. Going
    global removes the whole class of bug.
  -->
  <!--
    Classes are deliberately namespaced `bcup-toast-*` because plain
    `.toast` collides with Bootstrap 5's own toast component, which
    applies `display: none` to any `.toast` element that doesn't also
    have `.show` — that was the invisible-toast bug. Don't rename these
    back to `.toast`.
  -->
  <div class="bcup-toast-stack" aria-live="polite">
    <transition-group name="bcup-toast" tag="div" class="bcup-toast-stack-inner">
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="bcup-toast"
        :class="`bcup-toast-${toast.kind}`"
        @click="dismiss(toast.id)"
        role="status"
      >
        <span class="bcup-toast-icon" aria-hidden="true">{{ iconFor(toast.kind) }}</span>
        <span class="bcup-toast-text">{{ toast.text }}</span>
        <button
          class="bcup-toast-close"
          @click.stop="dismiss(toast.id)"
          aria-label="Dismiss"
        >×</button>
      </div>
    </transition-group>
  </div>
</template>

<script>
import { TOAST_EVENT } from '../toast.js';

const ICONS = {
  success: '✓',
  error:   '✕',
  warn:    '⚠',
  info:    'ℹ',
};

export default {
  name: 'ToastContainer',
  data() {
    return {
      toasts: [],
      // Monotonic id counter so re-creating a toast with the same
      // text+kind still animates as a new entry.
      nextId: 1,
    };
  },
  created() {
    this._onToast = (event) => {
      const { text, kind = 'info', durationMs = 3500 } = event.detail || {};
      if (!text) return;
      this.push({ text, kind, durationMs });
    };
    window.addEventListener(TOAST_EVENT, this._onToast);
  },
  beforeUnmount() {
    window.removeEventListener(TOAST_EVENT, this._onToast);
    // Clear any pending dismissal timers so nothing fires after
    // unmount and tries to splice into a non-existent array.
    for (const t of this.toasts) clearTimeout(t._timer);
  },
  methods: {
    iconFor(kind) {
      return ICONS[kind] ?? ICONS.info;
    },
    push({ text, kind, durationMs }) {
      const id = this.nextId++;
      const toast = { id, text, kind, _timer: null };
      this.toasts.push(toast);

      // Hard cap so a runaway loop doesn't fill the screen with toasts.
      if (this.toasts.length > 5) {
        const evicted = this.toasts.shift();
        if (evicted?._timer) clearTimeout(evicted._timer);
      }

      toast._timer = setTimeout(() => this.dismiss(id), durationMs);
    },
    dismiss(id) {
      const idx = this.toasts.findIndex((t) => t.id === id);
      if (idx === -1) return;
      const [removed] = this.toasts.splice(idx, 1);
      if (removed?._timer) clearTimeout(removed._timer);
    },
  },
};
</script>

<!-- styles intentionally NOT scoped — see template comment -->
