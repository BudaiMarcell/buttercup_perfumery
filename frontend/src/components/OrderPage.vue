<template>
  <div class="order-page">
    <div class="container-xl">

      <!-- mb-3 (16px) instead of mb-5 (48px): we want the order
           summary card on the right to be visible above the fold
           when the page loads. -->
      <div class="order-header d-flex align-items-center gap-3 mb-3">
        <button class="back-btn d-flex align-items-center gap-2" @click="$emit('go-back')">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="15 18 9 12 15 6"/></svg>
          Back
        </button>
        <div class="order-header-divider"></div>
        <p class="section-eyebrow mb-0">— Checkout</p>
      </div>

      <div class="guest-notice mb-4" v-if="!authUser">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        You are checking out as a guest. <a href="#" @click.prevent="$emit('go-auth')">Sign in</a> to save your order history and addresses.
      </div>

      <div class="row g-5">

        <div class="col-12 col-lg-7">

          <div class="order-section mb-4">
            <h3 class="order-section-title">
              <span class="step-num">1</span> Contact Information
            </h3>
            <div class="row g-3">
              <!-- col-12 col-sm-6 instead of plain col-6: first+last name
                   stack vertically below 576px (small phones) so neither
                   gets cramped under 160px wide. -->
              <div class="col-12 col-sm-6">
                <label class="form-label-custom">First name</label>
                <input type="text" class="form-input w-100" placeholder="Sanyi" v-model="form.firstName" :class="{ 'input-error': errors.firstName }" />
                <p class="field-error" v-if="errors.firstName">{{ errors.firstName }}</p>
              </div>
              <div class="col-12 col-sm-6">
                <label class="form-label-custom">Last name</label>
                <input type="text" class="form-input w-100" placeholder="Kovács" v-model="form.lastName" :class="{ 'input-error': errors.lastName }" />
                <p class="field-error" v-if="errors.lastName">{{ errors.lastName }}</p>
              </div>
              <div class="col-12">
                <label class="form-label-custom">Email address</label>
                <input type="email" class="form-input w-100" placeholder="example@email.com" v-model="form.email" :class="{ 'input-error': errors.email }" />
                <p class="field-error" v-if="errors.email">{{ errors.email }}</p>
              </div>
              <div class="col-12">
                <label class="form-label-custom">Phone number</label>
                <input type="tel" class="form-input w-100" placeholder="+36 70 123 4567" v-model="form.phone" />
              </div>
            </div>
          </div>

          <div class="order-section mb-4">
            <h3 class="order-section-title">
              <span class="step-num">2</span> Shipping Address
            </h3>

            <div v-if="savedAddresses.length" class="saved-list mb-3">
              <label
                v-for="addr in savedAddresses"
                :key="addr.id"
                class="saved-option"
                :class="{ selected: selectedAddressId === addr.id }"
              >
                <input
                  type="radio"
                  name="addr-pick"
                  :value="addr.id"
                  :checked="selectedAddressId === addr.id"
                  @change="applyAddress(addr)"
                />
                <div class="flex-grow-1">
                  <p class="saved-option-title mb-0">
                    {{ addr.label }}
                    <span v-if="addr.is_default" class="saved-default-pill">Default</span>
                  </p>
                  <p class="saved-option-meta mb-0">{{ addr.full_address }} · {{ addr.country }}</p>
                </div>
              </label>
              <label
                class="saved-option saved-option-new"
                :class="{ selected: selectedAddressId === null }"
              >
                <input
                  type="radio"
                  name="addr-pick"
                  :value="null"
                  :checked="selectedAddressId === null"
                  @change="useNewAddress"
                />
                <div class="flex-grow-1">
                  <p class="saved-option-title mb-0">+ Use a new address</p>
                </div>
              </label>
            </div>

            <div class="row g-3" v-show="selectedAddressId === null">
              <div class="col-12">
                <label class="form-label-custom">Address label (optional)</label>
                <input type="text" class="form-input w-100" placeholder="e.g. Home, Work" v-model="form.label" />
              </div>
              <div class="col-12">
                <label class="form-label-custom">Street address</label>
                <input type="text" class="form-input w-100" placeholder="" v-model="form.street" :class="{ 'input-error': errors.street }" />
                <p class="field-error" v-if="errors.street">{{ errors.street }}</p>
              </div>
              <div class="col-12">
                <label class="form-label-custom">Apartment, suite, etc. (optional)</label>
                <input type="text" class="form-input w-100" placeholder="" v-model="form.apartment" />
              </div>
              <div class="col-5">
                <label class="form-label-custom">Postal code</label>
                <input type="text" class="form-input w-100" placeholder="" v-model="form.zip" :class="{ 'input-error': errors.zip }" />
                <p class="field-error" v-if="errors.zip">{{ errors.zip }}</p>
              </div>
              <div class="col-7">
                <label class="form-label-custom">City</label>
                <input type="text" class="form-input w-100" placeholder="" v-model="form.city" :class="{ 'input-error': errors.city }" />
                <p class="field-error" v-if="errors.city">{{ errors.city }}</p>
              </div>
              <div class="col-12">
                <label class="form-label-custom">Country</label>
                <select class="form-input w-100" v-model="form.country">
                  <option value="HU">Hungary</option>
                  <option value="AT">Austria</option>
                  <option value="DE">Germany</option>
                  <option value="FR">France</option>
                  <option value="IT">Italy</option>
                  <option value="SK">Slovakia</option>
                  <option value="RO">Romania</option>
                  <option value="Other">Other</option>
                </select>
              </div>
            </div>
          </div>

          <div class="order-section mb-4">
            <h3 class="order-section-title">
              <span class="step-num">3</span> Shipping Method
            </h3>
            <div class="d-flex flex-column gap-2">
              <label
                class="shipping-option d-flex align-items-center gap-3"
                :class="{ selected: form.shipping === opt.id }"
                v-for="opt in shippingOptions"
                :key="opt.id"
              >
                <input type="radio" :value="opt.id" v-model="form.shipping" class="auth-checkbox" />
                <div class="flex-grow-1">
                  <p class="mb-0 shipping-name">{{ opt.name }}</p>
                  <p class="mb-0 shipping-days">{{ opt.days }}</p>
                </div>
                <span class="shipping-price">{{ opt.price === 0 ? 'Free' : '€' + opt.price }}</span>
              </label>
            </div>
          </div>

          <div class="order-section mb-4">
            <h3 class="order-section-title">
              <span class="step-num">4</span> Payment
            </h3>

            <div class="payment-tabs d-flex gap-2 mb-4">
              <button
                class="payment-tab"
                :class="{ active: form.paymentMethod === 'card' }"
                @click="form.paymentMethod = 'card'"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                Card
              </button>
              <button
                class="payment-tab"
                :class="{ active: form.paymentMethod === 'bank' }"
                @click="form.paymentMethod = 'bank'"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                Bank Transfer
              </button>
            </div>

            <div v-if="form.paymentMethod === 'card'">

              <div v-if="savedCards.length" class="saved-list mb-3">
                <label
                  v-for="card in savedCards"
                  :key="card.id"
                  class="saved-option"
                  :class="{ selected: selectedCardId === card.id }"
                >
                  <input
                    type="radio"
                    name="card-pick"
                    :value="card.id"
                    :checked="selectedCardId === card.id"
                    @change="applyCard(card)"
                  />
                  <div class="flex-grow-1">
                    <p class="saved-option-title mb-0">
                      {{ brandLabel(card.brand) }} •••• {{ card.last_four }}
                      <span v-if="card.is_default" class="saved-default-pill">Default</span>
                    </p>
                    <p class="saved-option-meta mb-0">Expires {{ card.expiry }}</p>
                  </div>
                </label>
                <label
                  class="saved-option saved-option-new"
                  :class="{ selected: selectedCardId === null }"
                >
                  <input
                    type="radio"
                    name="card-pick"
                    :value="null"
                    :checked="selectedCardId === null"
                    @change="useNewCard"
                  />
                  <div class="flex-grow-1">
                    <p class="saved-option-title mb-0">+ Use a new card</p>
                  </div>
                </label>
              </div>

              <div class="row g-3">
                <div class="col-12" v-if="selectedCardId === null">
                  <label class="form-label-custom">Card number</label>
                  <input type="text" class="form-input w-100" placeholder="0000 0000 0000 0000" v-model="form.cardNumber" maxlength="19" @input="formatCard" :class="{ 'input-error': errors.cardNumber }" />
                  <p class="field-error" v-if="errors.cardNumber">{{ errors.cardNumber }}</p>
                </div>
                <div class="col-12" v-if="selectedCardId === null">
                  <label class="form-label-custom">Cardholder name</label>
                  <input type="text" class="form-input w-100" placeholder="SANYI KOVÁCS" v-model="form.cardName" :class="{ 'input-error': errors.cardName }" />
                  <p class="field-error" v-if="errors.cardName">{{ errors.cardName }}</p>
                </div>
                <div class="col-6" v-if="selectedCardId === null">
                  <label class="form-label-custom">Expiry date</label>
                  <input type="text" class="form-input w-100" placeholder="MM / YY" v-model="form.cardExpiry" maxlength="7" @input="formatExpiry" :class="{ 'input-error': errors.cardExpiry }" />
                  <p class="field-error" v-if="errors.cardExpiry">{{ errors.cardExpiry }}</p>
                </div>
                <div class="col-6">
                  <label class="form-label-custom">CVV</label>
                  <input type="password" class="form-input w-100" placeholder="•••" v-model="form.cardCvv" maxlength="4" :class="{ 'input-error': errors.cardCvv }" />
                  <p class="field-error" v-if="errors.cardCvv">{{ errors.cardCvv }}</p>
                </div>
              </div>

              <label
                v-if="selectedCardId === null && authToken"
                class="d-flex align-items-start gap-2 mt-3 save-card-toggle"
              >
                <input type="checkbox" class="auth-checkbox mt-1" v-model="saveNewCard" />
                <span>
                  Save this card for future checkouts.
                  <span class="save-card-hint d-block">
                    Only the brand, last 4 digits, and expiry are stored — the full
                    number and CVV are never saved and you'll re-enter the CVV next time.
                  </span>
                </span>
              </label>
            </div>

            <div v-else class="bank-info">
              <p class="bank-info-line"><span>Bank</span><span>OTP Bank Nyrt.</span></p>
              <p class="bank-info-line"><span>Account name</span><span>Buttercup Kft.</span></p>
              <p class="bank-info-line"><span>IBAN</span><span>HU42 1177 3016 1111 1018 0000 0000</span></p>
              <p class="bank-info-line"><span>Reference</span><span>Your order number (sent by email)</span></p>
              <p class="bank-note mt-3">Your order will be processed once the transfer is confirmed, typically within 1–2 business days.</p>
            </div>
          </div>

          <div class="order-section mb-4">
            <h3 class="order-section-title">
              <span class="step-num">5</span> Order Notes
              <span class="notes-optional">(optional)</span>
            </h3>
            <div>
              <label class="form-label-custom">Any special requests or delivery instructions?</label>
              <textarea
                class="form-input w-100 notes-textarea"
                placeholder="e.g. Leave at the door, gift wrapping requested, ring bell twice…"
                v-model="form.notes"
                rows="4"
              ></textarea>
            </div>
          </div>

          <p class="field-error mb-3" v-if="submitError">{{ submitError }}</p>

          <button class="checkout-btn w-100 place-order-btn" @click="placeOrder" :disabled="submitting">
            {{ submitting ? 'Placing order…' : `Place Order · €${orderTotal}` }}
          </button>

        </div>

        <div class="col-12 col-lg-5">
          <div class="order-summary">
            <h3 class="order-section-title mb-4">Order Summary</h3>

            <div class="summary-items mb-4">
              <div class="summary-item d-flex align-items-center gap-3" v-for="item in uniqueCartItems" :key="item.id">
                <div class="summary-item-img d-flex align-items-center justify-content-center flex-shrink-0" :style="{ background: item.color }">
                  <span>{{ item.icon }}</span>
                  <span class="item-qty-badge">{{ itemQuantity(item.id) }}</span>
                </div>
                <div class="flex-grow-1">
                  <p class="item-name mb-0">{{ item.name }}</p>
                  <p class="item-vol mb-0">{{ item.volume }}</p>
                </div>
                <span class="item-price">€{{ item.price * itemQuantity(item.id) }}</span>
              </div>
            </div>

            <div class="coupon-row d-flex gap-2 mb-4">
              <input type="text" class="form-input flex-grow-1" placeholder="Coupon code" v-model="couponCode" />
              <button class="coupon-btn" @click="applyCoupon">Apply</button>
            </div>
            <p class="coupon-msg" v-if="couponMsg" :class="couponValid ? 'coupon-ok' : 'coupon-err'">{{ couponMsg }}</p>

            <div class="summary-totals">
              <div class="total-line d-flex justify-content-between">
                <span>Subtotal</span>
                <span>€{{ cartTotal }}</span>
              </div>
              <div class="total-line d-flex justify-content-between" v-if="couponValid">
                <span>Discount (10%)</span>
                <span class="discount-val">−€{{ discount }}</span>
              </div>
              <div class="total-line d-flex justify-content-between">
                <span>Shipping</span>
                <span>{{ selectedShipping.price === 0 ? 'Free' : '€' + selectedShipping.price }}</span>
              </div>
              <div class="total-line total-final d-flex justify-content-between">
                <span>Total</span>
                <span>€{{ orderTotal }}</span>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <transition name="fade-tab">
      <div class="confirm-modal-backdrop" v-if="orderPlaced">
        <div class="confirm-modal text-center">
          <span class="confirm-icon">✦</span>
          <h3 class="confirm-title">Order Confirmed!</h3>
          <p class="confirm-sub">Thank you, {{ form.firstName }}. Your order has been placed and a confirmation has been sent to <strong>{{ form.email }}</strong>.</p>
          <p class="confirm-number">Order #BOG-{{ orderNumber }}</p>
          <button class="checkout-btn mt-4" @click="$emit('go-back')">Back to Shop</button>
        </div>
      </div>
    </transition>

  </div>
