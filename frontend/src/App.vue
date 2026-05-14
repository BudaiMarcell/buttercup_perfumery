<template>
  <div id="app" :class="{ 'has-verify-banner': showVerifyBanner }">

    <!--
      Site-wide email-not-verified nag. Lives ABOVE the navbar in DOM
      order and is position:fixed at top:0 with z-index above the
      navbar. The navbar shifts down by the banner height (handled in
      style.css via .has-verify-banner) so it doesn't overlap.
    -->
    <transition name="fade">
      <div v-if="showVerifyBanner" class="verify-banner-global">
        <div class="container-xl d-flex align-items-center gap-3 flex-wrap">
          <span class="verify-banner-icon">✦</span>
          <span class="verify-banner-text">
            Please verify your email address —
            <strong>{{ currentUser?.email }}</strong>.
            Check your inbox for the confirmation link.
          </span>
          <div class="verify-banner-actions d-flex gap-2 ms-md-auto">
            <button
              class="verify-banner-btn"
              @click="resendVerifyMail"
              :disabled="verifyBannerSending"
            >
              {{ verifyBannerSending ? 'Sending…' : 'Resend link' }}
            </button>
            <button
              class="verify-banner-dismiss"
              @click="dismissVerifyBanner"
              title="Hide for this session"
              aria-label="Dismiss"
            >×</button>
          </div>
        </div>
        <p v-if="verifyBannerMsg" class="verify-banner-msg container-xl">
          {{ verifyBannerMsg }}
        </p>
      </div>
    </transition>

    <nav
      class="navbar navbar-expand-lg buttercup-nav fixed-top"
      :class="{ scrolled: isScrolled }"
    >
      <div class="container-xl">
        <a
          class="navbar-brand buttercup-brand"
          href="#"
          @click.prevent="goTo('main')"
        >
          <span class="logo-mark">✦</span>
          <span class="logo-text">BUTTERCUP</span>
        </a>

        <button
          class="navbar-toggler border-0 p-0"
          type="button"
          @click="mobileMenuOpen = !mobileMenuOpen"
          aria-label="Toggle navigation"
        >
          <span class="toggler-icon d-flex flex-column gap-1">
            <span></span><span></span><span></span>
          </span>
        </button>

        <div class="navbar-collapse" :class="{ show: mobileMenuOpen }">
          <ul class="navbar-nav mx-auto gap-lg-1 mb-3 mb-lg-0">
            <li class="nav-item" v-for="item in navItems" :key="item.key">
              <a
                class="nav-link buttercup-link"
                :class="{ active: activeNav === item.key }"
                href="#"
                @click.prevent="goTo(item.key)"
                >{{ item.label }}</a
              >
            </li>
          </ul>
          <div class="d-flex align-items-center gap-1">
            <button class="icon-btn" @click="toggleProfile" title="Profile">
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </button>
            <button
              class="icon-btn position-relative"
              @click="toggleCart"
              title="Cart"
            >
              <svg
                width="18"
                height="18"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z" />
                <line x1="3" y1="6" x2="21" y2="6" />
                <path d="M16 10a4 4 0 0 1-8 0" />
              </svg>
              <span class="cart-badge position-absolute" v-if="cartCount > 0">{{
                cartCount
              }}</span>
            </button>
          </div>
        </div>
      </div>
    </nav>

    <transition name="slide-right">
      <div class="drawer-backdrop" v-if="cartOpen">
        <div class="drawer-overlay" @click="cartOpen = false"></div>
        <div class="drawer-panel d-flex flex-column">
          <div
            class="drawer-header d-flex justify-content-between align-items-center"
          >
            <h3 class="drawer-title mb-0">
              Your Cart <span class="drawer-count">({{ cartCount }})</span>
            </h3>
            <button class="icon-btn" @click="cartOpen = false">
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
          <div
            class="drawer-items flex-grow-1 overflow-auto"
            v-if="cartItems.length"
          >
            <!-- Stock warning banner — shows when any item failed the
                 server-side stock check on cart open / checkout click. -->
            <div v-if="stockWarning" class="cart-stock-warning">
              {{ stockWarning }}
            </div>

            <div
              class="cart-item d-flex align-items-center gap-3"
              v-for="item in cartItems"
              :key="item.id + Math.random()"
              :class="{ 'cart-item-oos': stockProblems[item.id] }"
            >
              <div
                class="cart-item-img d-flex align-items-center justify-content-center flex-shrink-0"
                :style="{ background: item.color }"
              >
                <span>{{ item.icon }}</span>
              </div>
              <div class="flex-grow-1">
                <p class="item-name mb-0">{{ item.name }}</p>
                <p class="item-vol mb-0">{{ item.volume }}</p>
                <p class="item-price mb-0">€{{ item.price }}</p>
                <p v-if="stockProblems[item.id]" class="item-stock-flag mb-0">
                  {{ stockProblems[item.id] }}
                </p>
              </div>
              <button class="remove-btn" @click="removeFromCart(item.id)">
                ×
              </button>
            </div>
          </div>
          <div
            class="empty-cart d-flex flex-column align-items-center justify-content-center flex-grow-1"
            v-else
          >
            <span style="font-size: 48px">🌿</span>
            <p class="mt-2 mb-0">Your cart is empty</p>
          </div>
          <div class="drawer-footer" v-if="cartItems.length">
            <div class="d-flex justify-content-between mb-3 subtotal-row">
              <span>Subtotal</span><span>€{{ cartTotal }}</span>
            </div>
            <button class="checkout-btn w-100" @click="goToOrder">
              Proceed to Checkout
            </button>
          </div>
        </div>
      </div>
    </transition>

    <transition name="slide-right">
      <div class="drawer-backdrop" v-if="profileOpen">
        <div class="drawer-overlay" @click="profileOpen = false"></div>
        <div class="drawer-panel d-flex flex-column">
          <div
            class="drawer-header d-flex justify-content-between align-items-center"
          >
            <h3 class="drawer-title mb-0">My Account</h3>
            <button class="icon-btn" @click="profileOpen = false">
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
          <div class="p-4 d-flex flex-column align-items-center gap-4">
            <div
              class="profile-avatar d-flex align-items-center justify-content-center"
              :style="currentUser ? { background: 'var(--beige-light)', color: 'var(--dusty-rose)' } : {}"
            >
              <span v-if="currentUser" style="font-family: var(--font-serif); font-size: 28px; color: var(--dusty-rose);">
                {{ currentUser.name ? currentUser.name.charAt(0).toUpperCase() : '?' }}
              </span>
              <svg
                v-else
                width="40"
                height="40"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1"
              >
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                <circle cx="12" cy="7" r="4" />
              </svg>
            </div>
            <p class="profile-name mb-0">{{ currentUser ? currentUser.name : 'Not Signed In' }}</p>
            <p v-if="currentUser" style="font-size:13px; color: var(--text-light); margin-top:-12px; margin-bottom:0;">{{ currentUser.email }}</p>
            <div class="profile-links w-100">
              <a
                href="#"
                v-for="l in profileLinks"
                :key="l.route"
                :class="currentUser ? '' : 'profile-link-disabled'"
                :title="currentUser ? '' : 'Sign in to access'"
                @click.prevent="openProfileLink(l.route)"
                >{{ l.label }}</a
              >
            </div>
            <button v-if="!currentUser" class="checkout-btn w-100" @click="goToAuth">
              Sign In / Register
            </button>
            <button v-else class="reset-btn w-100" @click="logout">
              Sign Out
            </button>
          </div>
        </div>
      </div>
    </transition>

    <OrderPage
      v-if="activeNav === 'order'"
      :cart-items="cartItems"
      :cart-total="cartTotal"
      @go-back="leaveOrder"
      @go-auth="goToAuth"
      @order-placed="onOrderPlaced"
    />

    <AuthPage v-if="activeNav === 'auth'" @auth-success="onAuthSuccess" />

    <ResetPasswordPage v-if="activeNav === 'reset-password'" />

    <ShopPage
      v-if="activeNav === 'shop'"
      :products="products"
      :wishlist-ids="wishlistIds"
      @add-to-cart="addToCart"
      @wishlist-toggle="toggleWishlist"
    />

    <AboutPage v-if="activeNav === 'about'" />

    <NotFoundPage v-if="activeNav === 'not-found'" />

    <AccountOrders
      v-if="activeNav === 'account-orders'"
      @go-auth="goToAuth"
      @go-shop="goTo('shop')"
      @logout="logout"
    />

    <AccountWishlist
      v-if="activeNav === 'account-wishlist'"
      @go-auth="goToAuth"
      @go-shop="goTo('shop')"
      @logout="logout"
      @add-to-cart="addToCart"
      @wishlist-changed="onWishlistChanged"
    />

    <AccountAddresses
      v-if="activeNav === 'account-addresses'"
      @go-auth="goToAuth"
      @logout="logout"
    />

    <AccountPaymentMethods
      v-if="activeNav === 'account-payment-methods'"
      @go-auth="goToAuth"
      @logout="logout"
    />

    <AccountSettings
      v-if="activeNav === 'account-settings'"
      @go-auth="goToAuth"
      @logout="logout"
      @user-updated="onUserUpdated"
    />

    <template v-if="activeNav === 'main'">
      <section class="hero position-relative overflow-hidden">
        <div class="hero-bg">
          <div class="hero-blob blob-1"></div>
          <div class="hero-blob blob-2"></div>
          <div class="grain-overlay"></div>
        </div>
        <div class="container-xl">
          <div class="row align-items-center min-vh-100 py-5">
            <div class="col-lg-6 hero-content text-center text-lg-start">
              <p class="hero-eyebrow">New Collection — Spring 2026</p>
              <h1 class="hero-title">
                <span class="d-block">Wear all</span>
                <span class="d-block accent">Natural.</span>
              </h1>
              <p class="hero-sub mx-auto mx-lg-0">
                Botanical essences, hand-distilled in small batches.<br
                  class="d-none d-xl-block"
                />
              </p>
              <div
                class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start"
              >
                <button class="cta-primary" @click="goTo('shop')">
                  Explore Collection
                </button>
                <button class="cta-ghost" @click="goTo('about')">Our Story →</button>
              </div>
            </div>

            <div
              class="col-lg-6 d-flex flex-column align-items-center mt-5 mt-lg-0 hero-visual"
            >
              <div
                class="bottle-showcase d-flex align-items-end justify-content-center gap-3"
              >
                <div class="bottle side-bottle small">
                  <div class="bottle-cap"></div>
                  <div class="bottle-body">
                    <div class="bottle-liquid light"></div>
                  </div>
                </div>
                <div class="bottle main-bottle">
                  <div class="bottle-cap"></div>
                  <div class="bottle-body">
                    <div class="bottle-liquid"></div>
                    <div class="bottle-label">
                      <span class="b-name">BUTTERCUP</span>
                      <span class="b-vol">EDP 50ml</span>
                    </div>
                  </div>
                </div>
                <div class="bottle side-bottle">
                  <div class="bottle-cap"></div>
                  <div class="bottle-body">
                    <div class="bottle-liquid dark"></div>
                  </div>
                </div>
              </div>
              <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                <span class="tag">🌿 Vetiver</span>
                <span class="tag">🪵 Cedarwood</span>
                <span class="tag">🌸 Neroli</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      <div class="marquee-bar overflow-hidden">
        <div class="marquee-track">
          <span v-for="n in 3" :key="n"
            >HAND-CRAFTED &nbsp;✦&nbsp; BOTANICAL &nbsp;✦&nbsp; SUSTAINABLE
            &nbsp;✦&nbsp; SMALL BATCH &nbsp;✦&nbsp; VEGAN &nbsp;✦&nbsp; CRUELTY
            FREE &nbsp;✦&nbsp;</span
          >
        </div>
      </div>

      <section class="feature-banner">
        <div class="container-xl">
          <div class="row align-items-center g-5">
            <div class="col-lg-5 feature-content">
              <p class="feature-eyebrow">The Art of Fragrance</p>
              <h2>Every bottle tells<br /><em>a story of place.</em></h2>
              <p class="feature-body">
                Our perfumers travel to the source — Moroccan rose fields at
                dawn, Atlas cedar forests, Calabrian bergamot groves — to
                capture essences that speak of landscape and memory.
              </p>
              <button class="cta-primary light" @click="goTo('about')">
                Discover Our Process
              </button>
            </div>
            <div class="col-lg-7">
              <div class="row g-3">
                <div class="col-6" v-for="fc in featureCards" :key="fc.name">
                  <div class="feature-card text-center">
                    <span class="fc-icon d-block mb-2">{{ fc.icon }}</span>
                    <p class="mb-1">{{ fc.name }}</p>
                    <small>{{ fc.origin }}</small>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
      <section class="py-5 testimonials">
        <div class="container-xl">
          <div class="text-center mb-5">
            <p class="section-eyebrow">— What they say</p>
            <h2 class="section-title">Loved by many</h2>
          </div>
          <div class="row g-4">
            <div class="col-12 col-md-4" v-for="t in testimonials" :key="t.id">
              <div class="testimonial h-100">
                <p class="t-stars">★★★★★</p>
                <p class="t-text">"{{ t.text }}"</p>
                <div class="d-flex align-items-center gap-3">
                  <div
                    class="t-avatar d-flex align-items-center justify-content-center flex-shrink-0"
                    :style="{ background: t.color }"
                  >
                    {{ t.initial }}
                  </div>
                  <div>
                    <p class="t-name mb-0">{{ t.name }}</p>
                    <p class="t-product mb-0">{{ t.product }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="newsletter py-5">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-12 col-md-8 col-lg-6 text-center">
              <p class="nl-eyebrow">✦ Join the Circle</p>
              <h2 class="nl-title">
                Natural beauty<br />
                delivered to your inbox.
              </h2>
              <p class="nl-body">
                Seasonal releases, behind-the-scenes stories, and exclusive
                offers for subscribers.
              </p>
              <div class="nl-form d-flex flex-column flex-sm-row">
                <input
                  type="email"
                  placeholder="example@email.com"
                  v-model="email"
                  :disabled="subscribing || subscribed"
                />
                <button @click="subscribe" :disabled="subscribing || subscribed">
                  {{ subscribing ? 'Sending…' : (subscribed ? 'Subscribed' : 'Subscribe') }}
                </button>
              </div>
              <p class="nl-note mt-3" v-if="subscribed" style="color: #547a3a;">
                ✓ Thank you — you're on the list.
              </p>
              <p class="nl-note mt-3" v-else-if="subscribeError" style="color: #a44a4a;">
                {{ subscribeError }}
              </p>
            </div>
          </div>
        </div>
      </section> </template
    >

    <footer class="footer pt-5 pb-4">
      <div class="container-xl">
        <div class="row g-5 pb-5 footer-divider">
          <div class="col-12 col-lg-4">
            <div class="d-flex align-items-center gap-2 mb-3">
              <span class="logo-mark footer-mark">✦</span>
              <span class="logo-text footer-logo-text">BUTTERCUP</span>
            </div>
            <p class="footer-tagline">
              Botanical perfumery made with<br />intention and reverence for
              nature.
            </p>
            <div class="d-flex gap-3 mt-3">
              <a
                href="https://www.instagram.com/"
                target="_blank"
                class="social-link"
                title="Instagram"
              >
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
                  <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                  <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                </svg>
              </a>
              <a
                href="https://www.facebook.com/"
                target="_blank"
                class="social-link"
                title="Facebook"
              >
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <path
                    d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"
                  />
                </svg>
              </a>
              <a
                href="https://www.tiktok.com/"
                target="_blank"
                class="social-link"
                title="TikTok"
              >
                <svg
                  width="18"
                  height="18"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                >
                  <path d="M9 12a4 4 0 1 0 4 4V4a5 5 0 0 0 5 5" />
                </svg>
              </a>
            </div>
          </div>
          <div class="col-12 col-lg-8">
            <div class="row g-4">
              <div class="col-6 col-md-4">
                <div class="fl-group d-flex flex-column gap-2">
                  <h4>Shop</h4>
                  <a href="#" @click.prevent="goTo('shop')"
                    >All Fragrances</a
                  >
                  <a href="#" @click.prevent="goTo('shop')"
                    >Collections</a
                  >
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="fl-group d-flex flex-column gap-2">
                  <h4>Company</h4>
                  <a href="#" @click.prevent="goTo('about')">Our Story</a>
                  <a href="https://www.facebook.com/" target="_blank"
                    >Facebook</a
                  >
                  <a href="https://www.tiktok.com/" target="_blank">TikTok</a>
                  <a href="https://www.instagram.com/" target="_blank"
                    >Instagram</a
                  >
                </div>
              </div>
              <div class="col-6 col-md-4">
                <div class="fl-group d-flex flex-column gap-2">
                  <h4>Help</h4>
                  <a href="#" @click.prevent="showFaq = true">FAQ</a>
                  <a href="mailto:hello@buttercup.hu">Contact</a>
                  <a href="#" @click.prevent="showPrivacy = true"
                    >Privacy Policy</a
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
        <div
          class="pt-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-3"
        >
          <p class="footer-copy mb-0">© 2026 Buttercup. All rights reserved.</p>
        </div>
      </div>
    </footer>

    <transition name="fade-tab">
      <div
        class="modal-backdrop-simple"
        v-if="showFaq"
        @click.self="showFaq = false"
      >
        <div class="simple-modal">
          <div
            class="simple-modal-header d-flex justify-content-between align-items-center mb-4"
          >
            <h3 class="drawer-title mb-0">Frequently Asked Questions</h3>
            <button class="icon-btn" @click="showFaq = false">
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
          <div class="faq-list">
            <div
              class="faq-item"
              v-for="faq in faqs"
              :key="faq.q"
              @click="faq.open = !faq.open"
            >
              <div
                class="faq-q d-flex justify-content-between align-items-center"
              >
                <span>{{ faq.q }}</span>
                <svg
                  width="14"
                  height="14"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                  :style="{
                    transform: faq.open ? 'rotate(180deg)' : 'rotate(0)',
                    transition: 'transform .2s',
                  }"
                >
                  <polyline points="6 9 12 15 18 9" />
                </svg>
              </div>
              <div class="faq-a" v-if="faq.open">{{ faq.a }}</div>
            </div>
          </div>
        </div>
      </div>
    </transition>

    <transition name="fade-tab">
      <div
        class="modal-backdrop-simple"
        v-if="showPrivacy"
        @click.self="showPrivacy = false"
      >
        <div class="simple-modal">
          <div
            class="simple-modal-header d-flex justify-content-between align-items-center mb-4"
          >
            <h3 class="drawer-title mb-0">Privacy Policy</h3>
            <button class="icon-btn" @click="showPrivacy = false">
              <svg
                width="20"
                height="20"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.5"
              >
                <line x1="18" y1="6" x2="6" y2="18" />
                <line x1="6" y1="6" x2="18" y2="18" />
              </svg>
            </button>
          </div>
          <div class="privacy-content">
            <p class="privacy-section-title">1. Data Controller</p>
            <p>Buttercup Ltd. — hello@buttercup.hu</p>
            <p class="privacy-section-title">2. Data Collected</p>
            <p>
              When placing an order, we collect your name, email address,
              shipping address, and payment details solely to fulfil your order.
            </p>
            <p class="privacy-section-title">3. Use of Data</p>
            <p>
              Your data is used exclusively for order processing, delivery, and
              customer support. It is never shared with third parties.
            </p>
            <p class="privacy-section-title">4. Cookies</p>
            <p>
              Our website uses cookies to improve your browsing experience.
              Accepting cookies is optional, though some features may be
              unavailable without them.
            </p>
            <p class="privacy-section-title">5. Your Rights</p>
            <p>
              You have the right to access, modify, or request deletion of your
              personal data. Please contact us at hello@buttercup.hu.
            </p>
            <p class="privacy-section-title">6. Contact</p>
            <p>
              For any privacy-related enquiries, please reach out at
              hello@buttercup.hu.
            </p>
          </div>
        </div>
      </div>
    </transition>

    <transition name="cookie-slide">
      <div class="cookie-banner" v-if="showCookies">
        <div
          class="cookie-inner d-flex flex-column flex-sm-row align-items-start align-items-sm-center gap-3"
        >
          <span class="cookie-icon">🍪</span>
          <p class="cookie-text mb-0 flex-grow-1">
            We use cookies to enhance your browsing experience.
            <a
              href="#"
              @click.prevent="
                showPrivacy = true;
                showCookies = false;
              "
              >Privacy Policy</a
            >
          </p>
          <div class="d-flex gap-2 flex-shrink-0">
            <button class="cookie-decline" @click="showCookies = false">
              Decline
            </button>
            <button class="cookie-accept" @click="acceptCookies">Accept</button>
          </div>
        </div>
      </div>
    </transition>

    <!-- Global toast container. Lives at the document root so it
         floats above every drawer / modal. -->
    <ToastContainer />
  </div>
