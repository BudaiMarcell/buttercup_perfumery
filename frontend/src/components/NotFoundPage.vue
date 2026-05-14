<template>
  <!--
    Branded 404. Reached either by the router's catch-all redirect
    (currently rewrites bad URLs to '/') or — more commonly — by
    customers landing on a stale link from elsewhere.
  -->
  <div class="not-found-page">
    <div class="container-xl">
      <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6 text-center py-5">
          <span class="nf-icon">✦</span>
          <p class="section-eyebrow mt-3">— 404</p>
          <h1 class="nf-title">This page drifted away.</h1>
          <p class="nf-body">
            The fragrance you were looking for isn't here. It may have been
            renamed, retired, or — most likely — the link was mistyped.
          </p>

          <div class="nf-actions d-flex gap-3 justify-content-center flex-wrap">
            <button class="cta-primary" @click="$router.push({ name: 'main' })">
              Back to home
            </button>
            <button class="cta-secondary" @click="$router.push({ name: 'shop' })">
              Browse the shop
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFoundPage',
};
</script>

<style scoped>
.not-found-page {
  min-height: 60vh;
  display: flex;
  align-items: center;
  background: var(--cream, #faf5ec);
}
.nf-icon {
  font-size: 56px;
  color: var(--dusty-rose, #b9676b);
  display: inline-block;
}
.nf-title {
  font-family: var(--font-serif, 'Cormorant Garamond', Georgia, serif);
  font-weight: 300;
  font-size: clamp(32px, 5vw, 52px);
  color: var(--text-dark, #2a2518);
  margin: 12px 0 16px 0;
}
.nf-body {
  color: var(--text-light, #6b5d48);
  font-size: 16px;
  line-height: 1.6;
  margin-bottom: 32px;
}
.nf-actions {
  margin-top: 12px;
}
.cta-secondary {
  background: transparent;
  color: var(--text-dark);
  border: 1px solid rgba(156, 159, 136, 0.4);
  padding: 12px 24px;
  border-radius: 4px;
  font-family: var(--font-sans, 'Jost', sans-serif);
  font-size: 13px;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}
.cta-secondary:hover {
  border-color: var(--dusty-rose);
  color: var(--dusty-rose);
}
</style>