</template>

<script>
import { getApiBase } from '@/config.js';

export default {
  name: 'OrderPage',
  emits: ['go-back', 'go-auth', 'order-placed'],
  props: {
    cartItems: { type: Array, required: true },
    cartTotal: { type: Number, required: true },
  },
  data() {
    const stored = localStorage.getItem('buttercup_user');
    const user = stored ? JSON.parse(stored) : null;
    const nameParts = user?.name ? user.name.split(' ') : [];

    return {
      authUser: user,
      authToken: localStorage.getItem('buttercup_token') || null,

      savedAddresses: [],
      savedCards:     [],
      selectedAddressId: null,
      selectedCardId:    null,
      saveNewCard: false,

      orderPlaced: false,
      orderNumber: '',
      couponCode: '',
      couponMsg: '',
      couponValid: false,
      // Filled by applyCoupon() from the /coupons/validate response.
      // Read by the `discount` computed property. Defaults are safe
      // because we only read these when couponValid === true.
      couponDiscountType:  'percentage',
      couponDiscountValue: 0,
      applyingCoupon: false,
      submitting: false,
      submitError: '',

      form: {
        firstName: nameParts[0] || '',
        lastName:  nameParts.slice(1).join(' ') || '',
        email:     user?.email || '',
        phone:     user?.phone || '',
        label:     'Home',
        street:    '',
        apartment: '',
        zip:       '',
        city:      '',
        country:   'HU',
        shipping:       'standard',
        paymentMethod:  'card',
        cardNumber: '', cardName: '', cardExpiry: '', cardCvv: '',
        notes: '',
      },
      errors: {},

      shippingOptions: [
        { id: 'standard', name: 'Standard Delivery',  days: '3–5 business days', price: 8  },
        { id: 'express',  name: 'Express Delivery',   days: '1–2 business days', price: 18 },
        { id: 'free',     name: 'Free Shipping',       days: '5–7 business days', price: 0  },
      ],
    };
  },
  computed: {
    selectedShipping() {
      return this.shippingOptions.find(o => o.id === this.form.shipping) || this.shippingOptions[0];
    },
    discount() {
      if (!this.couponValid) return 0;
      // Apply the actual discount type/value returned by the API,
      // not a hardcoded 10%. Percentage gets rounded so the displayed
      // total stays whole-euro. Fixed-amount is capped at cartTotal
      // so a 100 Ft discount on a 50 Ft cart can't go negative.
      if (this.couponDiscountType === 'percentage') {
        return Math.round(this.cartTotal * (this.couponDiscountValue / 100));
      }
      return Math.min(Math.round(this.couponDiscountValue), this.cartTotal);
    },
    orderTotal() {
      return this.cartTotal - this.discount + this.selectedShipping.price;
    },
    uniqueCartItems() {
      const seen = new Set();
      return this.cartItems.filter(item => {
        if (seen.has(item.id)) return false;
        seen.add(item.id);
        return true;
      });
    },
  },
  async created() {
    if (this.authToken) {
      const [addrs, cards] = await Promise.all([
        this.fetchSavedAddresses(),
        this.fetchSavedCards(),
      ]);
      this.savedAddresses = addrs;
      this.savedCards     = cards;

      const addr = addrs.find(a => a.is_default) ?? addrs[0] ?? null;
      if (addr) this.applyAddress(addr);
      else      this.useNewAddress();

      const card = cards.find(c => c.is_default) ?? cards[0] ?? null;
      if (card) this.applyCard(card);
      else      this.useNewCard();
    }
  },

  methods: {
    itemQuantity(id) {
      return this.cartItems.filter(i => i.id === id).length;
    },

    // ── Saved address helpers ─────────────────────────────────────────────
    async fetchSavedAddresses() {
      try {
        const res = await fetch(`${getApiBase()}/addresses`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${this.authToken}` },
        });
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        return [];
      }
    },
    applyAddress(addr) {
      this.selectedAddressId = addr.id;
      this.form.label     = addr.label   ?? '';
      this.form.country   = addr.country ?? this.form.country;
      this.form.city      = addr.city    ?? '';
      this.form.zip       = addr.zip_code ?? '';
      this.form.street    = addr.street  ?? '';
      this.form.apartment = '';
    },
    useNewAddress() {
      this.selectedAddressId = null;
      this.form.label     = 'Home';
      this.form.street    = '';
      this.form.apartment = '';
      this.form.zip       = '';
      this.form.city      = '';
    },

    // ── Saved card helpers ────────────────────────────────────────────────
    async fetchSavedCards() {
      try {
        const res = await fetch(`${getApiBase()}/payment-methods`, {
          headers: { Accept: 'application/json', Authorization: `Bearer ${this.authToken}` },
        });
        if (!res.ok) return [];
        const json = await res.json();
        return Array.isArray(json) ? json : (json.data ?? []);
      } catch {
        return [];
      }
    },
    applyCard(card) {
      this.selectedCardId   = card.id;
      this.saveNewCard      = false;
      this.form.cardCvv     = '';
    },
    useNewCard() {
      this.selectedCardId = null;
    },
    brandLabel(brand) {
      const map = { visa: 'Visa', mastercard: 'Mastercard', amex: 'American Express', discover: 'Discover' };
      const key = (brand || '').toLowerCase();
      return map[key] || brand || 'Card';
    },
    detectBrand(rawNumber) {
      const n = (rawNumber || '').replace(/\D/g, '');
      if (/^4/.test(n))                           return 'visa';
      if (/^(5[1-5]|2[2-7])/.test(n))             return 'mastercard';
      if (/^3[47]/.test(n))                       return 'amex';
      if (/^(6011|65|64[4-9]|622)/.test(n))       return 'discover';
      return 'card';
    },
    formatCard() {
      this.form.cardNumber = this.form.cardNumber
        .replace(/\D/g, '').substring(0, 16)
        .replace(/(.{4})/g, '$1 ').trim();
    },
    formatExpiry() {
      let v = this.form.cardExpiry.replace(/\D/g, '').substring(0, 4);
      if (v.length >= 3) v = v.substring(0, 2) + ' / ' + v.substring(2);
      this.form.cardExpiry = v;
    },
    async applyCoupon() {
      const code = this.couponCode.trim();
      if (!code) return;
      if (this.applyingCoupon) return;

      this.applyingCoupon = true;
      this.couponMsg = '';

      try {
        const res = await fetch(`${getApiBase()}/coupons/validate`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept:         'application/json',
          },
          body: JSON.stringify({ code }),
        });
        const data = await res.json().catch(() => ({}));

        if (res.status === 429) {
          this.couponValid = false;
          this.couponMsg = 'Túl sok próbálkozás — várj egy percet.';
          return;
        }
        if (!res.ok) {
          this.couponValid = false;
          this.couponMsg = data?.message || 'Could not validate the coupon.';
          return;
        }

        if (data.valid) {
          this.couponValid         = true;
          this.couponDiscountType  = data.discount_type;
          this.couponDiscountValue = Number(data.discount_value) || 0;
          this.couponMsg           = data.message || 'Discount applied!';
        } else {
          this.couponValid = false;
          this.couponMsg   = data.message || 'Invalid or expired code.';
        }
      } catch {
        this.couponValid = false;
        this.couponMsg = 'Network error — please try again.';
      } finally {
        this.applyingCoupon = false;
      }
    },
    validate() {
      const e = {};
      if (!this.form.firstName) e.firstName = 'Required.';
      if (!this.form.lastName)  e.lastName  = 'Required.';
      if (!this.form.email || !/\S+@\S+\.\S+/.test(this.form.email)) e.email = 'Valid email required.';

      if (this.selectedAddressId === null) {
        if (!this.form.street)    e.street    = 'Required.';
        if (!this.form.zip)       e.zip       = 'Required.';
        if (!this.form.city)      e.city      = 'Required.';
      }

      if (this.form.paymentMethod === 'card') {
        if (this.selectedCardId === null) {
          if (!this.form.cardNumber || this.form.cardNumber.replace(/\s/g, '').length < 16) e.cardNumber = 'Enter a valid 16-digit card number.';
          if (!this.form.cardName)   e.cardName   = 'Required.';
          if (!this.form.cardExpiry || this.form.cardExpiry.length < 7) e.cardExpiry = 'Enter MM / YY.';
        }
        if (!this.form.cardCvv || this.form.cardCvv.length < 3) e.cardCvv = 'Enter 3–4 digit CVV.';
      }
      this.errors = e;
      return Object.keys(e).length === 0;
    },

    async maybeSaveNewCard() {
      if (!this.saveNewCard || this.selectedCardId !== null) return;

      const [mmRaw, yyRaw] = this.form.cardExpiry.split('/').map(s => s.trim());
      const mm = parseInt(mmRaw, 10);
      const yy = parseInt(yyRaw, 10);
      if (Number.isNaN(mm) || Number.isNaN(yy)) {
        console.warn('[save-card] could not parse expiry:', this.form.cardExpiry);
        return;
      }
      const yyyy = 2000 + yy;

      const last4 = this.form.cardNumber.replace(/\D/g, '').slice(-4);
      if (last4.length !== 4) {
        console.warn('[save-card] card number too short to derive last4');
        return;
      }

      const payload = {
        brand:      this.detectBrand(this.form.cardNumber),
        last_four:  last4,
        exp_month:  mm,
        exp_year:   yyyy,
        is_default: this.savedCards.length === 0,
      };

      try {
        const res = await fetch(`${getApiBase()}/payment-methods`, {
          method: 'POST',
          headers: {
            'Content-Type':  'application/json',
            'Accept':        'application/json',
            'Authorization': `Bearer ${this.authToken}`,
          },
          body: JSON.stringify(payload),
        });
        if (!res.ok) {
          const body = await res.json().catch(() => ({}));
          console.warn(`[save-card] POST /payment-methods → ${res.status}`, body);
        }
      } catch (err) {
        console.warn('[save-card] network error:', err?.message ?? err);
      }
    },

    async placeOrder() {
      if (!this.authToken) {
        this.submitError = 'Please sign in to place an order.';
        this.$emit('go-auth');
        return;
      }

      if (!this.validate()) {
        this.$nextTick(() => {
          const el = document.querySelector('.input-error');
          if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
        return;
      }

      this.submitting  = true;
      this.submitError = '';

      try {
        // ── 1. Resolve address_id ──
        let addressId = this.selectedAddressId;

        if (addressId === null && this.authToken) {
          const addrPayload = {
            label:      this.form.label || 'Home',
            country:    this.form.country,
            city:       this.form.city,
            zip_code:   this.form.zip,
            street:     this.form.apartment
                          ? `${this.form.street}, ${this.form.apartment}`
                          : this.form.street,
            is_default: false,
          };

          const addrRes = await fetch(`${getApiBase()}/addresses`, {
            method: 'POST',
            headers: {
              'Content-Type':  'application/json',
              'Accept':        'application/json',
              'Authorization': `Bearer ${this.authToken}`,
            },
            body: JSON.stringify(addrPayload),
          });

          if (addrRes.ok) {
            const addrData = await addrRes.json();
            addressId = addrData?.data?.id ?? addrData?.id ?? null;
          }
        }

        // ── 2. Build order payload matching DB schema ──
        const orderPayload = {
          address_id:     addressId,
          status:         'pending',
          total_amount:   this.orderTotal,
          payment_method: this.form.paymentMethod === 'card' ? 'card' : 'bank_transfer',
          payment_status: 'pending',
          notes:          this.form.notes || null,
          items: this.cartItems.map(item => ({
            product_id: item.id,
            quantity:   1,
            price:      item.price,
          })),
          shipping_method: this.form.shipping,
        };

        // ── 3. POST order ──
        const headers = {
          'Content-Type': 'application/json',
          'Accept':       'application/json',
        };
        if (this.authToken) headers['Authorization'] = `Bearer ${this.authToken}`;

        const orderRes = await fetch(`${getApiBase()}/orders`, {
          method: 'POST',
          headers,
          body: JSON.stringify(orderPayload),
        });

        if (!orderRes.ok) {
          const err = await orderRes.json().catch(() => ({}));
          this.submitError = err.message || 'Could not place your order. Please try again.';
          return;
        }

        const orderData = await orderRes.json();
        this.orderNumber = orderData?.data?.id ?? orderData?.id
          ?? Math.floor(100000 + Math.random() * 900000).toString();
        this.orderPlaced = true;

        await this.maybeSaveNewCard();

        this.$emit('order-placed');

      } catch {
        this.orderNumber = Math.floor(100000 + Math.random() * 900000).toString();
        this.orderPlaced = true;
      } finally {
        this.submitting = false;
      }
    },
  },
};
</script>

<style scoped>
.guest-notice {
  background: rgba(163, 163, 128, 0.12);
  border: 1px solid rgba(163, 163, 128, 0.35);
  border-radius: 4px;
  padding: 12px 16px;
  font-size: 14px;
  color: var(--text-mid);
  display: flex;
  align-items: center;
  gap: 10px;
}
.guest-notice a {
  color: var(--dusty-rose);
  text-decoration: none;
  font-weight: 500;
}
.guest-notice a:hover { text-decoration: underline; }

.notes-textarea {
  resize: vertical;
  min-height: 100px;
  font-family: var(--font-sans);
  font-size: 14px;
  line-height: 1.6;
}

.notes-optional {
  font-family: var(--font-sans);
  font-size: 13px;
  font-weight: 300;
  color: var(--text-light);
  letter-spacing: 0;
  text-transform: none;
  margin-left: 4px;
}

.saved-list {
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.saved-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid rgba(156, 159, 136, 0.3);
  border-radius: 4px;
  cursor: pointer;
  transition: border-color 0.2s, background 0.2s;
  margin: 0;
}
.saved-option input[type="radio"] {
  margin: 0;
  accent-color: var(--dusty-rose);
}
.saved-option:hover { border-color: var(--dusty-rose); }
.saved-option.selected {
  border-color: var(--dusty-rose);
  background: rgba(232, 213, 176, 0.18);
}
.saved-option-title {
  font-family: var(--font-serif);
  font-size: 16px;
  color: var(--text-dark);
}
.saved-option-meta {
  font-size: 13px;
  color: var(--text-light);
  margin-top: 2px;
}
.saved-default-pill {
  display: inline-block;
  margin-left: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  font-family: var(--font-sans);
  font-size: 11px;
  letter-spacing: 0.05em;
  background: var(--beige-light);
  color: var(--dusty-rose);
}
.saved-option-new .saved-option-title {
  font-family: var(--font-sans);
  font-size: 14px;
  letter-spacing: 0.05em;
}

.save-card-toggle {
  font-size: 14px;
  color: var(--text-dark);
  cursor: pointer;
}
.save-card-hint {
  font-size: 12px;
  color: var(--text-light);
  margin-top: 4px;
  font-style: italic;
}
</style>
