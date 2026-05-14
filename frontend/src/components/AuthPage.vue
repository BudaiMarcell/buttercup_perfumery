<template>
  <div class="auth-page">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-7 col-lg-5">


          <div class="auth-logo text-center mb-4">
            <span class="logo-mark">✦</span>
            <span class="logo-text">BUTTERCUP</span>
          </div>


          <div class="auth-tabs d-flex mb-5">
            <button
              class="auth-tab flex-grow-1"
              :class="{ active: activeTab === 'login' }"
              @click="activeTab = 'login'"
            >
              Sign In
            </button>
            <button
              class="auth-tab flex-grow-1"
              :class="{ active: activeTab === 'register' }"
              @click="activeTab = 'register'"
            >
              Register
            </button>
          </div>


          <transition name="fade-tab" mode="out-in">

            <!-- ── Forgot password sub-form ──────────────────────────── -->
            <!-- A third "tab" reachable only via the "Forgot password?"  -->
            <!-- link, kept inside the same transition so styling and     -->
            <!-- spacing match the login/register tabs exactly.            -->
            <div class="auth-form" key="forgot" v-if="activeTab === 'forgot'">
              <p class="auth-eyebrow text-center mb-4">Reset your password</p>

              <p class="auth-help text-center mb-4">
                Enter the email address associated with your account and we'll
                send you a link to set a new password.
              </p>

              <div class="form-group mb-4">
                <label class="form-label-custom">Email address</label>
                <input
                  type="email"
                  class="form-input w-100"
                  placeholder="example@email.com"
                  v-model="forgot.email"
                  :class="{ 'input-error': forgotErrors.email }"
                />
                <p class="field-error" v-if="forgotErrors.email">{{ forgotErrors.email }}</p>
              </div>

              <p class="field-error text-center mb-3" v-if="submitError && activeTab === 'forgot'">{{ submitError }}</p>

              <button
                class="checkout-btn w-100 mb-4"
                @click="submitForgot"
                :disabled="submitting"
              >{{ submitting ? 'Sending…' : 'Send reset link' }}</button>

              <p class="auth-switch text-center">
                Remembered it?
                <a href="#" @click.prevent="activeTab = 'login'">Back to sign in</a>
              </p>
            </div>

            <div class="auth-form" key="login" v-else-if="activeTab === 'login'">
              <p class="auth-eyebrow text-center mb-4">Welcome back</p>

              <div class="form-group mb-4">
                <label class="form-label-custom">Email address</label>
                <input
                  type="email"
                  class="form-input w-100"
                  placeholder="example@email.com"
                  v-model="login.email"
                  :class="{ 'input-error': loginErrors.email }"
                />
                <p class="field-error" v-if="loginErrors.email">{{ loginErrors.email }}</p>
              </div>

              <div class="form-group mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                  <label class="form-label-custom">Password</label>
                  <a href="#" class="forgot-link" @click.prevent="goToForgot">Forgot password?</a>
                </div>
                <div class="input-wrap">
                  <input
                    :type="showLoginPassword ? 'text' : 'password'"
                    class="form-input w-100"
                    placeholder="••••••••"
                    v-model="login.password"
                    :class="{ 'input-error': loginErrors.password }"
                  />
                  <button class="eye-btn" type="button" @click="showLoginPassword = !showLoginPassword">
                    <svg v-if="!showLoginPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <p class="field-error" v-if="loginErrors.password">{{ loginErrors.password }}</p>
              </div>

              <div class="d-flex align-items-center gap-2 mb-4">
                <input type="checkbox" id="rememberMe" v-model="login.remember" class="auth-checkbox" />
                <label for="rememberMe" class="remember-label">Remember me</label>
              </div>

              <p class="field-error text-center mb-3" v-if="submitError && activeTab === 'login'">{{ submitError }}</p>

              <button
                class="checkout-btn w-100 mb-4"
                @click="submitLogin"
                :disabled="submitting"
              >{{ submitting ? 'Signing in…' : 'Sign In' }}</button>

              <p class="auth-switch text-center">
                Don't have an account?
                <a href="#" @click.prevent="activeTab = 'register'">Register here</a>
              </p>
            </div>


            <div class="auth-form" key="register" v-else>
              <p class="auth-eyebrow text-center mb-4">Create your account</p>

              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label class="form-label-custom">First name</label>
                  <input
                    type="text"
                    class="form-input w-100"
                    placeholder="Sanyi"
                    v-model="register.firstName"
                    :class="{ 'input-error': registerErrors.firstName }"
                  />
                  <p class="field-error" v-if="registerErrors.firstName">{{ registerErrors.firstName }}</p>
                </div>
                <div class="col-6">
                  <label class="form-label-custom">Last name</label>
                  <input
                    type="text"
                    class="form-input w-100"
                    placeholder="Kovács"
                    v-model="register.lastName"
                    :class="{ 'input-error': registerErrors.lastName }"
                  />
                  <p class="field-error" v-if="registerErrors.lastName">{{ registerErrors.lastName }}</p>
                </div>
              </div>

              <div class="form-group mb-3">
                <label class="form-label-custom">Email address</label>
                <input
                  type="email"
                  class="form-input w-100"
                  placeholder="example@email.com"
                  v-model="register.email"
                  :class="{ 'input-error': registerErrors.email }"
                />
                <p class="field-error" v-if="registerErrors.email">{{ registerErrors.email }}</p>
              </div>

              <div class="form-group mb-3">
                <label class="form-label-custom">Password</label>
                <div class="input-wrap">
                  <input
                    :type="showRegPassword ? 'text' : 'password'"
                    class="form-input w-100"
                    placeholder="••••••••"
                    v-model="register.password"
                    :class="{ 'input-error': registerErrors.password }"
                  />
                  <button class="eye-btn" type="button" @click="showRegPassword = !showRegPassword">
                    <svg v-if="!showRegPassword" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <p class="field-error" v-if="registerErrors.password">{{ registerErrors.password }}</p>

                <div class="strength-bar mt-2" v-if="register.password">
                  <div
                    class="strength-fill"
                    :style="{ width: passwordStrength.fill + '%' }"
                    :class="passwordStrength.cls"
                  ></div>
                </div>
                <p class="strength-label" v-if="register.password">{{ passwordStrength.label }}</p>
              </div>

              <div class="form-group mb-4">
                <label class="form-label-custom">Confirm password</label>
                <div class="input-wrap">
                  <input
                    :type="showRegConfirm ? 'text' : 'password'"
                    class="form-input w-100"
                    placeholder="••••••••"
                    v-model="register.confirm"
                    :class="{ 'input-error': registerErrors.confirm }"
                  />
                  <button class="eye-btn" type="button" @click="showRegConfirm = !showRegConfirm">
                    <svg v-if="!showRegConfirm" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg v-else width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                  </button>
                </div>
                <p class="field-error" v-if="registerErrors.confirm">{{ registerErrors.confirm }}</p>
              </div>

              <div class="d-flex align-items-start gap-2 mb-4">
                <input type="checkbox" id="agreeTerms" v-model="register.agreed" class="auth-checkbox mt-1" />
                <label for="agreeTerms" class="remember-label">
                  I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
              </div>

              <p class="field-error text-center mb-3" v-if="submitError && activeTab === 'register'">{{ submitError }}</p>

              <button
                class="checkout-btn w-100 mb-4"
                @click="submitRegister"
                :disabled="submitting"
              >{{ submitting ? 'Creating account…' : 'Create Account' }}</button>

              <p class="auth-switch text-center">
                Already have an account?
                <a href="#" @click.prevent="activeTab = 'login'">Sign in here</a>
              </p>
            </div>
          </transition>


          <transition name="fade-tab">
            <div class="auth-success text-center mt-4" v-if="successMessage">
              <span class="success-icon">✦</span>
              <p class="mt-2 mb-0">{{ successMessage }}</p>
            </div>
          </transition>

        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { getApiBase } from '@/config.js';

