<template>
  <AccountLayout
    title="My Orders"
    subtitle="Your order history and current shipments."
    :signed-in="!!authToken"
    @go-auth="$emit('go-auth')"
    @logout="$emit('logout')"
  >
    <AccountSkeleton v-if="loading" :rows="3" />

    <div v-else-if="error" class="account-banner error">{{ error }}</div>

    <div v-else-if="orders.length === 0" class="account-empty text-center py-5">
      <span class="empty-icon">📦</span>
      <h3 class="mt-3">No orders yet</h3>
      <p class="mb-4">When you place an order it'll show up here.</p>
      <button class="account-btn-primary" @click="$emit('go-shop')">Browse the shop</button>
    </div>

    <div v-else>
      <div class="orders-header-row">
        <h2 class="account-section-title mb-0">
          {{ filteredOrders.length }}
          {{ filteredOrders.length === 1 ? 'order' : 'orders' }}
          <span v-if="filteredOrders.length !== orders.length" class="orders-filter-suffix">
            (of {{ orders.length }})
          </span>
        </h2>

        <!-- Filter + search. Both filter the local `orders` array so
             there's no network round-trip per keystroke. -->
        <div class="orders-controls">
          <input
            type="search"
            class="orders-search"
            placeholder="Search by order #"
            v-model.trim="search"
          />
          <select v-model="statusFilter" class="orders-status-filter">
            <option value="">All statuses</option>
            <option v-for="s in availableStatuses" :key="s" :value="s">
              {{ statusLabel(s) }}
            </option>
          </select>
        </div>
      </div>

      <div
        v-if="filteredOrders.length === 0"
        class="orders-empty-filtered"
      >
        No orders match those filters.
        <a href="#" @click.prevent="clearFilters">Clear filters</a>
      </div>

      <div v-for="order in filteredOrders" :key="order.id" class="order-card">
        <div class="order-card-head" @click="toggle(order.id)">
          <div>
            <p class="order-id">Order #{{ order.id }}</p>
            <p class="order-date">{{ order.created_at }}</p>
          </div>
          <div class="order-meta">
            <span class="account-pill" :class="statusClass(order.status)">{{ statusLabel(order.status) }}</span>
            <span class="order-total">{{ formatPrice(order.total_amount) }}</span>
            <span class="order-toggle">{{ expanded === order.id ? '−' : '+' }}</span>
          </div>
        </div>

        <div v-if="expanded === order.id" class="order-card-body">
          <div class="order-section-grid">
            <div>
              <p class="order-detail-label">Payment</p>
              <p>{{ paymentLabel(order.payment_method) }} · {{ order.payment_status }}</p>
            </div>
            <div v-if="order.address">
              <p class="order-detail-label">Shipping to</p>
              <p>{{ order.address.full_address }}</p>
            </div>
          </div>

          <div class="order-items">
            <p class="order-detail-label">Items</p>
            <div v-for="item in order.items" :key="item.id" class="order-item-row">
              <div class="order-item-name">
                {{ item.product?.name || 'Product' }}
                <span class="order-item-qty">× {{ item.quantity }}</span>
              </div>
              <div class="order-item-price">{{ formatPrice(item.subtotal) }}</div>
            </div>
          </div>

          <p v-if="order.notes" class="order-notes">"{{ order.notes }}"</p>

          <!-- Customer-initiated cancellation. Only shown while the
               order is still `pending`; once admin moves it to
               `processing` the back-end refuses the request anyway. -->
          <div v-if="order.status === 'pending'" class="order-actions">
            <p v-if="cancelError[order.id]" class="account-banner error">
              {{ cancelError[order.id] }}
            </p>
            <button
              class="account-btn-danger"
              :disabled="cancelling[order.id]"
              @click="cancelOrder(order)"
            >
              {{ cancelling[order.id] ? 'Cancelling…' : 'Cancel this order' }}
            </button>
            <p class="form-hint mt-1">
              You can cancel while the order is still pending. After we start
              processing it, contact us instead.
            </p>
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
import { toastSuccess, toastError } from '../../toast.js';

const STATUS_LABELS = {
  pending:    'Pending',
  processing: 'Processing',
  shipped:    'Shipped',
  arrived:    'Delivered',
  canceled:   'Canceled',
  refunded:   'Refunded',
};

