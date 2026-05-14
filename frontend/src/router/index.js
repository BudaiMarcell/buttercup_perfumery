import { createRouter, createWebHistory } from 'vue-router';

const routes = [
  { path: '/', name: 'main' },
  { path: '/shop', name: 'shop' },
  { path: '/about', name: 'about' },
  { path: '/login', name: 'auth' },
  // Landing target for the password-reset link in the verification email.
  // Token + email arrive as query params (`?token=…&email=…`); the
  // component reads them via $route.query.
  { path: '/reset-password', name: 'reset-password' },
  { path: '/checkout', name: 'order' },

  // ── My Account ────────────────────────────────────────────────────────
  { path: '/account/orders',          name: 'account-orders' },
  { path: '/account/wishlist',        name: 'account-wishlist' },
  { path: '/account/addresses',       name: 'account-addresses' },
  { path: '/account/payment-methods', name: 'account-payment-methods' },
  { path: '/account/settings',        name: 'account-settings' },

  // Branded 404. We used to silently redirect unknown URLs to '/',
  // which hid typos and stale shared links from us — now the user
  // sees a clear "this page is missing" page with a way back.
  { path: '/:pathMatch(.*)*', name: 'not-found' },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    return savedPosition || { top: 0 };
  },
});

export default router;
