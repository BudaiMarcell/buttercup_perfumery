<template>
  <AccountLayout
    title="Wishlist"
    subtitle="Fragrances you've saved for later."
    :signed-in="!!authToken"
    @go-auth="$emit('go-auth')"
    @logout="$emit('logout')"
  >
    <AccountSkeleton v-if="loading" :rows="3" />

    <div v-else-if="error" class="account-banner error">{{ error }}</div>

    <div v-else-if="items.length === 0" class="account-empty text-center py-5">
      <span class="empty-icon">✦</span>
      <h3 class="mt-3">Nothing saved yet</h3>
      <p class="mb-4">Tap the heart on a product to save it here.</p>
      <button class="account-btn-primary" @click="$emit('go-shop')">Browse the shop</button>
    </div>

    <div v-else>
      <h2 class="account-section-title">{{ items.length }} saved item{{ items.length === 1 ? '' : 's' }}</h2>

      <div class="row g-3">
        <div
          v-for="entry in items"
          :key="entry.id"
          class="col-12 col-sm-6 col-xl-4"
        >
          <div class="wishlist-card">
            <div class="wishlist-img" :style="bgFor(entry)">
              <span class="wishlist-icon" aria-hidden="true">{{ iconFor(entry) }}</span>
            </div>
            <div class="wishlist-body">
              <p class="wishlist-name">{{ entry.product?.name || 'Product' }}</p>
              <p class="wishlist-meta">{{ entry.product?.volume_ml || '' }} · {{ genderLabel(entry.product?.gender) }}</p>
              <p class="wishlist-price">{{ formatPrice(entry.product?.price) }}</p>
              <div class="account-actions mt-2">
                <button
                  class="account-btn-secondary"
                  :disabled="busyId === entry.product_id"
                  @click="addToCart(entry)"
                >
                  Add to cart
                </button>
                <button
                  class="account-btn-danger"
                  :disabled="busyId === entry.product_id"
                  @click="remove(entry.product_id)"
                >
                  {{ busyId === entry.product_id ? '…' : 'Remove' }}
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AccountLayout>
</template>

<script>
import AccountLayout from './AccountLayout.vue';
import AccountSkeleton from './AccountSkeleton.vue';
import { getApiBase } from '../../config.js';
import { mapApiProduct } from '../../products.js';

const GENDER_LABELS = {
  male:   "Men's",
  female: "Women's",
  unisex: 'Unisex',
};

export default {
  name: 'AccountWishlist',
  components: { AccountLayout, AccountSkeleton },
  emits: ['go-auth', 'go-shop', 'logout', 'add-to-cart', 'wishlist-changed'],
  data() {
    return {
      authToken: localStorage.getItem('buttercup_token') || null,
      items:     [],
      loading:   false,
      error:     '',
      busyId:    null,
    };
  },
  created() {
    if (this.authToken) this.loadWishlist();
  },
  methods: {
    async loadWishlist() {
      this.loading = true;
      this.error   = '';
      try {
        const res = await fetch(`${getApiBase()}/wishlist`, {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) {
          this.handleAuthExpiry();
          return;
        }
        if (!res.ok) {
          this.error = 'Could not load your wishlist right now.';
          return;
        }
        const json = await res.json();
        this.items = Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        this.error = 'Network error — please try again.';
      } finally {
        this.loading = false;
      }
    },

    async remove(productId) {
      this.busyId = productId;
      try {
        const res = await fetch(`${getApiBase()}/wishlist/${productId}`, {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.error = 'Could not remove that item — please try again.';
          return;
        }
        this.items = this.items.filter((x) => x.product_id !== productId);
        this.$emit('wishlist-changed', this.items.map((x) => x.product_id));
      } catch {
        this.error = 'Network error — please try again.';
      } finally {
        this.busyId = null;
      }
    },

    addToCart(entry) {
      if (!entry.product) return;
      const display = mapApiProduct(entry.product);
      this.$emit('add-to-cart', display);
    },

    handleAuthExpiry() {
      localStorage.removeItem('buttercup_token');
      localStorage.removeItem('buttercup_user');
      this.authToken = null;
      this.$emit('go-auth');
    },

    bgFor(entry) {
      const display = entry.product ? mapApiProduct(entry.product) : null;
      return display ? { background: display.bgColor } : {};
    },
    iconFor(entry) {
      const display = entry.product ? mapApiProduct(entry.product) : null;
      return display?.icon ?? '✦';
    },
    genderLabel(g) { return GENDER_LABELS[g] ?? 'Unisex'; },
    formatPrice(value) {
      if (value == null) return '';
      const n = Math.round(Number(value) || 0);
      return `${n.toLocaleString('hu-HU')} Ft`;
    },
  },
};
</script>

<style>
.wishlist-card {
  display: flex;
  flex-direction: column;
  background: var(--white-warm, #fff);
  border: 1px solid rgba(156, 159, 136, 0.22);
  border-radius: 6px;
  overflow: hidden;
  height: 100%;
}
.wishlist-img {
  height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.wishlist-icon { font-size: 38px; }
.wishlist-body { padding: 14px 16px 16px 16px; display: flex; flex-direction: column; flex-grow: 1; }
.wishlist-name {
  font-family: var(--font-serif);
  font-size: 18px;
  margin: 0 0 4px 0;
  color: var(--text-dark);
}
.wishlist-meta {
  font-size: 12px;
  letter-spacing: 0.04em;
  color: var(--text-light);
  text-transform: uppercase;
  margin: 0 0 6px 0;
}
.wishlist-price {
  font-family: var(--font-serif);
  font-size: 18px;
  margin: 0;
  color: var(--dusty-rose);
}
</style>