const STATUS_CLASSES = {
  arrived:  'success',
  shipped:  'success',
  pending:  'warn',
  processing: 'warn',
  canceled: 'danger',
  refunded: 'danger',
};

const PAYMENT_LABELS = {
  card:          'Card',
  bank_transfer: 'Bank Transfer',
  transfer:      'Bank Transfer',
};

export default {
  name: 'AccountOrders',
  components: { AccountLayout, AccountSkeleton },
  emits: ['go-auth', 'go-shop', 'logout'],
  data() {
    return {
      authToken: localStorage.getItem('buttercup_token') || null,
      orders:    [],
      loading:   false,
      error:     '',
      expanded:  null,
      // Per-order in-flight + error state for the cancel button. Kept
      // keyed by order id so a slow request on one card doesn't disable
      // the buttons on the others.
      cancelling: {},
      cancelError: {},

      // Local filter state — pure client-side, no extra API calls.
      search: '',
      statusFilter: '',
    };
  },

  computed: {
    /**
     * Subset of orders matching the current search + status filter.
     * Search matches the order id as a string (most useful) and falls
     * back to the visible status label. Empty filters mean "show
     * everything", and the array preserves the server's order.
     */
    filteredOrders() {
      const q  = this.search.toLowerCase().replace(/^#/, '');
      const sf = this.statusFilter;
      if (!q && !sf) return this.orders;

      return this.orders.filter((order) => {
        if (sf && order.status !== sf) return false;
        if (!q) return true;
        return String(order.id).includes(q);
      });
    },
    /**
     * Statuses that are actually present in the current order list,
     * so the dropdown doesn't offer "Refunded" if the user has never
     * had a refund.
     */
    availableStatuses() {
      const seen = new Set();
      for (const o of this.orders) {
        if (o.status) seen.add(o.status);
      }
      // Preserve a sensible order rather than insertion order.
      const ORDER = ['pending', 'processing', 'shipped', 'arrived', 'canceled', 'refunded'];
      return ORDER.filter((s) => seen.has(s));
    },
  },
  created() {
    if (this.authToken) this.loadOrders();
  },
  methods: {
    async loadOrders() {
      this.loading = true;
      this.error   = '';
      try {
        const res = await fetch(`${getApiBase()}/orders`, {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) {
          localStorage.removeItem('buttercup_token');
          localStorage.removeItem('buttercup_user');
          this.authToken = null;
          this.$emit('go-auth');
          return;
        }
        if (!res.ok) {
          this.error = 'Could not load your orders right now.';
          return;
        }
        const json = await res.json();
        this.orders = Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        this.error = 'Network error — please try again.';
      } finally {
        this.loading = false;
      }
    },
    toggle(id) {
      this.expanded = this.expanded === id ? null : id;
    },

    clearFilters() {
      this.search = '';
      this.statusFilter = '';
    },

    async cancelOrder(order) {
      if (!order || order.status !== 'pending') return;
      if (!window.confirm(`Cancel order #${order.id}? This restores the items to stock.`)) {
        return;
      }

      this.cancelling = { ...this.cancelling, [order.id]: true };
      this.cancelError = { ...this.cancelError, [order.id]: '' };

      try {
        const res = await fetch(`${getApiBase()}/orders/${order.id}/cancel`, {
          method: 'POST',
          headers: {
            Accept:        'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401) {
          localStorage.removeItem('buttercup_token');
          localStorage.removeItem('buttercup_user');
          this.authToken = null;
          this.$emit('go-auth');
          return;
        }
        if (!res.ok) {
          const msg = data?.message || 'Could not cancel the order.';
          this.cancelError = { ...this.cancelError, [order.id]: msg };
          toastError(msg);
          return;
        }

        // Replace the local order with the fresh one from the server so
        // the status pill flips to "Canceled" without a full re-fetch.
        const updated = data?.data ?? data;
        if (updated?.id) {
          const idx = this.orders.findIndex((o) => o.id === updated.id);
          if (idx !== -1) this.orders.splice(idx, 1, updated);
        } else {
          await this.loadOrders();
        }
        toastSuccess(`Order #${order.id} canceled. Stock has been restored.`);
      } catch {
        this.cancelError = {
          ...this.cancelError,
          [order.id]: 'Network error — please try again.',
        };
        toastError('Network error — please try again.');
      } finally {
        this.cancelling = { ...this.cancelling, [order.id]: false };
      }
    },
    statusLabel(s) { return STATUS_LABELS[s] ?? s; },
    statusClass(s) { return STATUS_CLASSES[s] ?? ''; },
    paymentLabel(p) { return PAYMENT_LABELS[p] ?? p; },
    formatPrice(value) {
      const n = Math.round(Number(value) || 0);
      return `${n.toLocaleString('hu-HU')} Ft`;
    },
  },
};
</script>

<style>
.order-card {
  background: var(--white-warm, #fff);
  border: 1px solid rgba(156, 159, 136, 0.22);
  border-radius: 6px;
  margin-bottom: 12px;
  overflow: hidden;
}
.order-card-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  cursor: pointer;
  gap: 16px;
  flex-wrap: wrap;
  transition: background 0.2s;
}
.order-card-head:hover { background: rgba(232, 213, 176, 0.18); }
.order-id   { font-family: var(--font-serif); font-size: 18px; margin: 0; color: var(--text-dark); }
.order-date { font-size: 13px; color: var(--text-light); margin: 0; }
.order-meta { display: flex; align-items: center; gap: 14px; font-family: var(--font-sans); }
.order-total { font-family: var(--font-serif); font-size: 18px; color: var(--text-dark); }
.order-toggle {
  display: inline-flex;
  width: 24px; height: 24px;
  border-radius: 50%;
  background: var(--beige-light);
  color: var(--dusty-rose);
  align-items: center; justify-content: center;
  font-size: 18px; line-height: 1;
}

.order-card-body {
  padding: 16px 20px 20px 20px;
  background: rgba(245, 240, 229, 0.5);
  border-top: 1px solid rgba(156, 159, 136, 0.18);
}
.order-section-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  margin-bottom: 14px;
}
@media (max-width: 575px) {
  .order-section-grid { grid-template-columns: 1fr; }
}
.order-detail-label {
  font-size: 12px;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-light);
  margin: 0 0 4px 0;
}
.order-section-grid p:not(.order-detail-label),
.order-items p:not(.order-detail-label) {
  margin: 0;
  font-size: 14px;
  color: var(--text-dark);
}
.order-items { margin-top: 10px; }
.order-item-row {
  display: flex; justify-content: space-between;
  padding: 6px 0;
  border-bottom: 1px dashed rgba(156, 159, 136, 0.2);
  font-size: 14px;
}
.order-item-row:last-child { border-bottom: none; }
.order-item-qty { color: var(--text-light); font-size: 13px; margin-left: 6px; }
.order-notes { margin-top: 12px; color: var(--text-light); font-style: italic; font-size: 14px; }

.orders-header-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 14px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}
.orders-filter-suffix {
  color: var(--text-light);
  font-size: 14px;
  font-family: var(--font-sans);
  font-weight: 400;
  margin-left: 4px;
}
.orders-controls {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}
.orders-search,
.orders-status-filter {
  background: var(--white-warm, #fff);
  border: 1px solid rgba(156, 159, 136, 0.3);
  border-radius: 4px;
  padding: 7px 12px;
  font-size: 13px;
  font-family: var(--font-sans);
  color: var(--text-dark);
  transition: border-color 0.18s;
}
.orders-search { min-width: 180px; }
.orders-status-filter { min-width: 140px; cursor: pointer; }
.orders-search:focus,
.orders-status-filter:focus {
  outline: none;
  border-color: var(--dusty-rose, #b9676b);
}
.orders-empty-filtered {
  text-align: center;
  color: var(--text-light);
  padding: 32px 0;
  font-size: 14px;
}
.orders-empty-filtered a {
  color: var(--dusty-rose, #b9676b);
  margin-left: 6px;
}

.orders-loading {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-top: 12px;
}
.order-card-skeleton {
  background: var(--white-warm, #fff);
  border: 1px solid rgba(156, 159, 136, 0.18);
  border-radius: 6px;
  padding: 16px 20px;
}

.order-actions {
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid rgba(156, 159, 136, 0.18);
}
.order-actions .form-hint {
  font-size: 12px;
  color: var(--text-light);
  margin-top: 6px;
}
</style>
