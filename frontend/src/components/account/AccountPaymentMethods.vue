<template>
  <AccountLayout
    title="Payment Methods"
    subtitle="Cards saved for faster checkout."
    :signed-in="!!authToken"
    @go-auth="$emit('go-auth')"
    @logout="$emit('logout')"
  >
    <AccountSkeleton v-if="loading" :rows="2" />

    <div v-else>
      <div v-if="banner" class="account-banner" :class="banner.kind">{{ banner.text }}</div>

      <div v-if="cards.length === 0" class="account-empty text-center py-5">
        <span class="empty-icon">💳</span>
        <h3 class="mt-3">No saved cards</h3>
        <p class="mb-4">
          Save a card from the next checkout — only the brand, last 4 digits,
          and expiry are kept on file. The full number and CVV are never stored.
        </p>
      </div>

      <div v-else>
        <h2 class="account-section-title">{{ cards.length }} saved card{{ cards.length === 1 ? '' : 's' }}</h2>

        <div v-for="card in cards" :key="card.id" class="payment-row">
          <div class="payment-info">
            <div class="payment-row-head">
              <span class="payment-brand">{{ brandLabel(card.brand) }}</span>
              <span class="payment-last-four">•••• {{ card.last_four }}</span>
              <span v-if="card.is_default" class="account-pill success">Default</span>
            </div>
            <p class="payment-meta">Expires {{ card.expiry }}</p>
          </div>
          <div class="account-actions">
            <button
              v-if="!card.is_default"
              class="account-btn-secondary"
              :disabled="busyId === card.id"
              @click="makeDefault(card)"
            >Set default</button>
            <button
              class="account-btn-danger"
              :disabled="busyId === card.id"
              @click="remove(card)"
            >{{ busyId === card.id ? '…' : 'Remove' }}</button>
          </div>
        </div>

        <p class="payment-disclaimer mt-4">
          We never store the full card number or CVV. To use a saved card you'll
          re-enter the CVV at checkout. Brand and last 4 digits are kept so the
          picker can show a meaningful label.
        </p>
      </div>
    </div>
  </AccountLayout>
</template>

<script>
import AccountLayout from './AccountLayout.vue';
import AccountSkeleton from './AccountSkeleton.vue';
import { getApiBase } from '../../config.js';

const BRAND_LABELS = {
  visa:       'Visa',
  mastercard: 'Mastercard',
  amex:       'American Express',
  discover:   'Discover',
};

export default {
  name: 'AccountPaymentMethods',
  components: { AccountLayout, AccountSkeleton },
  emits: ['go-auth', 'logout'],
  data() {
    return {
      authToken: localStorage.getItem('buttercup_token') || null,
      cards:     [],
      loading:   false,
      banner:    null,
      busyId:    null,
    };
  },
  created() {
    if (this.authToken) this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const res = await fetch(`${getApiBase()}/payment-methods`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${this.authToken}` },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.banner = { kind: 'error', text: 'Could not load your saved cards.' };
          return;
        }
        const json = await res.json();
        this.cards = Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        this.banner = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.loading = false;
      }
    },

    async remove(card) {
      if (!confirm(`Remove ${this.brandLabel(card.brand)} •••• ${card.last_four}?`)) return;
      this.busyId = card.id;
      try {
        const res = await fetch(`${getApiBase()}/payment-methods/${card.id}`, {
          method: 'DELETE',
          headers: { Accept: 'application/json', Authorization: `Bearer ${this.authToken}` },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.banner = { kind: 'error', text: 'Could not remove that card.' };
          return;
        }
        this.cards = this.cards.filter((c) => c.id !== card.id);
        this.banner = { kind: 'success', text: 'Card removed.' };
      } catch {
        this.banner = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.busyId = null;
      }
    },

    async makeDefault(card) {
      this.busyId = card.id;
      try {
        const res = await fetch(`${getApiBase()}/payment-methods/${card.id}`, {
          method: 'PUT',
          headers: {
            'Content-Type':  'application/json',
            Accept:          'application/json',
            Authorization:   `Bearer ${this.authToken}`,
          },
          body: JSON.stringify({ is_default: true }),
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.banner = { kind: 'error', text: 'Could not update default card.' };
          return;
        }
        await this.load();
      } catch {
        this.banner = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.busyId = null;
      }
    },

    handleAuthExpiry() {
      localStorage.removeItem('buttercup_token');
      localStorage.removeItem('buttercup_user');
      this.authToken = null;
      this.$emit('go-auth');
    },

    brandLabel(brand) {
      const key = (brand || '').toLowerCase();
      return BRAND_LABELS[key] || brand || 'Card';
    },
  },
};
</script>

<style>
.payment-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(156, 159, 136, 0.18);
  flex-wrap: wrap;
}
.payment-row:last-of-type { border-bottom: none; }
.payment-info { flex: 1 1 240px; }
.payment-row-head {
  display: flex;
  align-items: center;
  gap: 12px;
  flex-wrap: wrap;
}
.payment-brand {
  font-family: var(--font-serif);
  font-size: 18px;
  color: var(--text-dark);
}
.payment-last-four {
  font-family: var(--font-mono, "Courier New", monospace);
  letter-spacing: 0.1em;
  color: var(--text-dark);
}
.payment-meta {
  font-size: 13px;
  color: var(--text-light);
  margin: 4px 0 0 0;
}
.payment-disclaimer {
  font-size: 13px;
  color: var(--text-light);
  font-style: italic;
}
</style>
