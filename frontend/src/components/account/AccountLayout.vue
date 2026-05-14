<template>
  <div class="account-page">
    <div class="container-xl py-5">

      <div class="account-hero mb-4">
        <p class="section-eyebrow">— My Account</p>
        <h1 class="account-title">{{ title }}</h1>
        <p class="account-sub" v-if="subtitle">{{ subtitle }}</p>
      </div>

      <div v-if="!signedIn" class="account-empty text-center py-5">
        <span class="empty-icon" aria-hidden="true">🔒</span>
        <h3 class="mt-3">Please sign in</h3>
        <p class="mb-4">You need to be signed in to view this page.</p>
        <button class="checkout-btn" @click="$emit('go-auth')">Sign In / Register</button>
      </div>

      <div v-else class="row g-4">
        <aside class="col-12 col-lg-3">
          <nav class="account-nav">
            <router-link
              v-for="link in navLinks"
              :key="link.name"
              :to="{ name: link.name }"
              class="account-nav-link"
              active-class="active"
            >
              <span class="nav-icon" aria-hidden="true">{{ link.icon }}</span>
              <span>{{ link.label }}</span>
            </router-link>

            <button class="account-nav-link account-nav-signout" @click="$emit('logout')">
              <span class="nav-icon" aria-hidden="true">↩</span>
              <span>Sign Out</span>
            </button>
          </nav>
        </aside>

        <main class="col-12 col-lg-9">
          <div class="account-content">
            <slot />
          </div>
        </main>
      </div>

    </div>
  </div>
</template>

<script>
export default {
  name: 'AccountLayout',
  emits: ['go-auth', 'logout'],
  props: {
    title:    { type: String, required: true },
    subtitle: { type: String, default: '' },
    signedIn: { type: Boolean, required: true },
  },
  data() {
    return {
      navLinks: [
        { name: 'account-orders',          label: 'My Orders',       icon: '📦' },
        { name: 'account-wishlist',        label: 'Wishlist',        icon: '✦' },
        { name: 'account-addresses',       label: 'Addresses',       icon: '📍' },
        { name: 'account-payment-methods', label: 'Payment Methods', icon: '💳' },
        { name: 'account-settings',        label: 'Settings',        icon: '⚙' },
      ],
    };
  },
};
</script>

<style>
.account-page {
  background: var(--cream, #faf5ec);
  min-height: 70vh;
}
.account-hero { padding-bottom: 12px; border-bottom: 1px solid rgba(156, 159, 136, 0.2); }
.account-title {
  font-family: var(--font-serif);
  font-weight: 300;
  font-size: clamp(28px, 4vw, 44px);
  color: var(--text-dark);
  margin: 0;
}
.account-sub {
  color: var(--text-light);
  margin: 8px 0 0 0;
  font-size: 15px;
}

.account-nav {
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 12px;
  background: var(--white-warm, #ffffff);
  border: 1px solid rgba(156, 159, 136, 0.2);
  border-radius: 6px;
  position: sticky;
  top: 90px;
}
.account-nav-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 4px;
  font-family: var(--font-sans);
  font-size: 15px;
  color: var(--text-dark);
  text-decoration: none;
  background: transparent;
  border: none;
  text-align: left;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.account-nav-link:hover { background: var(--beige-light); color: var(--dusty-rose); }
.account-nav-link.active {
  background: var(--beige-light);
  color: var(--dusty-rose);
  font-weight: 500;
}
.account-nav-link .nav-icon {
  display: inline-flex;
  width: 22px;
  justify-content: center;
  font-size: 14px;
  color: var(--dusty-rose);
}
.account-nav-signout { margin-top: 8px; border-top: 1px solid rgba(156, 159, 136, 0.2); padding-top: 16px; }

.account-content {
  background: var(--white-warm, #ffffff);
  border: 1px solid rgba(156, 159, 136, 0.2);
  border-radius: 6px;
  padding: 24px;
}

.account-empty .empty-icon { font-size: 48px; }
.account-empty h3 {
  font-family: var(--font-serif);
  font-weight: 400;
  color: var(--text-dark);
}
.account-empty p { color: var(--text-light); }

.account-pill {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  background: var(--beige-light);
  color: var(--dusty-rose);
}
.account-pill.success { background: #e0ecd9; color: #547a3a; }
.account-pill.warn    { background: #f6e7c8; color: #a38033; }
.account-pill.danger  { background: #f3d4d4; color: #a44a4a; }

.account-section-title {
  font-family: var(--font-serif);
  font-size: 22px;
  font-weight: 400;
  color: var(--text-dark);
  margin: 0 0 16px 0;
}

.account-list-row {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(156, 159, 136, 0.18);
}
.account-list-row:last-child { border-bottom: none; }

.account-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.account-btn-primary {
  background: var(--dusty-rose);
  color: #fff;
  border: none;
  padding: 10px 18px;
  border-radius: 4px;
  font-family: var(--font-sans);
  font-size: 14px;
  letter-spacing: 0.05em;
  cursor: pointer;
  transition: background 0.2s;
}
.account-btn-primary:hover:not(:disabled) { background: var(--dusty-rose-dark); }
.account-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }
.account-btn-secondary {
  background: transparent;
  color: var(--text-dark);
  border: 1px solid rgba(156, 159, 136, 0.4);
  padding: 8px 14px;
  border-radius: 4px;
  font-family: var(--font-sans);
  font-size: 13px;
  cursor: pointer;
  transition: border-color 0.2s, color 0.2s;
}
.account-btn-secondary:hover { border-color: var(--dusty-rose); color: var(--dusty-rose); }
.account-btn-danger {
  background: transparent;
  color: #a44a4a;
  border: 1px solid #d6a4a4;
  padding: 8px 14px;
  border-radius: 4px;
  font-family: var(--font-sans);
  font-size: 13px;
  cursor: pointer;
  transition: background 0.2s, color 0.2s;
}
.account-btn-danger:hover { background: #f3d4d4; }

.account-form {
  display: flex;
  flex-direction: column;
  gap: 14px;
  max-width: 520px;
}
.account-form label {
  font-size: 13px;
  letter-spacing: 0.04em;
  color: var(--text-light);
  text-transform: uppercase;
  margin-bottom: 4px;
}
.account-form .form-input {
  width: 100%;
}

.account-banner {
  padding: 12px 16px;
  border-radius: 4px;
  font-size: 14px;
  margin-bottom: 16px;
}
.account-banner.success { background: #e0ecd9; color: #3d5a23; }
.account-banner.error   { background: #f3d4d4; color: #8a2e2e; }
</style>
