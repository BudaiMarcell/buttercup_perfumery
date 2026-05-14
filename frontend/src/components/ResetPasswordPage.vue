<template>
  <!--
    Landing page for the password-reset link in the verification email.
    The Laravel broker generates the link with `token` and `email` query
    params. We trust those as opaque strings and POST them back to the
    /reset-password endpoint along with the new password.
  -->
  <div class="auth-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">

          <div class="auth-logo text-center mb-4">
            <span class="logo-mark">✦</span>
            <span class="logo-text">BUTTERCUP</span>
          </div>

          <div class="auth-form">
            <p class="auth-eyebrow text-center mb-4">Set a new password</p>

            <!-- Token came back missing or empty: the user landed here
                 without the email link, or the link got mangled. -->
            <div v-if="!hasToken" class="auth-warning text-center mb-4">
              <p class="mb-2">This reset link is invalid or incomplete.</p>
              <a href="#" @click.prevent="goToLogin">Back to sign in</a>
            </div>

            <template v-else-if="!completed">
              <p class="auth-help text-center mb-4">
                You're resetting the password for
                <strong>{{ email }}</strong>.
              </p>

              <div class="form-group mb-3">
                <label class="form-label-custom">New password</label>
                <div class="input-wrap">
                  <input
                    :type="showPassword ? 'text' : 'password'"
                    class="form-input w-100"
                    placeholder="••••••••"
                    v-model="form.password"
                    :class="{ 'input-error': errors.password }"
                  />
                  <button class="eye-btn" type="button" @click="showPassword = !showPassword">
                    <svg v-if="!showPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <p class="field-error" v-if="errors.password">{{ errors.password }}</p>
              </div>

              <div class="form-group mb-4">
                <label class="form-label-custom">Confirm new password</label>
                <div class="input-wrap">
                  <input
                    :type="showConfirm ? 'text' : 'password'"
                    class="form-input w-100"
                    placeholder="••••••••"
                    v-model="form.confirm"
                    :class="{ 'input-error': errors.confirm }"
                  />
                  <button class="eye-btn" type="button" @click="showConfirm = !showConfirm">
                    <svg v-if="!showConfirm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <p class="field-error" v-if="errors.confirm">{{ errors.confirm }}</p>
              </div>

              <p class="field-error text-center mb-3" v-if="submitError">{{ submitError }}</p>

              <button
                class="checkout-btn w-100 mb-4"
                @click="submit"
                :disabled="submitting"
              >{{ submitting ? 'Updating…' : 'Update password' }}</button>

              <p class="auth-switch text-center">
                Remembered it?
                <a href="#" @click.prevent="goToLogin">Back to sign in</a>
              </p>
            </template>

            <div v-else class="auth-success text-center">
              <span class="success-icon">✦</span>
              <p class="mt-2 mb-3">Password updated. You can sign in with your new password.</p>
              <button class="checkout-btn" @click="goToLogin">Go to sign in</button>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getApiBase } from '@/config.js';

export default {
  name: 'ResetPasswordPage',
  data() {
    return {
      // Pulled from query params on mount; treat as opaque strings.
      token: '',
      email: '',
      form: { password: '', confirm: '' },
      errors: {},
      submitError: '',
      submitting: false,
      completed: false,
      showPassword: false,
      showConfirm: false,
    };
  },
  computed: {
    hasToken() {
      return Boolean(this.token && this.email);
    },
  },
  created() {
    // Capture the link params once. Reading from $route.query keeps us in
    // sync with vue-router's history mode without manually parsing.
    const q = this.$route?.query || {};
    this.token = typeof q.token === 'string' ? q.token : '';
    this.email = typeof q.email === 'string' ? q.email : '';
  },
  methods: {
    goToLogin() {
      this.$router.push({ name: 'auth' });
    },
    async submit() {
      this.errors = {};
      this.submitError = '';

      const p = this.form.password;
      if (!p)                                     this.errors.password = 'Password is required.';
      else if (p.length < 10)                     this.errors.password = 'Password must be at least 10 characters.';
      else if (!/[a-z]/.test(p) || !/[A-Z]/.test(p))
                                                  this.errors.password = 'Password must include upper and lower case letters.';
      else if (!/[0-9]/.test(p))                  this.errors.password = 'Password must include a number.';
      else if (!/[^A-Za-z0-9]/.test(p))           this.errors.password = 'Password must include a symbol.';
      if (p !== this.form.confirm)                this.errors.confirm  = 'Passwords do not match.';
      if (Object.keys(this.errors).length) return;

      this.submitting = true;
      try {
        const response = await fetch(`${getApiBase()}/reset-password`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
          },
          body: JSON.stringify({
            token:                  this.token,
            email:                  this.email,
            password:               this.form.password,
            password_confirmation:  this.form.confirm,
          }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          // 422 == invalid token / weak password — show whatever the
          // server gave us so the user can either retry or request a
          // fresh link.
          this.submitError = data?.message
            || (response.status === 429
                ? 'Túl sok próbálkozás. Kérlek várj egy percet.'
                : 'Could not update the password. The link may have expired.');
          return;
        }

        // Cross-tab logout signal. The user almost certainly clicked the
        // email link in a NEW tab while still being signed in on an OLD
        // tab. The API has just invalidated every Sanctum token for the
        // user, so the old tab is now holding a dead token without
        // knowing it. Removing the keys here fires a `storage` event in
        // every other tab on this origin — App.vue listens for that
        // event and force-logs out without a manual refresh.
        try {
          localStorage.removeItem('buttercup_token');
          localStorage.removeItem('buttercup_user');
        } catch { /* private mode etc — non-fatal */ }

        this.completed = true;
      } catch {
        this.submitError = 'Nem sikerült elérni a szervert. Ellenőrizd az internetkapcsolatot.';
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<style scoped>
.auth-help {
  color: var(--text-light, #888);
  font-size: 0.92rem;
  line-height: 1.5;
}
.auth-warning {
  color: #b45309;
  font-size: 0.95rem;
}
</style>
