<template>
  <AccountLayout
    title="Settings"
    subtitle="Manage your profile and password."
    :signed-in="!!authToken"
    @go-auth="$emit('go-auth')"
    @logout="$emit('logout')"
  >
    <AccountSkeleton v-if="loading" :rows="3" />

    <div v-else>
      <div v-if="emailVerified === false" class="account-banner warn verify-banner">
        <div>
          <strong>Az e-mail címed még nincs megerősítve.</strong>
          <p class="mb-0 mt-1">
            Küldhetünk egy új megerősítő linket a regisztrált címre.
          </p>
        </div>
        <button
          class="account-btn-secondary"
          :disabled="resendingVerify"
          @click="resendVerification"
        >
          {{ resendingVerify ? 'Küldés…' : 'Új link küldése' }}
        </button>
      </div>
      <p v-if="verifyMsg" class="account-banner" :class="verifyMsg.kind">{{ verifyMsg.text }}</p>

      <h2 class="account-section-title">Profile</h2>
      <p v-if="profileMsg" class="account-banner" :class="profileMsg.kind">{{ profileMsg.text }}</p>

      <form class="account-form" @submit.prevent="saveProfile">
        <div>
          <label>Name</label>
          <input class="form-input" v-model="profile.name" required />
        </div>
        <div>
          <label>Email</label>
          <input class="form-input" type="email" v-model="profile.email" required />
        </div>
        <div>
          <label>Phone (optional)</label>
          <input class="form-input" v-model="profile.phone" placeholder="+36 …" />
        </div>
        <div class="account-actions mt-2">
          <button class="account-btn-primary" :disabled="savingProfile" type="submit">
            {{ savingProfile ? 'Saving…' : 'Save changes' }}
          </button>
        </div>
      </form>

      <hr class="account-divider" />

      <h2 class="account-section-title">Change password</h2>
      <p v-if="passwordMsg" class="account-banner" :class="passwordMsg.kind">{{ passwordMsg.text }}</p>

      <p class="form-hint" style="margin-bottom: 12px;">
        For your security we don't ask for your current password here —
        instead we'll email you a one-time link to set a new one. The link
        works for 60 minutes and arrives at <strong>{{ profile.email }}</strong>.
      </p>

      <div class="account-actions">
        <button
          class="account-btn-primary"
          :disabled="sendingResetLink"
          @click="requestPasswordResetLink"
        >
          {{ sendingResetLink ? 'Sending…' : '📧 Email me a reset link' }}
        </button>
      </div>

      <hr class="account-divider" />

      <h2 class="account-section-title">Sign out</h2>
      <p class="text-light-muted">
        Sign out of this device. To sign out of every device at once, change
        your password — that revokes every active session.
      </p>
      <div class="account-actions">
        <button class="account-btn-secondary" @click="$emit('logout')">Sign out</button>
      </div>

      <hr class="account-divider" />

      <h2 class="account-section-title" style="color: #a44a4a;">Delete account</h2>
      <p class="text-light-muted">
        Permanently delete your account and remove your personal data
        from our systems. Your past orders stay on file for legal /
        accounting reasons but are no longer linked to your name.
        <strong>This action is irreversible.</strong>
      </p>

      <p v-if="deleteMsg" class="account-banner" :class="deleteMsg.kind">{{ deleteMsg.text }}</p>

      <div v-if="!showDeleteForm" class="account-actions">
        <button class="account-btn-danger" @click="showDeleteForm = true">
          Delete my account
        </button>
      </div>

      <form v-else class="account-form" @submit.prevent="confirmDelete" style="max-width: 480px;">
        <p style="color: #a44a4a; font-size: 14px;">
          To confirm, type your password. Every other device will be
          signed out and your data scrubbed.
        </p>
        <div>
          <label>Password</label>
          <input
            class="form-input"
            type="password"
            v-model="deletePassword"
            autocomplete="current-password"
            required
          />
        </div>
        <div class="account-actions mt-2">
          <button
            class="account-btn-danger"
            :disabled="deleting"
            type="submit"
          >{{ deleting ? 'Deleting…' : 'Yes, delete my account' }}</button>
          <button
            class="account-btn-secondary"
            type="button"
            @click="cancelDelete"
            :disabled="deleting"
          >Cancel</button>
        </div>
      </form>
    </div>
  </AccountLayout>
</template>

<script>
import AccountLayout from './AccountLayout.vue';
import AccountSkeleton from './AccountSkeleton.vue';
import { getApiBase } from '../../config.js';

