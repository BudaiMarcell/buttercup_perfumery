<template>
  <!--
    A single skeleton placeholder. Composes with itself: drop several
    of these in a grid to fake a list. Variant controls the silhouette.
  -->
  <div
    class="skeleton"
    :class="[`skeleton-${variant}`]"
    :style="{ width, height }"
    aria-hidden="true"
  ></div>
</template>

<script>
export default {
  name: 'Skeleton',
  props: {
    // 'line'  - thin row, for paragraphs / table cells
    // 'title' - thicker bar, for headings
    // 'card'  - tall block, for product/order cards
    // 'avatar'- square chunk, for icons or images
    variant: { type: String, default: 'line' },
    width:   { type: String, default: '' },
    height:  { type: String, default: '' },
  },
};
</script>

<style scoped>
/* Shimmer animation: a faint highlight sweeps left → right across a
   gentle beige base. Uses a CSS gradient so there's no GPU/JS cost
   beyond a single keyframe. */
.skeleton {
  position: relative;
  overflow: hidden;
  background: #efe7d8;
  border-radius: 4px;
}
.skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(
    90deg,
    transparent 0%,
    rgba(255, 255, 255, 0.45) 50%,
    transparent 100%
  );
  animation: skeleton-shimmer 1.4s infinite;
}

.skeleton-line   { height: 14px; width: 100%; border-radius: 3px; }
.skeleton-title  { height: 26px; width: 60%; border-radius: 4px; }
.skeleton-card   { height: 320px; width: 100%; border-radius: 6px; }
.skeleton-avatar { height: 60px; width: 60px; border-radius: 50%; }

@keyframes skeleton-shimmer {
  to { transform: translateX(100%); }
}

@media (prefers-reduced-motion: reduce) {
  .skeleton::after { animation: none; }
}
</style>
