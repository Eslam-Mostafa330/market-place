<div align="center">

# Multi-Vendor Marketplace API

**A production-grade, fully-featured marketplace backend built with Laravel 12**

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-Predis-DC382D?style=flat-square&logo=redis&logoColor=white)](https://redis.io)
[![Stripe](https://img.shields.io/badge/Stripe-Payments-635BFF?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com)
[![Portfolio](https://img.shields.io/badge/Purpose-Portfolio%20%2F%20Evaluation-blue?style=flat-square)](_)

A comprehensive RESTful API for a multi-vendor food/product delivery marketplace — covering vendor onboarding, store management, real-time rider tracking, order lifecycle, payments, loyalty, and analytics.

</div>

---

## Table of Contents

- [Overview](#-overview)
- [Architecture & Design Decisions](#-architecture--design-decisions)
- [Tech Stack](#-tech-stack)
- [Features](#-features)
- [Project Structure](#-project-structure)
- [Getting Started](#-getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Redis Setup (Windows)](#redis-setup-windows)
  - [Stripe Setup](#stripe-setup)
  - [Queue Workers](#queue-workers)
- [API Overview](#-api-overview)
- [API Documentation](#-api-documentation)
- [Authentication & Security](#-authentication--security)
- [Real-Time Rider Location Tracking](#-real-time-rider-location-tracking)
- [Order Lifecycle](#-order-lifecycle)
- [Performance Optimizations](#-performance-optimizations)
- [Scheduled Commands](#-scheduled-commands)
- [Development Tools](#-development-tools)
- [Testing Rider Location (Tinker)](#-testing-rider-location-tinker)

---

## Overview

This project is a **headless, API-only multi-vendor marketplace backend** designed to power mobile apps and web frontends simultaneously. It handles four distinct user roles — **Admin**, **Vendor**, **Customer**, and **Rider** — each with their own authentication flows, dashboards, and capabilities.

Key highlights:
- **UUID-based** primary keys across all models
- **Versioned API routes** (`/api/v1/...`) with modular file organization
- **Service-layer architecture** separating business logic from controllers
- **Enum-driven domain logic** for statuses, types, and transitions
- **Full order lifecycle** from placement to delivery with rider assignment, payouts, and loyalty rewards

---

## Architecture & Design Decisions

### Service Layer
Business logic lives in dedicated service classes (e.g., `AuthService`, `PlaceOrderService`, `RiderLocationService`, `VendorDashboardService`). Controllers are thin — they validate input, delegate to services, and return standardized responses.

### Enum-Driven Domain
States and types across the system are strongly typed via PHP enums: `OrderStatus`, `VendorVerificationStatus`, `CancellationReason`, `PayoutStatus`, `RiderAvailability`, `CouponType`, `SettingKey`, etc. This makes transitions explicit and validation trivial.

Enum values are stored as **`tinyInt`** in the database rather than strings — a deliberate performance choice that reduces storage size, speeds up indexed lookups, and keeps the database layer lean while the application layer handles all human-readable mapping.

### Standardized API Responses
A global response wrapper and API exception to ensures every endpoint returns a consistent JSON envelope — success flag, HTTP status, message, and data — simplifying frontend integration.

### Global N+1 Prevention
Lazy loading is disabled in `AppServiceProvider`, forcing all relations to be explicitly eager-loaded and catching N+1 issues at development time.

### Custom Stubs
Modified artisan stubs ensure every generated model, controller, and migration follows the project's conventions (UUID keys, standardized structure) out of the box.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.3 |
| Framework | Laravel 12 |
| Authentication | Laravel Sanctum (token + SPA cookie dual strategy) |
| Database | MySQL 8 |
| Cache / Queue Store | Redis via `predis/predis` |
| Real-time Location | Redis (sub-millisecond writes, 5-min TTL) |
| Payments | Stripe PHP SDK + Webhook handling |
| Activity Logging | `spatie/laravel-activitylog` |
| Dev Debugging | Laravel Telescope |
| Queue | Laravel Queues (multiple named queues) |

---

## Features

### Multi-Role Authentication
- **4 roles**: Admin, Vendor, Customer, Rider — each with isolated auth routes, middleware, and token scopes
- Login / Logout / Token Refresh
- **Two-Factor Authentication (OTP)** — currently enabled for **Admin** accounts only; additional roles can be configured in `config/two_factor.php` without code changes
- Email verification with rate-limited resend (3 attempts / 10 min)
- Password reset flow per role
- Token revocation on password change (logout from all other devices)
- Token revocation on account deactivation (security enforcement)
- Separate credential validation from token issuance — tokens are never issued until 2FA passes

### Vendor & Store Management
- Vendor registration with `INCOMPLETE` → `PENDING` → `VERIFIED` / `REJECTED` verification flow
- Admin can update verification status with mandatory rejection reason
- Verified vendors can manage **Stores**, **Branches**, **Products**, and **Coupons**
- Store logo/image upload via `MediaHandler`
- Deletion guards: cannot delete store if branches exist, cannot delete branch if orders exist

### Product Catalog
- Two-level product category hierarchy (parent → child, max one level deep) via custom validation rule
- Product CRUD scoped to store ownership
- Public product listing with **is_favorite** flag injected per authenticated customer
- Related products on product detail (limit 8)
- Hierarchical active product categories per store (public endpoint)

### Order Lifecycle

```
PENDING → ACCEPTED → PREPARING → WAITING_RIDER → PICKED_UP → DELIVERED
                                                ↘ (rejected by rider → WAITING_RIDER again)
Any cancellable state → CANCELLED
```

- Full financial snapshot locked at order time: subtotal, discount, wallet discount, commission, vendor earnings, rider earnings
- Sequential order numbers (`ORD-YYYYMMDD-00001`) with `lockForUpdate()` to prevent race conditions
- Coupon validation with `lockForUpdate()` to prevent concurrent usage exploits
- Stock decrement wrapped in DB transaction with `SELECT ... FOR UPDATE`
- Customer, Vendor, Rider, and Admin each have role-scoped order endpoints

### Real-Time Rider Assignment
- **`FindRiderJob`** dispatches automatically when order is marked ready
  - Retries every 30 seconds, up to 10 attempts (5 minutes total)
  - Uses **Haversine formula** to find the nearest available rider within a configurable radius
  - Escalates to Admin notification if no rider found
- Admin can manually assign riders, extend search window (+5 minutes), or cancel orders
- Rider can reject (restarts job), pick up, or deliver orders

### Real-Time Location Tracking
- GPS coordinates stored in **Redis** on every update (sub-millisecond write)
- MySQL sync throttled to **once every 30 seconds** to reduce DB load
- Redis keys auto-expire after **5 minutes** to remove stale riders
- `MarkStaleRidersUnavailable` scheduled command marks idle riders unavailable
- Redis-first lookup with MySQL fallback for offline riders

### Payments (Stripe)
- `PaymentIntent` creation with idempotency keys to prevent duplicate charges
- Client secret returned to frontend for Stripe.js integration
- Webhook handler with signature validation for `payment_succeeded` and `payment_failed` events
- Cash vs. card payment flows handled separately in payout logic

### Payouts
- Rider and Vendor payouts created automatically on order delivery
- Cash orders: marked paid immediately; card orders: rely on Stripe webhook
- Admin payout management: update details, mark complete with audit fields
- Riders can view their own payout history (admin fields hidden)

### Loyalty & Wallet
- Points awarded after delivery based on net paid amount
- Points redeemable for wallet balance
- Wallet discount applied at checkout (capped at 50% of order total)
- `loyalty_points` rate cached from settings for performance

### Reviews
- Customers can review delivered orders (one review per order, 24-hour edit window)
- Store average rating updated in O(1) using atomic increments
- Rating recalculated on update (if changed) and on admin deletion
- Duplicate review attempts handled as HTTP 409

### Notifications (Database)
All notifications are **queued** on dedicated queues:

| Notification | Trigger | Recipient |
|---|---|---|
| `NewOrderNotification` | Order placed | Vendor |
| `RiderAssignedNotification` | Rider assigned | Rider |
| `OrderStatusUpdatedNotification` | Accept / Pickup / Deliver | Customer |
| `OrderCancelledNotification` | Order cancelled | Customer |
| `AdminOrderEscalationNotification` | No rider found | Admin |

- Cursor-based pagination for better infinite-scroll performance
- Unread count endpoint for navbar badges
- Mark single / mark all as read endpoints

### Dashboards & Analytics
- **Vendor Dashboard**: period stats (orders, earnings, commission, AOV), monthly earnings chart, top products, latest reviews — filterable by store, month, year
- **Admin Dashboard**: platform-wide stats, top stores, top products, activity logs
- **Rider Dashboard**: delivery stats, monthly earnings, latest deliveries and payouts
- All dashboards include cached responses with observer-based invalidation

### Settings
- Admin-managed settings: contact info, social links, loyalty point rate
- Public settings endpoint cached indefinitely
- Settings organized via `SettingKey` enum with grouped key helpers

### Customer Preferences Engine
- Tracks product views, favorites, reorders, category affinity, and store loyalty
- Merges signals into deduplicated recommendations (capped at 40)
- `RefreshCustomerPreferences` job dispatched throttled per trigger (view, favorite, order)
- Full nightly rebuild scheduled automatically

### Security
- **CORS**: restricted to `FRONTEND_URL` env — no hardcoded origins
- **`BlockDirectAccessMiddleware`**: blocks requests from unauthorized origins
- **Custom rate limiting middleware**:
  - Auth routes: 10 req/min
  - Form submissions: 20 req/min
  - General routes: 60 req/min
  - Combined IP + user identifier to prevent bypass
- **`.htaccess` hardening**: blocks oversized query strings, empty User-Agents, path traversal, sensitive file access; adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` headers
- Sanctum stateful cookies for SPA + Bearer tokens for mobile (dual strategy)

---

## Project Structure

```
app/
├── Console/Commands/          # Scheduled commands (stale riders, expired tokens/OTPs)
├── Enums/                     # Domain enums (OrderStatus, PayoutStatus, RiderAvailability, etc.)
├── Exceptions/                # Global API exception handler
├── Http/
│   ├── Controllers/Api/V1
│   │   ├── Admin/             # Admin-scoped controllers
│   │   ├── Customer/          # Customer-scoped controllers
│   │   ├── Public/            # Unauthenticated public endpoints
│   │   ├── Rider/             # Rider-scoped controllers
│   │   └── Vendor/            # Vendor-scoped controllers
│   ├── Middleware/            # BlockDirectAccess, EnsureVendorIsVerified, RateLimiter, etc.
│   └── Requests/              # Form requests per role/domain
├── Jobs/                      # FindRiderJob, RefreshCustomerPreferences
├── Models/                    # Eloquent models (UUID keys, typed casts, accessors)
├── Notifications/             # All queued database notifications
├── Observers/                 # UserObserver, StoreObserver, ReviewObserver, etc.
├── Providers/                 # RouteBindingServiceProvider (cached slug bindings)
├── Resources/                 # API resources per domain
├── Rules/                     # Custom validation rules (SelectableProductCategory, EmailOrPhone, etc.)
├── Services/
│   ├── Auth/                  # AuthService, EmailVerificationService, PasswordResetService, TwoFactorService
│   ├── Customer/              # CustomerPreferencesService, LoyaltyService
│   ├── Rider/                 # RiderService, RiderLocationService
│   └── ...                    # OrderPricingCalculatorService, PlaceOrderService, PayoutServices, etc.
└── Traits/                    # ClearsCache, AdminAuthorization, ResolvesAuthCustomer, etc.

routes/api/v1/
├── admin/                     # Admin route files (auth, users, stores, orders, etc.)
├── vendor/                    # Vendor route files
├── customer/                  # Customer route files
├── rider/                     # Rider route files
└── public/                    # Public unauthenticated routes
```

---

## Getting Started

### Prerequisites

- PHP 8.3+
- Composer
- MySQL 8.0+
- Redis (see below for Windows setup)
- Stripe CLI (for local webhook testing)
- Node.js (optional, for asset compilation)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/Eslam-Mostafa330/market-place.git
cd marketplace

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your .env
# Set DB_*, REDIS_*, STRIPE_*, FRONTEND_URL, FRONT_URL, MAIL_* variables

# 6. Run migrations
php artisan migrate

# 7. Seed the database
php artisan db:seed

# 8. Link storage
php artisan storage:link
```

### Redis Setup (Windows)

This project uses `predis/predis` (pure PHP Redis client) — no PHP extension required, making it simple to introduce Redis in any environment.

For Windows, install **Memurai** (a Redis-compatible server for Windows):

1. Download **[Memurai for Redis v4.2.2](https://www.memurai.com/)** (`Memurai-Developer-v4.2.2.msi`)
2. Run the installer — Memurai registers as a Windows service automatically
3. Verify it's running:
   ```bash
   memurai-cli ping
   # Expected: PONG
   ```
4. Your `.env` should have:
   ```env
   REDIS_CLIENT=predis
   REDIS_HOST=127.0.0.1
   REDIS_PASSWORD=null
   REDIS_PORT=6379
   ```

### Stripe Setup

For local payment testing without a live server, use the **Stripe CLI** to forward webhooks:

```bash
# Install Stripe CLI, then:
stripe login
stripe listen --forward-to localhost:8000/api/v1/stripe/webhook
```

Copy the webhook signing secret from the CLI output into your `.env`:
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Then verify the payment process:
```bash
# Verify payment via terminal:
stripe payment_intents confirm pi_3TRMDdPULxx... --payment-method=pm_card_visa
```

### Queue Workers

The project uses **named queues** for priority and isolation. Start all workers with:

```bash
php artisan queue:work \
  --queue=rider-matching,rider-assigned,new-order,admin-order-escalation,order-status-change,cancel-order,default,refresh-user-preference
```

> **Queue priority**: `rider-matching` is first — rider assignment jobs are processed before all other notifications, ensuring minimal order delays.

For production, use **Supervisor** to keep workers running persistently.

---

## API Overview

All endpoints follow the versioned prefix `/api/v1/`.

### Role-Based Route Groups

| Prefix | Guard | Description |
|---|---|---|
| `/api/v1/admin/auth/` | — | Admin login, logout, refresh, OTP |
| `/api/v1/admin/` | `admin` middleware | Admin management endpoints |
| `/api/v1/vendor/auth/` | — | Vendor login, register, verify, reset |
| `/api/v1/vendor/` | `vendor` middleware | Vendor store/order management |
| `/api/v1/customer/auth/` | — | Customer register, login, verify, reset |
| `/api/v1/customer/` | `customer` middleware | Customer orders, addresses, favorites |
| `/api/v1/rider/auth/` | — | Rider login, logout, refresh |
| `/api/v1/rider/` | `rider` middleware | Rider orders, location, profile |
| `/api/v1/` (public) | — | Business categories, stores, products |

### Sample Endpoints

<details>
<summary><strong>Auth (Admin)</strong></summary>

```
POST   /api/v1/admin/auth/login
POST   /api/v1/admin/auth/logout
POST   /api/v1/admin/auth/refresh
POST   /api/v1/admin/auth/otp/verify
POST   /api/v1/admin/auth/otp/resend
```
</details>

<details>
<summary><strong>Orders (Customer)</strong></summary>

```
POST   /api/v1/customer/orders              # Place order
GET    /api/v1/customer/orders              # List my orders
GET    /api/v1/customer/orders/{id}         # Order details
POST   /api/v1/customer/orders/{id}/cancel  # Cancel order
POST   /api/v1/customer/loyalty/redeem      # Redeem points
```
</details>

<details>
<summary><strong>Rider Location & Orders</strong></summary>

```
PATCH  /api/v1/rider/location               # Update GPS coordinates
PATCH  /api/v1/rider/availability           # Toggle availability
POST   /api/v1/rider/orders/{id}/reject     # Reject → restart search
POST   /api/v1/rider/orders/{id}/pickup     # Mark picked up
POST   /api/v1/rider/orders/{id}/deliver    # Mark delivered
```
</details>

<details>
<summary><strong>Public Store & Product Browsing</strong></summary>

```
GET    /api/v1/business-categories
GET    /api/v1/stores/{category_slug}
GET    /api/v1/stores/{category_slug}/{store_slug}
GET    /api/v1/stores/{category_slug}/{store_slug}/branches
GET    /api/v1/stores/{category_slug}/{store_slug}/products
GET    /api/v1/stores/{category_slug}/{store_slug}/products/{product_slug}
POST   /api/v1/stores/{category_slug}/{store_slug}/products/{product_slug}/favorite
```
</details>

---

## Authentication & Security
 
### Token Strategy
- **Mobile clients** → Bearer token (Sanctum Personal Access Tokens)
- **SPA / Web** → Stateful Sanctum cookies (`statefulApi` enabled)
- Extended `PersonalAccessToken` model to support `session_id` tracking
### Two-Factor Authentication
OTP-based 2FA is currently enabled for **Admin** accounts. Other roles can be enabled by adding them to `config/two_factor.php` — no code changes required.
 
Admin 2FA flow:
1. Credentials validated → OTP emailed if 2FA enabled
2. OTP submitted → token issued only after verification
3. Trusted devices bypass OTP for 30 days (browser cookie-based)
### CORS
- Allowed origins restricted to `FRONTEND_URL` and `FRONTEND_URL_WWW`
- Credentials support enabled for cookie-based SPA auth
- All origins allowed in local environment for testing tools (e.g. Postman, ApiDog)
### Origin Enforcement (`BlockDirectAccessMiddleware`)
- Blocks requests from unauthorized origins at the middleware level
- `OPTIONS` preflight requests are allowed through before origin validation
- `HandleCors` runs before `BlockDirectAccess` to ensure proper CORS headers on blocked responses
- Bypassed in local environment to avoid friction during development
### Rate Limiting (Custom Middleware)
```
Auth endpoints:         10 requests / minute
Form submissions:       20 requests / minute
General API routes:     60 requests / minute
Throttle key:           IP + user ID (prevents shared-IP bypass)
Admin routes:           Excluded from throttling
```
 
### `.htaccess` Hardening
 
| Rule | Purpose |
|---|---|
| Block query strings > 500 chars | Mitigate query string abuse / injection attempts |
| Block empty `User-Agent` | Reject primitive bots and scanners |
| Forward `Authorization` header to PHP | Required for Bearer token auth behind Apache |
| Forward `X-XSRF-Token` header to PHP | Required for Sanctum SPA cookie auth |
| `AcceptPathInfo Off` | Disable `PATH_INFO` to mitigate path traversal attacks |
| Block sensitive files | Deny access to `.env`, `.log`, `.json`, `.lock`, `.sql`, `.bak`, `.sh`, `.git`, `.swp`, `.DS_Store` |
| `X-Frame-Options: DENY` | Prevent clickjacking |
| `X-Content-Type-Options: nosniff` | Prevent MIME-type sniffing |
| `X-XSS-Protection: 1; mode=block` | Legacy XSS filter for older browsers |
| `Referrer-Policy: strict-origin-when-cross-origin` | Control referrer leakage |
| Remove `X-Powered-By` | Hide PHP version from response headers |
 
---

## Real-Time Rider Location Tracking

```
Rider App  ──PATCH /rider/location──►  API  ──►  Redis (instant write)
                                                     │
                                          (every 30s)│
                                                     ▼
                                               MySQL sync

FindRiderJob  ──►  RiderLocationService::findNearestRider()
                        │
                        ├── Haversine formula (GPS → km distance)
                        ├── Filter: available + within radius
                        └── Return nearest rider or null
```

- **Redis TTL**: 5 minutes — stale riders auto-removed
- **Scheduled command**: `MarkStaleRidersUnavailable` runs every 10 minutes
- **Logout flow**: rider marked unavailable, Redis keys deleted immediately
- **Redis down**: `ConnectionException` caught → 503 response with clear message

---

## Order Lifecycle

```
Customer places order  { "use_wallet": true }
        │
        ▼
[PlaceOrderService]
  ├── Validate branch, address, coupon, products (lockForUpdate)
  ├── Lock prices + decrement stock (DB transaction)
  ├── Resolve wallet discount (if use_wallet: true)
  │     ├── Read customer wallet balance
  │     ├── Cap discount at 50% of order total  (e.g. order=100, wallet=10 → pay 90)
  │     └── Deduct used amount from wallet balance
  ├── Calculate pricing snapshot (subtotal, coupon discount, wallet discount, commission, earnings)
  ├── Generate sequential order number (lockForUpdate)
  ├── Create payment intent (Stripe) if card payment
  └── Notify vendor (NewOrderNotification)

Vendor accepts → prepares → marks ready
        │
        ▼
[FindRiderJob dispatched] (queue: rider-matching)
  ├── Retry every 30s, max 10 attempts
  ├── Find nearest available rider (Haversine)
  ├── Assign rider → notify rider (RiderAssignedNotification)
  └── Escalate to admin if no rider found

Rider picks up → delivers
        │
        ▼
[RiderOrderService::deliverOrder]
  ├── Mark order DELIVERED
  ├── Set payment_status = PAID (cash) or await Stripe webhook (card)
  ├── Create RiderPayout + VendorPayout
  ├── Award loyalty points to customer (based on net paid amount)
  │     └── Points accumulate → redeemable via POST /customer/loyalty/redeem → credited to wallet
  └── Notify customer (OrderStatusUpdatedNotification)
```

---

## Performance Optimizations

| Area | Technique |
|---|---|
| Rider location | Redis writes (sub-ms) + throttled MySQL sync (30s) |
| Business category lookup | Slug-based cache (120 days), observer-invalidated |
| Store binding | Slug-based cache (90 days), observer-invalidated |
| Profile summaries | Per-user cache keys, invalidated on update |
| Vendor store list | Cached per vendor (60 days) |
| Notification listing | Cursor-based pagination (better than offset for large sets) |
| Settings | Cached indefinitely, cleared on admin update |
| Indexes | Added on `orders`, `order_items`, `reviews` for aggregation queries |
| N+1 prevention | `Model::preventLazyLoading()` enabled globally |
| Job payloads | Primitive IDs dispatched (not full models) to reduce queue memory |
| Notifications | Queued async on dedicated named queues |
| Race conditions | `lockForUpdate()` on order numbers, coupons, stock, reviews |
| Query optimization | Selective column retrieval, strategic joins over nested ORM
  relations, raw queries for heavy aggregations, proper data types (e.g. tinyInt for flags), and index-aware query design
---

## Scheduled Commands

| Command | Schedule | Description |
|---|---|---|
| `DeleteExpiredTokens` | Daily at **2:00 AM** | Removes expired personal access tokens from the database |
| `DeleteExpiredTwoFactorData` | Daily at **1:00 AM** | Deletes unused expired OTP codes and stale trusted device records |
| `MarkStaleRidersUnavailable` | Every **10 minutes** | Marks riders as unavailable if they have not sent a location update in the last 10 minutes — ensures stale GPS entries don't interfere with rider assignment |
| Activity log cleanup | Daily | Prunes `spatie/laravel-activitylog` records older than **90 days** |
| `RefreshCustomerPreferences` | Nightly (full rebuild) | Rebuilds all customer preference scores from scratch |

---

## Development Tools

### Laravel Telescope
Installed for local development only. Provides request inspection, query analysis, job monitoring, and mail preview.

```bash
# Access at:
http://localhost:8000/telescope
```

### Activity Logging (Spatie)
Model changes are automatically logged for: `Store`, `User`, `Order`, `VendorPayout`, `RiderPayout`.
- Logs auto-cleaned after **90 days** (scheduled daily)
- Admin dashboard includes recent activity log

---

## Testing Rider Location (Tinker)

To simulate a rider's location update and test the nearest rider search while a `FindRiderJob` is running:

```bash
# Start queue worker first (in a separate terminal):
php artisan queue:work --queue=rider-matching,...

# Then in another terminal:
php artisan tinker
```

```php
$service = app(App\Services\Rider\RiderLocationService::class);
$riderProfile = App\Models\RiderProfile::first();

// Use coordinates near a store branch that has a pending order
$service->updateRiderLocation($riderProfile, 30.01225878, 31.32566761);
```

> The latitude/longitude should be within the configured search radius of the store branch where the test order was created.

---

## Disclaimer

This repository is intended for **educational and demonstration purposes only**.

This code is made publicly available for portfolio and evaluation purposes. All business logic, names, and data structures are generic and not associated with any real company or service.
---

<div align="center">

Built with ❤️ using **Laravel 12** · **PHP 8.3** · **Redis** · **Stripe**

</div>