export default {
  name: 'AuthPage',
  emits: ['go-back', 'auth-success'],
  data() {
    return {
      activeTab: 'login',
      showLoginPassword: false,
      showRegPassword: false,
      showRegConfirm: false,
      successMessage: '',
      submitError: '',
      submitting: false,

      login: { email: '', password: '', remember: false },
      loginErrors: {},

      register: { firstName: '', lastName: '', email: '', password: '', confirm: '', agreed: false },
      registerErrors: {},

      forgot: { email: '' },
      forgotErrors: {},
    };
  },
  computed: {
    passwordStrength() {
      const p = this.register.password;
      if (!p) return { pct: 0,fill:0, label: '', cls: '' };
      let score = 0;
      if (p.length >= 8)          score++;
      if (/[A-Z]/.test(p))        score++;
      if (/[0-9]/.test(p))        score++;
      if (/[^A-Za-z0-9]/.test(p)) score++;
      const map = [
        { pct: 9,  label: 'Weak', fill:25,   cls: 'strength-weak' },
        { pct: 11,  label: 'Fair', fill:50,    cls: 'strength-fair' },
        { pct: 13,  label: 'Good', fill:75,   cls: 'strength-good' },
        { pct: 18, label: 'Strong', fill:100,  cls: 'strength-strong' },
      ];
      return map[score - 1] || { pct: 8, label: 'Too short', cls: 'strength-weak' };
    },
  },
  methods: {

    persistAuth(token, user) {
      try {
        localStorage.setItem('buttercup_token', token);
        localStorage.setItem('buttercup_user', JSON.stringify(user));
      } catch {
      }
    },

    /**
     * Switch from the login tab into the forgot-password sub-form, while
     * pre-filling the email field so the user doesn't retype what they
     * just entered for the login attempt.
     */
    goToForgot() {
      this.forgot.email   = this.login.email || '';
      this.forgotErrors   = {};
      this.submitError    = '';
      this.successMessage = '';
      this.activeTab      = 'forgot';
    },

    async submitForgot() {
      this.forgotErrors   = {};
      this.submitError    = '';
      this.successMessage = '';

      if (!this.forgot.email) {
        this.forgotErrors.email = 'Email is required.';
      } else if (!/\S+@\S+\.\S+/.test(this.forgot.email)) {
        this.forgotErrors.email = 'Please enter a valid email.';
      }
      if (Object.keys(this.forgotErrors).length) return;

      this.submitting = true;
      try {
        const response = await fetch(`${getApiBase()}/forgot-password`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
          },
          body: JSON.stringify({ email: this.forgot.email }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          this.submitError = this.extractError(data, response.status);
          return;
        }

        // The API returns the same generic message whether or not the
        // address exists (anti-enumeration), so we just relay it.
        this.successMessage = data.message
          || 'If an account exists for this email, a reset link has been sent.';
      } catch {
        this.submitError = 'Nem sikerült elérni a szervert. Ellenőrizd az internetkapcsolatot.';
      } finally {
        this.submitting = false;
      }
    },


    extractError(payload, status) {
      if (status === 429) return 'Túl sok próbálkozás. Kérlek várj egy percet.';
      if (payload?.message) return payload.message;
      if (payload?.errors) {
        const first = Object.values(payload.errors).flat()[0];
        if (first) return first;
      }
      return 'Ismeretlen hiba történt. Próbáld újra később.';
    },

    async submitLogin() {
      this.loginErrors = {};
      this.submitError = '';
      this.successMessage = '';

      if (!this.login.email)    this.loginErrors.email    = 'Email is required.';
      else if (!/\S+@\S+\.\S+/.test(this.login.email)) this.loginErrors.email = 'Please enter a valid email.';
      if (!this.login.password) this.loginErrors.password = 'Password is required.';
      if (Object.keys(this.loginErrors).length) return;

      this.submitting = true;
      try {
        const response = await fetch(`${getApiBase()}/login`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
          },
          body: JSON.stringify({
            email:    this.login.email,
            password: this.login.password,
          }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          // 429 with `locked: true` is the server's "you've exhausted
          // your attempts, cool off" response. Surface that clearly so
          // the user knows it's not just a typo.
          if (response.status === 429 && data?.locked) {
            this.submitError = data.message
              || 'Too many failed attempts. Please wait and try again.';
            return;
          }
          this.submitError = this.extractError(data, response.status);
          return;
        }

        if (data.token && data.user) this.persistAuth(data.token, data.user);
        this.successMessage = 'Signed in successfully. Welcome back!';
        this.$emit('auth-success', data.user);
      } catch {
        this.submitError = 'Nem sikerült elérni a szervert. Ellenőrizd az internetkapcsolatot.';
      } finally {
        this.submitting = false;
      }
    },

    async submitRegister() {
      this.registerErrors = {};
      this.submitError = '';
      this.successMessage = '';

      if (!this.register.firstName) this.registerErrors.firstName = 'First name is required.';
      if (!this.register.lastName)  this.registerErrors.lastName  = 'Last name is required.';
      if (!this.register.email)     this.registerErrors.email     = 'Email is required.';
      else if (!/\S+@\S+\.\S+/.test(this.register.email)) this.registerErrors.email = 'Please enter a valid email.';
      if (!this.register.password)  this.registerErrors.password  = 'Password is required.';
      else if (this.register.password.length < 10) this.registerErrors.password = 'Password must be at least 10 characters.';
      else if (!/[a-z]/.test(this.register.password) || !/[A-Z]/.test(this.register.password))
        this.registerErrors.password = 'Password must include upper and lower case letters.';
      else if (!/[0-9]/.test(this.register.password))
        this.registerErrors.password = 'Password must include a number.';
      else if (!/[^A-Za-z0-9]/.test(this.register.password))
        this.registerErrors.password = 'Password must include a symbol.';
      if (this.register.password !== this.register.confirm) this.registerErrors.confirm = 'Passwords do not match.';
      if (!this.register.agreed)    this.registerErrors.agreed    = true;
      if (Object.keys(this.registerErrors).length) return;

      this.submitting = true;
      try {
        const response = await fetch(`${getApiBase()}/register`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
          },
          body: JSON.stringify({
            name:                   `${this.register.firstName} ${this.register.lastName}`.trim(),
            email:                  this.register.email,
            password:               this.register.password,
            password_confirmation:  this.register.confirm,
          }),
        });
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          this.submitError = this.extractError(data, response.status);
          return;
        }

        if (data.token && data.user) this.persistAuth(data.token, data.user);
        // Backend has queued the verification email — gently nudge the
        // user to go check their inbox before they keep going.
        this.successMessage = 'Account created! Please check your inbox to verify your email address.';
        this.$emit('auth-success', data.user);
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
/* Helper text used by the forgot-password sub-form. Inherits the same
   muted tone as other secondary copy on the auth page. */
.auth-help {
  color: var(--text-light, #888);
  font-size: 0.92rem;
  line-height: 1.5;
}
</style>