export default {
  name: 'AccountSettings',
  components: { AccountLayout, AccountSkeleton },
  emits: ['go-auth', 'logout', 'user-updated'],
  data() {
    return {
      authToken: localStorage.getItem('buttercup_token') || null,
      loading: false,

      profile: { name: '', email: '', phone: '' },
      profileMsg: null,
      savingProfile: false,

      // Replaces the old in-form change-password flow. We no longer hold
      // any password value in component state — the user clicks one
      // button and the rest happens via the email link.
      passwordMsg: null,
      sendingResetLink: false,

      // Account deletion confirmation state — hidden by default, opens
      // an inline form that requires the user to retype their password.
      showDeleteForm: false,
      deletePassword: '',
      deleting: false,
      deleteMsg: null,

      emailVerified:    null,
      resendingVerify:  false,
      verifyMsg:        null,
    };
  },
  created() {
    if (this.authToken) this.loadProfile();
  },
  methods: {
    async loadProfile() {
      this.loading = true;
      try {
        const res = await fetch(`${getApiBase()}/me`, {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.profileMsg = { kind: 'error', text: 'Could not load your profile.' };
          return;
        }
        const json = await res.json();
        const u = json.data ?? json;
        this.profile = {
          name:  u.name  ?? '',
          email: u.email ?? '',
          phone: u.phone ?? '',
        };
        this.emailVerified = typeof u.email_verified === 'boolean'
          ? u.email_verified
          : !!u.email_verified_at;
      } catch {
        this.profileMsg = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.loading = false;
      }
    },

    async resendVerification() {
      this.resendingVerify = true;
      this.verifyMsg = null;
      try {
        const res = await fetch(`${getApiBase()}/email/resend`, {
          method: 'POST',
          headers: {
            Accept:        'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (res.status === 429) {
          this.verifyMsg = { kind: 'error', text: 'Túl sok kérés — próbáld újra egy perc múlva.' };
          return;
        }
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.verifyMsg = { kind: 'error', text: json.message || 'Nem sikerült elküldeni a megerősítő e-mailt.' };
          return;
        }
        this.verifyMsg = { kind: 'success', text: json.message || 'Megerősítő e-mail elküldve.' };
      } catch {
        this.verifyMsg = { kind: 'error', text: 'Hálózati hiba — próbáld újra.' };
      } finally {
        this.resendingVerify = false;
      }
    },

    async saveProfile() {
      this.savingProfile = true;
      this.profileMsg    = null;
      try {
        const res = await fetch(`${getApiBase()}/me`, {
          method: 'PUT',
          headers: {
            'Content-Type':  'application/json',
            Accept:          'application/json',
            Authorization:   `Bearer ${this.authToken}`,
          },
          body: JSON.stringify({
            name:  this.profile.name,
            email: this.profile.email,
            phone: this.profile.phone || null,
          }),
        });
        if (res.status === 401) { this.handleAuthExpiry(); return; }
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
          this.profileMsg = { kind: 'error', text: this.firstError(json) || 'Could not save your profile.' };
          return;
        }

        const u = json.data ?? json;
        try {
          localStorage.setItem('buttercup_user', JSON.stringify(u));
        } catch {
        }
        this.$emit('user-updated', u);
        this.profileMsg = { kind: 'success', text: 'Profile updated.' };
      } catch {
        this.profileMsg = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.savingProfile = false;
      }
    },

    async requestPasswordResetLink() {
      this.sendingResetLink = true;
      this.passwordMsg = null;

      try {
        const res = await fetch(`${getApiBase()}/me/password-reset-link`, {
          method: 'POST',
          headers: {
            Accept:        'application/json',
            Authorization: `Bearer ${this.authToken}`,
          },
        });
        const json = await res.json().catch(() => ({}));

        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (res.status === 429) {
          this.passwordMsg = {
            kind: 'error',
            text: json.message || 'Túl sok kérés — próbáld újra egy perc múlva.',
          };
          return;
        }
        if (!res.ok) {
          this.passwordMsg = {
            kind: 'error',
            text: this.firstError(json) || 'Could not send the reset link.',
          };
          return;
        }

        this.passwordMsg = {
          kind: 'success',
          text: json.message
            || 'Reset link sent — please check your inbox in the next minute.',
        };
      } catch {
        this.passwordMsg = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.sendingResetLink = false;
      }
    },

    cancelDelete() {
      this.showDeleteForm = false;
      this.deletePassword = '';
      this.deleteMsg = null;
    },

    async confirmDelete() {
      if (!this.deletePassword) return;
      this.deleting  = true;
      this.deleteMsg = null;

      try {
        const res = await fetch(`${getApiBase()}/me`, {
          method: 'DELETE',
          headers: {
            'Content-Type':  'application/json',
            Accept:          'application/json',
            Authorization:   `Bearer ${this.authToken}`,
          },
          body: JSON.stringify({ password: this.deletePassword }),
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 401) { this.handleAuthExpiry(); return; }
        if (!res.ok) {
          this.deleteMsg = { kind: 'error', text: this.firstError(data) || 'Could not delete the account.' };
          return;
        }

        // Successful delete → force a logout locally. This also fires
        // a storage event in any other tab, signing them out too.
        try {
          localStorage.removeItem('buttercup_token');
          localStorage.removeItem('buttercup_user');
        } catch { /* private mode */ }
        this.authToken = null;
        this.$emit('logout');
      } catch {
        this.deleteMsg = { kind: 'error', text: 'Network error — please try again.' };
      } finally {
        this.deleting = false;
        this.deletePassword = '';
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
.account-divider {
  border: none;
  border-top: 1px solid rgba(156, 159, 136, 0.2);
  margin: 28px 0 24px 0;
}
.form-hint {
  font-size: 12px;
  color: var(--text-light);
  margin: 4px 0 0 0;
}
.text-light-muted { color: var(--text-light); font-size: 14px; }

.verify-banner {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  align-items: center;
  justify-content: space-between;
}
.verify-banner > div { flex: 1 1 260px; }
</style>
