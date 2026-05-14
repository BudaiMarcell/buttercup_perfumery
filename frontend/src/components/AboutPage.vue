<template>
  <div class="about-page">
    <section class="about-hero">
      <div class="container-xl">
        <div class="row align-items-center min-vh-75 py-5">
          <div class="col-lg-7">
            <p class="section-eyebrow">— Our Story</p>
            <h1 class="about-title">
              <span class="d-block">Made with</span>
              <span class="d-block accent">intention.</span>
            </h1>
            <p class="about-lead">
              Natural, vegan, cruelty free, and sustainable. <br />
              Handmade with intention and a minimal footprint on the planet.
              Enjoy the wellness of <span class="accent">natural power</span>.
            </p>
          </div>
          <div class="col-lg-5 d-flex justify-content-center mt-5 mt-lg-0">
            <div class="about-hero-visual">
              <div class="about-blob"></div>
              <div class="about-icon-cluster">
                <span class="cluster-icon ci-1">🌿</span>
                <span class="cluster-icon ci-2">✦</span>
                <span class="cluster-icon ci-3">🌸</span>
                <span class="cluster-icon ci-4">🌲</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-values py-5">
      <div class="container-xl">
        <div class="text-center mb-5">
          <p class="section-eyebrow">— What we stand for</p>
          <h2 class="section-title">Our Values</h2>
        </div>
        <div class="row g-4">
          <div
            class="col-12 col-md-6 col-lg-3"
            v-for="v in values"
            :key="v.title"
          >
            <div class="value-card text-center h-100">
              <span class="value-icon d-block mb-3">{{ v.icon }}</span>
              <h4 class="value-title">{{ v.title }}</h4>
              <p class="value-desc">{{ v.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="about-story" id="story">
      <div class="container-xl">
        <div class="row g-5 align-items-center">
          <div class="col-lg-6">
            <div class="story-img-placeholder">
              <span>Your image here</span>
            </div>
          </div>
          <div class="col-lg-6 story-text">
            <p class="section-eyebrow">— The Beginning</p>
            <h2 class="section-title">Where it all started</h2>
            <p>
              Boglárka Varga, CEO of Buttercup Perfumery, began fantasising
              about creating her dream products in 2023 — and now they can be
              all yours too. Since then we have come a long way: in 2026 we were
              awarded <em>Small Company of the Year</em>
              and have been noticed by numerous supporters across Europe.
            </p>
            <p>
              We plan to expand this year by opening three new shops where you
              can participate in workshops and experience our all-year and
              seasonal scents in person. You can currently find us in Budapest
              and Milan, and we ship worldwide.
            </p>
            <p>
              We chose to remain on the handmade and small-batch side of
              business — which means our carbon footprint is the smallest
              compared to competitors worldwide. Every product, every bottle,
              every decision is made with purpose.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="about-sustainability" id="sustainability">
      <div class="container-xl">
        <div class="row g-5 align-items-center">
          <div class="col-lg-6 order-lg-2">
            <div class="story-img-placeholder">
              <span>Your image here</span>
            </div>
          </div>
          <div class="col-lg-6 order-lg-1 story-text">
            <p class="section-eyebrow">— Our Products</p>
            <h2 class="section-title">More than a scent</h2>
            <p>
              Perfumes tend to be drying for the skin — but not ours. Our
              fragrances are made to nourish the skin simultaneously, so you
              achieve an irresistible scent and genuine care in one. The same
              philosophy applies to our body care range, with perfume-free
              versions available for those sensitive to scents.
            </p>
            <p>
              All of our nail care and hand care products come in both scented
              and unscented versions. Our silicone and sulfate free haircare
              line lets your hair reach its full potential and forget damage —
              while keeping things simple. No unreadable ingredient lists: we
              use clean, effective components across everything we make.
            </p>
            <div class="sustain-badges d-flex flex-wrap gap-3 mt-4">
              <span class="sustain-badge" v-for="b in badges" :key="b">{{
                b
              }}</span>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
export default {
  name: "AboutPage",
  data() {
    return {
      values: [
        {
          icon: "🌿",
          title: "Botanical",
          desc: "Every ingredient is sourced from nature, chosen for its purity and story.",
        },
        {
          icon: "🤍",
          title: "Cruelty Free",
          desc: "Never tested on animals. Always kind to every living thing.",
        },
        {
          icon: "♻️",
          title: "Sustainable",
          desc: "Packaging designed to leave the lightest possible footprint on the earth.",
        },
        {
          icon: "✦",
          title: "Intentional",
          desc: "Every decision — from formula to bottle — is made with purpose and care.",
        },
      ],
      team: [
        {
          name: "Boglárka Varga",
          initial: "B",
          role: "Founder & CEO",
          bio: "Boglárka began dreaming of a natural, cruelty-free beauty line in 2023. Her vision — products that are as kind to the skin as they are to the planet — is the heart of everything Buttercup makes.",
          color: "#9c9f88",
        },
      ],
      badges: [
        "🌱 Vegan",
        "🐰 Cruelty Free",
        "♻️ Recycled Packaging",
        "🌍 Carbon Offset",
      ],
    };
  },
};
</script>

<style scoped>
.about-page {
  padding-top: 72px;
  background: var(--white-warm);
}

.about-hero {
  position: relative;
  overflow: hidden;
  background: var(--white-warm);
}
.min-vh-75 {
  min-height: 75vh;
}

.about-title {
  font-family: var(--font-serif);
  font-size: clamp(52px, 8vw, 88px);
  font-weight: 300;
  line-height: 0.95;
  color: var(--text-dark);
  margin-bottom: 28px;
}
.accent {
  color: var(--dusty-rose);
  font-style: italic;
}

.about-lead {
  font-size: 17px;
  line-height: 1.75;
  color: var(--text-mid);
  max-width: 480px;
}

.about-hero-visual {
  position: relative;
  width: 320px;
  height: 320px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.about-blob {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: radial-gradient(circle, var(--beige-light) 0%, transparent 70%);
  animation: floatBlob 7s ease-in-out infinite;
}
.about-icon-cluster {
  position: relative;
  width: 100%;
  height: 100%;
}
.cluster-icon {
  position: absolute;
  font-size: 42px;
  animation: floatBottle 4s ease-in-out infinite;
}
.ci-1 {
  top: 20%;
  left: 15%;
  animation-delay: 0s;
}
.ci-2 {
  top: 10%;
  right: 20%;
  font-size: 28px;
  color: var(--dusty-rose);
  animation-delay: 1s;
}
.ci-3 {
  bottom: 20%;
  right: 15%;
  animation-delay: 2s;
}
.ci-4 {
  bottom: 15%;
  left: 20%;
  animation-delay: 1.5s;
}

@keyframes floatBlob {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.06);
  }
}
@keyframes floatBottle {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

.about-values {
  background: var(--white-warm);
}

.value-card {
  background: #fff;
  border: 1px solid rgba(156, 159, 136, 0.2);
  border-radius: 4px;
  padding: 36px 24px;
  transition:
    box-shadow 0.25s,
    transform 0.25s;
}
.value-card:hover {
  box-shadow: 0 8px 32px rgba(42, 37, 24, 0.1);
  transform: translateY(-4px);
}
.value-icon {
  font-size: 36px;
}
.value-title {
  font-family: var(--font-serif);
  font-size: 20px;
  font-weight: 400;
  color: var(--text-dark);
  margin-bottom: 10px;
}
.value-desc {
  font-size: 13px;
  line-height: 1.7;
  color: var(--text-light);
  margin: 0;
}

.about-story,
.about-sustainability {
  padding: 100px 0;
  border-top: 1px solid rgba(156, 159, 136, 0.15);
}

.story-img-placeholder {
  width: 100%;
  height: 420px;
  background: linear-gradient(135deg, var(--beige-light), var(--sage-light));
  border-radius: 4px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-sans);
  font-size: 13px;
  letter-spacing: 0.1em;
  color: var(--text-light);
}

.story-text p {
  font-size: 15px;
  line-height: 1.8;
  color: var(--text-mid);
  margin-bottom: 16px;
}

.about-team {
  background: var(--white-warm);
}

.team-card {
  padding: 36px 24px;
  border: 1px solid rgba(156, 159, 136, 0.2);
  border-radius: 4px;
  background: #fff;
  transition: box-shadow 0.25s;
}
.team-card:hover {
  box-shadow: 0 8px 32px rgba(42, 37, 24, 0.1);
}

.team-avatar {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-serif);
  font-size: 28px;
  color: #fff;
}
.team-name {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 400;
  color: var(--text-dark);
  margin-bottom: 4px;
}
.team-role {
  font-size: 11px;
  letter-spacing: 0.15em;
  text-transform: uppercase;
  color: var(--dusty-rose);
}
.team-bio {
  font-size: 13px;
  line-height: 1.7;
  color: var(--text-light);
  margin: 0;
}

.sustain-badge {
  background: rgba(156, 159, 136, 0.15);
  border: 1px solid rgba(156, 159, 136, 0.35);
  padding: 6px 14px;
  font-size: 12px;
  border-radius: 20px;
  color: var(--text-mid);
}
</style>
