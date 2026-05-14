<template>
  <AccountLayout
    title="Addresses"
    subtitle="Saved delivery addresses for faster checkout."
    :signed-in="!!authToken"
    @go-auth="$emit('go-auth')"
    @logout="$emit('logout')"
  >
    <AccountSkeleton v-if="loading" :rows="2" />

    <div v-else>
      <div v-if="banner" class="account-banner" :class="banner.kind">{{ banner.text }}</div>

      <div v-if="addresses.length === 0 && !showForm" class="account-empty text-center py-5">
        <span class="empty-icon">📍</span>
        <h3 class="mt-3">No saved addresses</h3>
        <p class="mb-4">Add one to make checkout a one-click affair.</p>
        <button class="account-btn-primary" @click="newAddress">Add address</button>
      </div>

      <div v-else>
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h2 class="account-section-title m-0">{{ addresses.length }} saved address{{ addresses.length === 1 ? '' : 'es' }}</h2>
          <button v-if="!showForm" class="account-btn-primary" @click="newAddress">+ Add address</button>
        </div>

        <div v-for="addr in addresses" :key="addr.id" class="address-row">
          <div class="address-info">
            <div class="address-label-row">
              <span class="address-label">{{ addr.label }}</span>
              <span v-if="addr.is_default" class="account-pill success">Default</span>
            </div>
            <p class="address-body">{{ addr.full_address }}</p>
            <p class="address-country">{{ addr.country }}</p>
          </div>
          <div class="account-actions">
            <button
              class="account-btn-secondary"
              v-if="!addr.is_default"
              @click="makeDefault(addr)"
              :disabled="busyId === addr.id"
            >Set default</button>
            <button class="account-btn-secondary" @click="editAddress(addr)" :disabled="busyId === addr.id">Edit</button>
            <button class="account-btn-danger" @click="remove(addr)" :disabled="busyId === addr.id">
              {{ busyId === addr.id ? '…' : 'Delete' }}
            </button>
          </div>
        </div>

        <div v-if="showForm" class="address-form-wrap">
          <h3 class="account-section-title">{{ editing ? 'Edit address' : 'New address' }}</h3>
          <form class="account-form" @submit.prevent="submit">
            <div>
              <label>Label</label>
              <input class="form-input" v-model="form.label" placeholder="Home, Work, …" required />
            </div>
            <div class="row g-3">
              <div class="col-12 col-sm-6">
                <label>Country</label>
                <input class="form-input" v-model="form.country" required />
              </div>
              <div class="col-12 col-sm-6">
                <label>City</label>
                <input class="form-input" v-model="form.city" required />
              </div>
            </div>
            <div class="row g-3">
              <div class="col-12 col-sm-4">
                <label>ZIP</label>
                <input class="form-input" v-model="form.zip_code" required />
              </div>
              <div class="col-12 col-sm-8">
                <label>Street</label>
                <input class="form-input" v-model="form.street" required />
              </div>
            </div>
            <label class="d-flex align-items-center gap-2 mt-1" style="text-transform:none; letter-spacing:0;">
              <input type="checkbox" v-model="form.is_default" />
              <span>Use as my default delivery address</span>
            </label>
            <p v-if="formError" class="account-banner error">{{ formError }}</p>
            <div class="account-actions mt-2">
              <button class="account-btn-primary" :disabled="submitting" type="submit">
                {{ submitting ? 'Saving…' : (editing ? 'Update' : 'Save address') }}
              </button>
              <button class="account-btn-secondary" type="button" @click="cancelForm">Cancel</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AccountLayout>
</template>

<script>
import AccountLayout from './AccountLayout.vue';
import AccountSkeleton from './AccountSkeleton.vue';
import { getApiBase } from '../../config.js';

const EMPTY_FORM = () => ({
  label: 'Home',
  country: 'Magyarország',
  city: '',
  zip_code: '',
  street: '',
  is_default: false,
});

