<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminAuditLogController;
use App\Http\Controllers\Analytics\TrackingController;
use App\Http\Controllers\Analytics\AnalyticsDashboardController;

// Liveness + readiness probes. /health/live is what a load balancer
// pings every few seconds — never expensive, only checks "is PHP alive".
// /health/ready is the deep check used by UptimeRobot and the dashboard:
// it confirms MySQL + Redis are reachable, with per-check latencies.
Route::get('/health',       [HealthController::class, 'ready']);
Route::get('/health/live',  [HealthController::class, 'live']);
Route::get('/health/ready', [HealthController::class, 'ready']);

// Public XML sitemap. Crawlers find it via robots.txt's Sitemap:
// directive (frontend) which points at this URL behind the reverse
// proxy. The body is cached for an hour to avoid per-bot DB hits.
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
Route::post('/login',    [AuthController::class, 'login'])->middleware('throttle:5,1');

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');

// Password reset — public endpoints. Throttled tighter than login because
// they trigger outbound mail (cost) and a rate-limited broker on the DB.
Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
    ->middleware('throttle:5,1');
Route::post('/reset-password',  [AuthController::class, 'resetPassword'])
    ->middleware('throttle:5,1');

Route::get('/categories',        [CategoryController::class, 'index']);
Route::get('/categories/{slug}', [CategoryController::class, 'show']);
Route::get('/products',          [ProductController::class, 'index']);
Route::get('/products/{slug}',   [ProductController::class, 'show']);

// Bulk stock-availability check. Called by the cart drawer before the
// user is allowed to proceed to checkout — returns per-line stock
// state so the UI can mark out-of-stock items.
Route::post('/products/check-stock', [ProductController::class, 'checkStock'])
    ->middleware('throttle:60,1');

// Newsletter signup — public, throttled tighter than the analytics
// endpoints because each call writes a row.
Route::post('/newsletter/subscribe',   [NewsletterController::class, 'subscribe'])
    ->middleware('throttle:5,1');
Route::post('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])
    ->middleware('throttle:5,1');

Route::post('/analytics/track', [TrackingController::class, 'store'])
    ->middleware(['tracking.key', 'throttle:30,1']);

Route::post('/analytics/ping', [TrackingController::class, 'ping'])
    ->middleware(['tracking.key', 'throttle:240,1']);

Route::get('/cart',          [CartController::class, 'index']);
Route::post('/cart',         [CartController::class, 'store']);
Route::put('/cart/{id}',     [CartController::class, 'update']);
Route::delete('/cart/{id}',  [CartController::class, 'destroy']);
Route::delete('/cart',       [CartController::class, 'clear']);

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    Route::put('/me',           [AuthController::class, 'updateProfile']);
    Route::post('/me/password', [AuthController::class, 'changePassword']);

    // Self-service account deletion. Requires re-entering the password
    // (handled in the controller, not by middleware) for safety.
    Route::delete('/me', [AuthController::class, 'deleteAccount'])
        ->middleware('throttle:3,1');

    // Authenticated "email me a reset link" — used by AccountSettings so a
    // signed-in user can change their password by going through the same
    // mail flow as the forgot-password form, without retyping the
    // current password. Throttled per IP same as the public endpoints.
    Route::post('/me/password-reset-link', [AuthController::class, 'requestPasswordResetLink'])
        ->middleware('throttle:5,1');

    Route::post('/email/resend', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');

    Route::get('/wishlist',                 [WishlistController::class, 'index']);
    Route::post('/wishlist/{product}',      [WishlistController::class, 'store']);
    Route::delete('/wishlist/{product}',    [WishlistController::class, 'destroy']);

    Route::get('/payment-methods',          [PaymentMethodController::class, 'index']);
    Route::post('/payment-methods',         [PaymentMethodController::class, 'store']);
    Route::put('/payment-methods/{id}',     [PaymentMethodController::class, 'update']);
    Route::delete('/payment-methods/{id}',  [PaymentMethodController::class, 'destroy']);

    Route::get('/addresses',         [AddressController::class, 'index']);
    Route::post('/addresses',        [AddressController::class, 'store']);
    Route::put('/addresses/{id}',    [AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [AddressController::class, 'destroy']);

    Route::get('/orders',      [OrderController::class, 'index']);
    Route::post('/orders',     [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);

    // Customer-initiated cancellation. Only valid while the order is
    // still `pending`; once the warehouse has moved it the call returns
    // 422 with a help message.
    Route::post('/orders/{id}/cancel', [OrderController::class, 'cancel']);

    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::get('/products',              [AdminProductController::class, 'index']);
        Route::post('/products',             [AdminProductController::class, 'store']);
        Route::post('/products/bulk-delete', [AdminProductController::class, 'bulkDelete']);
        Route::post('/products/bulk-update', [AdminProductController::class, 'bulkUpdate']);
        Route::get('/products/{id}',         [AdminProductController::class, 'show']);
        Route::put('/products/{id}',         [AdminProductController::class, 'update']);
        Route::delete('/products/{id}',      [AdminProductController::class, 'destroy']);

        Route::get('/categories',         [AdminCategoryController::class, 'index']);
        Route::post('/categories',        [AdminCategoryController::class, 'store']);
        Route::get('/categories/{id}',    [AdminCategoryController::class, 'show']);
        Route::put('/categories/{id}',    [AdminCategoryController::class, 'update']);
        Route::delete('/categories/{id}', [AdminCategoryController::class, 'destroy']);

        Route::get('/orders',                [AdminOrderController::class, 'index']);
        Route::get('/orders/{id}',           [AdminOrderController::class, 'show']);
        Route::put('/orders/{id}/status',    [AdminOrderController::class, 'updateStatus']);
        Route::put('/orders/{id}/payment',   [AdminOrderController::class, 'updatePayment']);
        Route::delete('/orders/{id}',        [AdminOrderController::class, 'destroy']);

        Route::get('/coupons',         [AdminCouponController::class, 'index']);
        Route::post('/coupons',        [AdminCouponController::class, 'store']);
        Route::get('/coupons/{id}',    [AdminCouponController::class, 'show']);
        Route::put('/coupons/{id}',    [AdminCouponController::class, 'update']);
        Route::delete('/coupons/{id}', [AdminCouponController::class, 'destroy']);

        Route::get('/audit-logs', [AdminAuditLogController::class, 'index']);

        Route::get('/analytics/overview',     [AnalyticsDashboardController::class, 'overview']);
        Route::get('/analytics/hourly',       [AnalyticsDashboardController::class, 'hourly']);
        Route::get('/analytics/daily',        [AnalyticsDashboardController::class, 'daily']);
        Route::get('/analytics/top-products', [AnalyticsDashboardController::class, 'topProducts']);
        Route::get('/analytics/devices',      [AnalyticsDashboardController::class, 'devices']);
        Route::get('/analytics/funnel',       [AnalyticsDashboardController::class, 'funnel']);
        Route::get('/analytics/realtime',     [AnalyticsDashboardController::class, 'realtime']);
    });
});