</template>

<script>
import ShopPage from "./components/ShopPage.vue";
import AboutPage from "./components/AboutPage.vue";
import AuthPage from "./components/AuthPage.vue";
import ResetPasswordPage from "./components/ResetPasswordPage.vue";
import OrderPage from "./components/OrderPage.vue";
import NotFoundPage from "./components/NotFoundPage.vue";
import ToastContainer from "./components/ToastContainer.vue";
import { toastSuccess, toastError } from "./toast.js";
import AccountOrders from "./components/account/AccountOrders.vue";
import AccountWishlist from "./components/account/AccountWishlist.vue";
import AccountAddresses from "./components/account/AccountAddresses.vue";
import AccountPaymentMethods from "./components/account/AccountPaymentMethods.vue";
import AccountSettings from "./components/account/AccountSettings.vue";
import { getApiBase } from "./config.js";
import { fetchProducts } from "./products.js";
import {
  trackPageview,
  trackAddToCart,
  trackRemoveFromCart,
  trackCheckout,
  startHeartbeat,
} from "./analytics.js";

export default {
  name: "App",
  components: {
    ShopPage,
    AboutPage,
    AuthPage,
    ResetPasswordPage,
    OrderPage,
    NotFoundPage,
    ToastContainer,
    AccountOrders,
    AccountWishlist,
    AccountAddresses,
    AccountPaymentMethods,
    AccountSettings,
  },
  data() {
    const stored = localStorage.getItem("buttercup_user");
    return {
      currentUser: stored ? JSON.parse(stored) : null,
      isScrolled: false,
      cartOpen: false,
      profileOpen: false,
      mobileMenuOpen: false,
      hoveredProduct: null,
      email: "",
      subscribed: false,
      subscribing: false,
      subscribeError: '',

      // Verify-email banner state. Visible when the user is logged
      // in AND their email_verified_at is null AND they haven't
      // dismissed the banner this session.
      verifyBannerDismissed:
        sessionStorage.getItem('buttercup_verify_dismissed') === '1',
      verifyBannerSending: false,
      verifyBannerMsg: '',

      // Per-product stock-problem labels keyed by product id (set by
      // verifyCartStock). A non-empty entry styles the row red and
      // shows the message under the item name.
      stockProblems: {},
      // High-level banner shown above the cart items when at least
      // one row failed the stock check.
      stockWarning: '',
      // True while a /products/check-stock request is in flight.
      stockChecking: false,
      cartItems: [],
      showCookies: true,
      showFaq: false,
      showPrivacy: false,

      faqs: [
        {
          q: "What are the ingredients?",
          a: "All our fragrances are made with natural botanical ingredients carefully sourced from selected suppliers. All formulas are vegan and cruelty-free.",
          open: false,
        },
        {
          q: "How long does shipping take?",
          a: "Standard delivery takes 3–5 business days, express delivery 1–2 business days. Free shipping is available on orders over €150 within the EU.",
          open: false,
        },
        {
          q: "Can I return a product?",
          a: "Yes, you may return an unopened product within 14 days of delivery. Return shipping costs are borne by the customer, except in the case of a defective item.",
          open: false,
        },
        {
          q: "How should I store my fragrance?",
          a: "Store your fragrance in a cool, dark place away from direct sunlight and heat. Properly stored, it will retain its character for 3–5 years.",
          open: false,
        },
        {
          q: "Are sample sizes available?",
          a: "Yes! We offer small discovery sizes in our shop so you can experience a fragrance before committing to a full bottle.",
          open: false,
        },
      ],

      navItems: [
        { key: "main", label: "Frontpage" },
        { key: "shop", label: "Shop" },
        { key: "about", label: "About" },
      ],

      profileLinks: [
        { route: "account-orders",          label: "My Orders"        },
        { route: "account-wishlist",        label: "Wishlist"         },
        { route: "account-addresses",       label: "Addresses"        },
        { route: "account-payment-methods", label: "Payment Methods"  },
        { route: "account-settings",        label: "Settings"         },
      ],

      bottleFlip: [],

      featureCards: [
        { icon: "🌹", name: "Rose de Mai", origin: "Morocco" },
        { icon: "🌲", name: "Atlas Cedar", origin: "Morocco" },
        { icon: "🍋", name: "Bergamot", origin: "Calabria, Italy" },
        { icon: "🪷", name: "Iris Pallida", origin: "Florence, Italy" },
      ],

      products: [],

      wishlistIds: [],

      testimonials: [
        {
          id: 1,
          text: "Forêt Profonde is the most transportive scent I've ever worn. I feel like I'm wandering through an ancient forest every time.",
          name: "Margot R.",
          product: "Forêt Profonde",
          initial: "M",
          color: "#9c9f88",
        },
        {
          id: 2,
          text: "The packaging alone is a work of art. Terra Bruciata has become my signature and I get compliments everywhere I go.",
          name: "Thomas B.",
          product: "Terra Bruciata",
          initial: "T",
          color: "#a4623e",
        },
        {
          id: 3,
          text: "Finally a brand that feels considered from start to finish. Silence Blanc is ethereal — impossible to describe, impossible to forget.",
          name: "Anaïs V.",
          product: "Silence Blanc",
          initial: "A",
          color: "#cfb586",
        },
      ],

      footerLinks: [
        {
          title: "Shop",
          links: ["All Fragrances", "Collections"],
        },
        {
          title: "Company",
          links: ["Our Story", "Facebook", "TikTok", "Instagram"],
        },
        {
          title: "Help",
          links: ["FAQ", "Contact", "Privacy Policy"],
        },
      ],
    };
  },

  computed: {
    activeNav() {
      return this.$route?.name || "main";
    },
    showVerifyBanner() {
      // Logged in + unverified + not dismissed for this session.
      // currentUser is null until they sign in, and email_verified_at
      // is null until they click the email link.
      if (!this.currentUser) return false;
      if (this.currentUser.email_verified_at) return false;
      if (this.verifyBannerDismissed) return false;
      // Don't show on the reset-password page — it has its own UI and
      // the user may not be the same person.
      if (this.activeNav === 'reset-password') return false;
      return true;
    },
    cartCount() {
      return this.cartItems.length;
    },
    cartTotal() {
      return this.cartItems.reduce((s, i) => s + i.price, 0);
    },
  },

  async created() {
    this.products = await fetchProducts();
    if (this.currentUser) {
      await this.loadWishlistIds();
    }
  },

  mounted() {
    window.addEventListener("scroll", this.handleScroll);
    if (localStorage.getItem("cookiesAccepted")) {
      this.showCookies = false;
    }

    // ── Analytics: open a session + keep it alive ──
    // Fire an initial pageview so the very first navigation (= app boot)
    // creates an analytics_sessions row and a pageview event.
    const initialPath =
      (this.$route && (this.$route.fullPath || this.$route.path)) ||
      window.location.pathname + window.location.search;
    trackPageview(initialPath, { route: this.$route?.name || null });

    // Heartbeat pings /analytics/ping every 30s while the tab is visible,
    // bumping last_seen_at so the dashboard's "Aktív most" counter
    // (sessions seen in the last 2 min) actually reflects live traffic.
    this._stopHeartbeat = startHeartbeat();

    // Cross-tab auth sync. When the user resets their password in a new
    // tab (opened from the email link), that tab removes the auth keys
    // from localStorage, which fires this event in EVERY OTHER tab on
    // the origin. We piggy-back the same signal to log out cleanly
    // without needing the user to refresh.
    window.addEventListener("storage", this.onAuthStorageEvent);

    // Keyboard accessibility: ESC closes the topmost open drawer/modal.
    // Standard expected behavior — having it missing reads as unfinished.
    window.addEventListener("keydown", this.onGlobalKeydown);
  },
  beforeUnmount() {
    window.removeEventListener("scroll", this.handleScroll);
    clearInterval(this.carouselTimer);
    if (typeof this._stopHeartbeat === "function") this._stopHeartbeat();
    window.removeEventListener("storage", this.onAuthStorageEvent);
    window.removeEventListener("keydown", this.onGlobalKeydown);
  },

  watch: {
    // Every SPA navigation should look like a pageview to the analytics
    // backend. Without this, only the very first load (and shop-page
    // product hovers) ever produced an event row.
    $route(to, from) {
      if (!to) return;
      if (from && to.fullPath === from.fullPath) return;
      trackPageview(to.fullPath || to.path, { route: to.name || null });
    },
  },

  methods: {
    acceptCookies() {
      this.showCookies = false;
      localStorage.setItem("cookiesAccepted", "1");
    },
    handleScroll() {
      this.isScrolled = window.scrollY > 60;
    },
    toggleCart() {
      this.cartOpen = !this.cartOpen;
      this.profileOpen = false;
      // Fire-and-forget stock check when the drawer opens. If the user
      // has been sitting on the page for a while, items they added
      // earlier may have been bought out — the banner gives them a
      // heads-up before they hit "Proceed to Checkout" and get a 422.
      if (this.cartOpen && this.cartItems.length) {
        this.verifyCartStock();
      }
    },
    toggleProfile() {
      this.profileOpen = !this.profileOpen;
      this.cartOpen = false;
    },
    goTo(name) {
      this.mobileMenuOpen = false;
      if (this.$route?.name !== name) {
        this.$router.push({ name });
      }
    },
    goToAuth() {
      this.profileOpen = false;
      this.goTo("auth");
    },
    onAuthSuccess(user) {
      this.currentUser = user;
      this.profileOpen = false;
      this.loadWishlistIds();
      this.goTo("main");
    },
    openProfileLink(routeName) {
      this.profileOpen = false;
      if (!this.currentUser) {
        this.goTo("auth");
        return;
      }
      this.goTo(routeName);
    },
    onUserUpdated(user) {
      this.currentUser = user;
    },
    onWishlistChanged(productIds) {
      this.wishlistIds = Array.isArray(productIds) ? productIds : [];
    },
    onOrderPlaced() {
      this.cartItems = [];
      this.cartOpen = false;
      toastSuccess('Your order has been placed — check your inbox for the confirmation.');
    },

    dismissVerifyBanner() {
      this.verifyBannerDismissed = true;
      try { sessionStorage.setItem('buttercup_verify_dismissed', '1'); } catch {}
    },

    async resendVerifyMail() {
      const token = localStorage.getItem('buttercup_token');
      if (!token) return;
      this.verifyBannerSending = true;
      this.verifyBannerMsg = '';
      try {
        const res = await fetch(`${getApiBase()}/email/resend`, {
          method: 'POST',
          headers: {
            Accept:        'application/json',
            Authorization: `Bearer ${token}`,
          },
        });
        if (res.status === 429) {
          this.verifyBannerMsg = 'Túl gyakran kérted — próbáld újra egy perc múlva.';
        } else if (!res.ok) {
          this.verifyBannerMsg = 'Nem sikerült elküldeni a megerősítő e-mailt.';
        } else {
          this.verifyBannerMsg = 'Megerősítő e-mailt elküldtük. Kattints rá a linkre az aktiváláshoz.';
        }
      } catch {
        this.verifyBannerMsg = 'Hálózati hiba — próbáld újra.';
      } finally {
        this.verifyBannerSending = false;
      }
    },
    /**
     * Global Escape key handler. Closes overlays in a specific order
     * (topmost / most modal first) so that pressing ESC repeatedly
     * "peels back" the UI: modals → drawers → mobile menu.
     */
    onGlobalKeydown(e) {
      if (e.key !== "Escape" && e.keyCode !== 27) return;
      if (this.showPrivacy)    { this.showPrivacy = false; return; }
      if (this.showFaq)        { this.showFaq = false; return; }
      if (this.cartOpen)       { this.cartOpen = false; return; }
      if (this.profileOpen)    { this.profileOpen = false; return; }
      if (this.mobileMenuOpen) { this.mobileMenuOpen = false; return; }
    },

    /**
     * Triggered when ANOTHER tab on this origin mutates localStorage.
     * We only care about the auth keys: if the token or user record
     * vanishes (e.g. the reset-password tab cleared them), this tab is
     * now holding a dead session and should log out silently.
     *
     * Note: a `storage` event NEVER fires in the tab that made the
     * change — only in the other ones. So there is no risk of an
     * infinite loop with logout() below.
     */
    onAuthStorageEvent(e) {
      if (!e) return;
      const watched = e.key === "buttercup_token" || e.key === "buttercup_user";
      if (!watched) return;

      // newValue === null means the key was removed.
      if (e.newValue !== null) return;

      // Already logged out locally — nothing to do.
      if (!this.currentUser) return;

      // Skip the API /logout call (the server already revoked all
      // tokens for this user when the other tab reset the password);
      // just reset the local state, drawer state, and navigate home.
      this.currentUser = null;
      this.wishlistIds = [];
      this.cartItems = [];
      this.profileOpen = false;
      this.cartOpen = false;
      this.mobileMenuOpen = false;

      if (this.$route?.name !== "main") {
        this.$router.push({ name: "main" });
      }
    },

    logout() {
      const token = localStorage.getItem("buttercup_token");
      if (token) {
        fetch(`${getApiBase()}/logout`, {
          method: "POST",
          headers: { "Authorization": `Bearer ${token}`, "Accept": "application/json" },
        }).catch(() => {});
      }
      localStorage.removeItem("buttercup_token");
      localStorage.removeItem("buttercup_user");
      this.currentUser = null;
      this.wishlistIds = [];
      this.cartItems = [];
      this.profileOpen = false;
      this.cartOpen = false;
      this.mobileMenuOpen = false;

      // CRITICAL: if the user clicked Sign Out from /account/settings
      // (sidebar or bottom button), the account-* component stays mounted
      // and keeps its stale auth state until a manual refresh. Pushing to
      // the main route unmounts the child and forces a clean render with
      // the new (logged-out) state.
      if (this.$route?.name !== "main") {
        this.$router.push({ name: "main" });
      }
    },
    async goToOrder() {
      // Re-check stock at the click moment — the cart drawer's
      // verifyCartStock fires on open, but between then and "Proceed
      // to Checkout" another customer may have grabbed the last unit.
      // We still proceed if the network errors out (the API will
      // reject in OrderController::store as a last line of defence).
      const ok = await this.verifyCartStock();
      if (!ok) {
        // Stay on the cart — the banner + per-row badges already
        // explain what's wrong.
        return;
      }

      this.cartOpen = false;
      // Funnel signal: user committed to checkout. Counted on the API as
      // event_type='checkout' and feeds the conversion funnel widget.
      trackCheckout({
        item_count: this.cartItems.length,
        cart_total: this.cartTotal,
      });
      this.goTo("order");
    },
    leaveOrder() {
      if (window.history.length > 1) {
        this.$router.back();
      } else {
        this.goTo("main");
      }
    },
    toggleSearch() {},
    addToCart(p) {
      this.cartItems.push({ ...p });
      // Intentionally NOT opening the cart drawer here — the toast in
      // the top-right is enough confirmation, and pushing the drawer
      // out on every click is hostile to "I'll keep browsing" flow.
      // The user reaches the cart via the cart icon in the navbar.
      toastSuccess(`Added "${p.name}" to your cart.`);
      // Funnel signal: feeds Today.AddToCarts and the conversion funnel.
      if (p?.id) {
        trackAddToCart(p.id, { name: p.name, price: p.price });
      }
    },
    async loadWishlistIds() {
      const token = localStorage.getItem("buttercup_token");
      if (!token) {
        this.wishlistIds = [];
        return;
      }
      try {
        const res = await fetch(`${getApiBase()}/wishlist`, {
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        });
        if (!res.ok) return;
        const json = await res.json();
        const items = Array.isArray(json) ? json : json.data ?? [];
        this.wishlistIds = items.map((x) => x.product_id);
      } catch {
      }
    },
    async toggleWishlist(product) {
      if (!this.currentUser) {
        this.goToAuth();
        return;
      }
      const token = localStorage.getItem("buttercup_token");
      if (!token) {
        this.goToAuth();
        return;
      }

      const id = product.id;
      const wasWished = this.wishlistIds.includes(id);
      this.wishlistIds = wasWished
        ? this.wishlistIds.filter((x) => x !== id)
        : [...this.wishlistIds, id];

      try {
        const res = await fetch(`${getApiBase()}/wishlist/${id}`, {
          method: wasWished ? "DELETE" : "POST",
          headers: {
            Accept: "application/json",
            Authorization: `Bearer ${token}`,
          },
        });
        if (!res.ok) {
          this.wishlistIds = wasWished
            ? [...this.wishlistIds, id]
            : this.wishlistIds.filter((x) => x !== id);
        }
      } catch {
        this.wishlistIds = wasWished
          ? [...this.wishlistIds, id]
          : this.wishlistIds.filter((x) => x !== id);
      }
    },
    removeFromCart(id) {
      const i = this.cartItems.findIndex((x) => x.id === id);
      if (i !== -1) {
        const removed = this.cartItems[i];
        this.cartItems.splice(i, 1);
        if (id) trackRemoveFromCart(id, { name: removed?.name });
        // Clearing the row also clears its stock-problem flag; the
        // overall warning banner is cleared if the cart is now empty
        // or every remaining line was fine.
        if (this.stockProblems[id]) {
          const { [id]: _drop, ...rest } = this.stockProblems;
          this.stockProblems = rest;
        }
        if (this.cartItems.length === 0 ||
            Object.keys(this.stockProblems).length === 0) {
          this.stockWarning = '';
        }
      }
    },

    /**
     * Server-side stock check before the user is allowed to check out.
     * Quietly clears per-row flags + the banner when stock is OK,
     * decorates the failing rows when it isn't.
     */
    async verifyCartStock() {
      if (!this.cartItems.length) return true;

      this.stockChecking = true;
      try {
        // Aggregate identical product IDs into a count so a cart with
        // 3 of the same perfume sends one line with quantity:3, not
        // three lines with quantity:1 each.
        const counts = {};
        for (const item of this.cartItems) {
          counts[item.id] = (counts[item.id] || 0) + 1;
        }
        const payload = {
          items: Object.entries(counts).map(([id, qty]) => ({
            product_id: Number(id),
            quantity:   qty,
          })),
        };

        const res = await fetch(`${getApiBase()}/products/check-stock`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept:         'application/json',
          },
          body: JSON.stringify(payload),
        });
        if (!res.ok) return true; // never block checkout on a check failure

        const data = await res.json();
        const problems = {};
        for (const row of data.items || []) {
          if (row.in_stock) continue;
          problems[row.product_id] = row.reason === 'out_of_stock'
            ? 'Out of stock'
            : `Only ${row.available} left`;
        }
        this.stockProblems = problems;
        this.stockWarning  = Object.keys(problems).length
          ? "Some items in your cart aren't available anymore. Please remove them or reduce quantities."
          : '';
        return data.all_in_stock !== false;
      } catch {
        return true; // network errors shouldn't lock the user out
      } finally {
        this.stockChecking = false;
      }
    },
    scrollToProducts() {
      document
        .getElementById("products")
        ?.scrollIntoView({ behavior: "smooth" });
    },
    async subscribe() {
      const email = (this.email || '').trim();
      if (!email) return;

      // Quick local sanity check; the API does the real validation.
      if (!/\S+@\S+\.\S+/.test(email)) {
        this.subscribeError = 'Please enter a valid email address.';
        return;
      }

      this.subscribing = true;
      this.subscribeError = '';

      try {
        const res = await fetch(`${getApiBase()}/newsletter/subscribe`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept:         'application/json',
          },
          body: JSON.stringify({ email, source: 'homepage' }),
        });
        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
          const msg = data?.message
            || (res.status === 429
              ? 'Túl sok próbálkozás — kérlek, várj egy percet.'
              : 'Could not subscribe right now.');
          this.subscribeError = msg;
          toastError(msg);
          return;
        }

        this.subscribed = true;
        this.email = '';
        toastSuccess(data?.message || "You're on the list. Welcome!");
      } catch {
        this.subscribeError = 'Network error — please try again.';
        toastError('Network error — please try again.');
      } finally {
        this.subscribing = false;
      }
    },
  },
};
</script>