export default {
  name: 'AccountAddresses',
  components: { AccountLayout, AccountSkeleton },
  emits: ['go-auth', 'logout'],
  data() {
    return {
      authToken: localStorage.getItem('buttercup_token') || null,
      addresses: [],
      loading:   false,
      banner:    null,

      showForm:   false,
      editing:    null,
      submitting: false,
      formError:  '',
      form:       EMPTY_FORM(),
      busyId:     null,
    };
  },
  created() {
    if (this.authToken) this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const res = await fetch(`${getApiBase()}/addresses`, {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.banner = { kind: 'error', text: 'Could not load your addresses.' };
          return;
        }
        const json = await res.json();
        this.addresses = Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        this.banner = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.loading = false;
      }
    },

    newAddress() {
      this.editing  = null;
      this.form     = EMPTY_FORM();
      this.formError = '';
      this.showForm = true;
    },
    editAddress(addr) {
      this.editing = addr.id;
      this.form    = {
        label:      addr.label,
        country:    addr.country,
        city:       addr.city,
        zip_code:   addr.zip_code,
        street:     addr.street,
        is_default: !!addr.is_default,
      };
      this.formError = '';
      this.showForm  = true;
    },
    cancelForm() {
      this.showForm = false;
      this.formError = '';
    },

    async submit() {
      this.submitting = true;
      this.formError  = '';
      try {
        const url = this.editing
          ? `${getApiBase()}/addresses/${this.editing}`
          : `${getApiBase()}/addresses`;

        const res = await fetch(url, {
          method: this.editing ? 'PUT' : 'POST',
          headers: {
            'Content-Type':  'application/json',
            Accept:          'application/json',
            Authorization:   `Bearer ${this.authToken}`,
          },
          body: JSON.stringify(this.form),
        });

        if (res.status === 401) { this.handleAuthExpiry(); return; }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.formError = this.firstError(data) || 'Could not save the address.';
          return;
        }

        this.banner = { kind: 'success', text: this.editing ? 'Address updated.' : 'Address saved.' };
        this.showForm = false;
        await this.load();
      } catch {
        this.formError = 'Network error — please try again.';
      } finally {
        this.submitting = false;
      }
    },

    async remove(addr) {
      if (!confirm(`Delete "${addr.label}"? This cannot be undone.`)) return;
      this.busyId = addr.id;
      try {
        const res = await fetch(`${getApiBase()}/addresses/${addr.id}`, {
          method: 'DELETE',
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.banner = { kind: 'error', text: 'Could not delete that address.' };
          return;
        }
        this.addresses = this.addresses.filter((a) => a.id !== addr.id);
        this.banner = { kind: 'success', text: 'Address deleted.' };
      } catch {
        this.banner = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.busyId = null;
      }
    },

    async makeDefault(addr) {
      this.busyId = addr.id;
      try {
        const res = await fetch(`${getApiBase()}/addresses/${addr.id}`, {
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
          this.banner = { kind: 'error', text: 'Could not update default address.' };
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

    firstError(data) {
      if (data?.message) return data.message;
      if (data?.errors) {
        const first = Object.values(data.errors).flat()[0];
        if (first) return first;
      }
      return null;
    },
  },
};
</script>

<style>
.address-row {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 16px;
  padding: 16px 0;
  border-bottom: 1px solid rgba(156, 159, 136, 0.18);
  flex-wrap: wrap;
}
.address-row:last-of-type { border-bottom: none; }
.address-info { flex: 1 1 240px; }
.address-label-row {
  display: flex; align-items: center; gap: 10px;
  margin-bottom: 4px;
}
.address-label {
  font-family: var(--font-serif);
  font-size: 18px;
  color: var(--text-dark);
}
.address-body {
  margin: 0;
  color: var(--text-dark);
}
.address-country {
  font-size: 13px;
  color: var(--text-light);
  margin: 0;
}
.address-form-wrap {
  margin-top: 24px;
  padding-top: 24px;
  border-top: 1px dashed rgba(156, 159, 136, 0.3);
}
</style>
