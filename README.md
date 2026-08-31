<div align="center">

# Multi-Vendor Marketplace API

**A production-grade, fully-featured marketplace backend built with Laravel 12**

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://mysql.com)
[![Redis](https://img.shields.io/badge/Redis-Predis-DC382D?style=flat-square&logo=redis&logoColor=white)](https://redis.io)
[![Reverb](https://img.shields.io/badge/Reverb-WebSockets-0F766E?style=flat-square&logo=laravel&logoColor=white)](https://reverb.laravel.com)
[![Stripe](https://img.shields.io/badge/Stripe-Payments-635BFF?style=flat-square&logo=stripe&logoColor=white)](https://stripe.com)
[![Portfolio](https://img.shields.io/badge/Purpose-Portfolio%20%2F%20Evaluation-blue?style=flat-square)](_)

A comprehensive RESTful API for a multi-vendor food/product delivery marketplace, covering vendor onboarding, store management, real-time rider tracking, order lifecycle, payments, loyalty, live support chat, and analytics.

</div>

---

## Table of Contents

- [Overview](#overview)
- [Architecture & Design Decisions](#architecture--design-decisions)
- [Tech Stack](#tech-stack)
- [Features](#features)
  - [Customer Support & Live Chat](#customer-support--live-chat)
  - [Security](#security)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
  - [Prerequisites](#prerequisites)
  - [Installation](#installation)
  - [Redis Setup (Windows)](#redis-setup-windows)
  - [Stripe Setup](#stripe-setup)
  - [Queue Workers](#queue-workers)
- [Admin First Login (OTP Walkthrough)](#admin-first-login-otp-walkthrough)
- [Web (SPA) Login Flow](#web-spa-login-flow)
- [API Overview](#api-overview)
- [API Documentation](#api-documentation)
- [Authentication & Security](#authentication--security)
- [Real-Time Support Chat (Reverb)](#real-time-support-chat-reverb)
- [Real-Time Rider Location Tracking](#real-time-rider-location-tracking)
- [Order Lifecycle](#order-lifecycle)
- [Performance Optimizations](#performance-optimizations)
- [Scheduled Commands](#scheduled-commands)
- [Testing](#testing)
  - [Test Database Setup](#test-database-setup)
  - [Running the Suite](#running-the-suite)
  - [How the Tests Are Organised](#how-the-tests-are-organised)
- [Development Tools](#development-tools)
- [Testing Rider Location (Tinker)](#testing-rider-location-tinker)

---

## Overview

This project is a **headless, API-only multi-vendor marketplace backend** designed to power mobile apps and web frontends simultaneously. It handles five distinct user roles (**Admin**, **Vendor**, **Customer**, **Rider** and **Support**), each with their own authentication flows, dashboards, and capabilities.

Key highlights:
- **UUID-based** primary keys across all models
- **Versioned API routes** (`/api/v1/...`) with modular file organization
- **Service-layer architecture** separating business logic from controllers
- **Enum-driven domain logic** for statuses, types, and transitions
- **Full order lifecycle** from placement to delivery with rider assignment, payouts, and loyalty rewards

---

## Architecture & Design Decisions

### Service Layer
Business logic lives in dedicated service classes (e.g., `AuthService`, `PlaceOrderService`, `RiderLocationService`, `VendorDashboardService`). Controllers are thin: they validate input, delegate to services, and return standardized responses.

### Enum-Driven Domain
States and types across the system are strongly typed via PHP enums: `OrderStatus`, `VendorVerificationStatus`, `CancellationReason`, `PayoutStatus`, `RiderAvailability`, `CouponType`, `SettingKey`, etc. This makes transitions explicit and validation trivial.

Enum values are stored as **`tinyInt`** in the database rather than strings, a deliberate performance choice that reduces storage size, speeds up indexed lookups, and keeps the database layer lean while the application layer handles the human-readable mapping.

### Domain Events for Side Effects
Anything that should happen *because* an order changed (notifying the vendor, refreshing recommendation inputs, reversing a payment at the gateway) is raised as a domain event (`OrderPlaced`, `OrderCancelled`, `OrderStatusChanged`) and handled by queued listeners in `app/Listeners/Order`. Adding a new consequence of placing an order no longer means editing `PlaceOrderService`.

Two rules keep this safe:

- Listeners are marked **`$afterCommit = true`**, so a transaction that rolls back never announces work that did not happen.
- Events carry **identifiers and point-in-time values, not models**. A queued listener holding a model re-reads it when it runs, which would let an order that advanced in the meantime be announced with a status contradicting its own message. `OrderStatusChanged` therefore captures the status at the moment of the transition.

What stays *inside* the transaction is anything that must be atomic with the state change: stock, coupon usage, wallet balance, payouts, and loyalty points. Those are invariants, not side effects.

### Compensating Cancellation
Placing an order performs five mutations: it creates the order, decrements stock, consumes a coupon, debits wallet balance, and (for card orders) opens a payment intent. `CancelOrderService` is the single place that knows that list and reverses all of it under a row lock, so the customer and admin cancellation paths cannot drift apart on what cancelling actually undoes. The gateway reversal is handed to a retryable, idempotent job rather than being attempted inline, since it is a network call that must not hold database locks.

### Standardized API Responses
A global response wrapper and API exception to ensures every endpoint returns a consistent JSON envelope (success flag, HTTP status, message, and data), simplifying frontend integration.

### Global N+1 Prevention
Lazy loading is disabled in `AppServiceProvider` in **development mode only**, forcing all relationships to be explicitly eager-loaded and surfacing N+1 query issues during development.

In **production**, lazy loading is allowed to avoid throwing exceptions that could impact real users if an issue slips through.

### Custom Stubs
Modified artisan stubs ensure every generated model, controller, and migration follows the project's conventions (UUID keys, standardized structure) out of the box.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Language | PHP 8.4 |
| Framework | Laravel 12 |
| Authentication | Laravel Sanctum (token + SPA cookie dual strategy) |
| Database | MySQL 8 |
| Cache / Queue Store | Redis via `predis/predis` |
| Real-time Location | Redis (sub-millisecond writes, 5-min TTL) |
| WebSockets | Laravel Reverb (support chat, agent queue, desk presence) |
| Payments | Stripe PHP SDK + Webhook handling |
| Activity Logging | `spatie/laravel-activitylog` |
| Dev Debugging | Laravel Telescope |
| Queue | Laravel Queues (multiple named queues) |
| Testing | Pest 4 |

---

## Features

### Multi-Role Authentication
- **5 roles**: Admin, Vendor, Customer, Rider, Support, each with isolated auth routes, middleware, and token scopes
- Login / Logout / Token Refresh
- **Two-Factor Authentication (OTP)**: currently enabled for **Admin** accounts only; additional roles can be configured in `config/two_factor.php` without code changes
- Email verification with rate-limited resend (3 attempts / 10 min)
- Password reset flow per role
- Logout from all other devices on password change: other tokens (mobile) or other sessions (web) are invalidated
- Access revocation on account deactivation: the user's API tokens **and** server-side web sessions are deleted in one transaction, immediately signing them out of **both** mobile and web
- Separate credential validation from access issuance: no access (token or session) is granted until 2FA passes

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
PENDING → ACCEPTED → PREPARING → WAITING_RIDER → RIDER_ASSIGNED → PICKED_UP → DELIVERED
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

### Customer Support & Live Chat
Customers raise a ticket and hold a single threaded conversation with a support agent.

- A ticket carries a subject, category and an optional order. Order-bound categories require an order owned by the requester; unowned and non-existent ids are answered identically with `404`
- Lifecycle `OPEN → ASSIGNED → RESOLVED → CLOSED`, with the legal transitions declared in `TicketStatus::transitions()`
- Replying requires ownership: an agent claims a ticket before writing to it, and the check executes inside the row lock, since a claim read outside the transaction is racy
- Agents return a ticket to the queue with `DELETE .../claim`; admins can take over a ticket held by an absent agent
- Closure belongs to the desk. `RESOLVED` is the agent's verdict and is reversed by a customer reply
- Read receipts drive the unread badge on both sides and cover only messages written by the other party
- Conversations paginate by cursor, ordered by `created_at` and `id`, so messages sharing a timestamp are never skipped
- Open tickets per customer are capped in `config/support.php`
- A ticket held by an absent agent returns to the queue. That sweep also resolves abandoned conversations and closes resolved ones; `awaiting_customer` keeps it away from tickets that are awaiting a reply from the desk. All three run on the schedule rather than on a request: see [Scheduled Commands](#scheduled-commands)
- Messages, queue changes and desk availability are pushed live: [Real-Time Support Chat](#real-time-support-chat-reverb)

**Agent presence.** Availability is derived from agent activity rather than a client-side timer. Customers receive a single boolean and a message:

```json
{ "support_available": true, "message": "Our support team is online. Someone will reply to you shortly." }
```

Agent identities and headcount are never exposed.

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
- **Vendor Dashboard**: period stats (orders, earnings, commission, AOV), monthly earnings chart, top products, latest reviews, filterable by store, month, year
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
- **CORS**: restricted to `FRONTEND_URL` env, no hardcoded origins
- **`BlockDirectAccessMiddleware`**: blocks requests from unauthorized origins
- **Custom rate limiting middleware**:
  - Auth routes: 6 req/min
  - Form submissions: 20 req/min
  - General routes: 60 req/min
  - Combined IP + user identifier to prevent bypass
- **`.htaccess` hardening**: blocks oversized query strings, empty User-Agents, path traversal, sensitive file access; adds `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy` headers
- **Argon2id password hashing** at the OWASP cost floor (19 MiB memory, 2 iterations, 1 thread), replacing the default bcrypt. Unlike bcrypt it is memory-hard, so GPU and ASIC cracking cannot be parallelised cheaply. `rehash_on_login` transparently upgrades any hash whose parameters drift from the config
- **Password changes require the current password** (`required_with:password`), so a stolen session cookie or Bearer token is not enough on its own to take an account over. The attacker would also need the password they are trying to replace
- On a successful change every other session and token is revoked in the same request, leaving only the device that made the change signed in, so a thief who was already inside is evicted
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
│   │   ├── Support/           # Support agent desk (ticket queue, chat, presence)
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
│   ├── Support/               # SupportTicketService, SupportMessageService, SupportPresenceService
│   └── ...                    # OrderPricingCalculatorService, PlaceOrderService, PayoutServices, etc.
└── Traits/                    # ApiResponse, AdminAuthorization, ResolvesAuthCustomer, etc.

routes/api/v1/
├── admin/                     # Admin route files (auth, users, stores, orders, etc.)
├── vendor/                    # Vendor route files
├── customer/                  # Customer route files
├── rider/                     # Rider route files
├── support/                   # Support agent route files
└── public/                    # Public unauthenticated routes
```

---

## Getting Started

### Prerequisites

- PHP 8.3+, built with Argon2id support (`php -r 'var_dump(defined("PASSWORD_ARGON2ID"));'`)
- Composer
- MySQL 8.0+
- Redis (see below for Windows setup)
- Stripe CLI (for local webhook testing)

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
# Set DB_*, REDIS_*, STRIPE_*, FRONTEND_URL, SANCTUM_STATEFUL_DOMAINS, MAIL_* variables

# 6. Run migrations
php artisan migrate

# 7. Seed the database
php artisan db:seed

# 8. Link storage
php artisan storage:link
```

### Web (SPA) Session Config

The API serves **mobile clients via Bearer tokens** and the **web SPA via httpOnly session cookies** from the same endpoints. The client is detected per request from its origin. For the SPA to be treated as stateful, add its host(s) to `.env`. **The port is significant**: `localhost` does not match `localhost:5173`:

```env
# Hosts whose requests use cookie/session auth (the SPA).
# Must match the host of FRONTEND_URL.
SANCTUM_STATEFUL_DOMAINS=localhost:5173

# Sessions are stored server-side so they can be revoked individually
SESSION_DRIVER=database

# Local runs over http, so Secure cookies would not be returned by the browser
SESSION_SECURE_COOKIE=false   # set to true in production (https)
SESSION_SAME_SITE=lax
SESSION_DOMAIN=localhost
```

> The SPA must send credentials with every request (`withCredentials: true` in axios / `credentials: 'include'` in fetch) and call `GET /sanctum/csrf-cookie` before its first `POST`.

### Redis Setup (Windows)

This project uses `predis/predis` (pure PHP Redis client), so no PHP extension is required, making it simple to introduce Redis in any environment.

For Windows, install **Memurai** (a Redis-compatible server for Windows):

1. Download **[Memurai for Redis v4.2.2](https://www.memurai.com/)** (`Memurai-Developer-v4.2.2.msi`)
2. Run the installer. Memurai registers as a Windows service automatically
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

### Stripe Test Setup & Payment Verification

#### 1) Install and authenticate Stripe CLI
```bash
# Install Stripe CLI, then:
stripe login
```

#### 2) Start webhook listener
```bash
stripe listen --forward-to localhost:8000/api/v1/stripe/webhook
```
Copy the webhook signing secret from the CLI output and add your Stripe test credentials from your Stripe dashboard (test mode) into your `.env`:
```env
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```
> Get `STRIPE_KEY` and `STRIPE_SECRET` from your Stripe account in test mode (not live mode).

#### 3) Trigger a test payment from your API
From a customer account, call the place order endpoint using:
- `payment_method = 1` (Visa test card)

#### 4) Verify the payment via **Stripe CLI**
Copy the `pi_XXXXXXXX` from the order response and run:
```bash
stripe payment_intents confirm pi_XXXXXXXX --payment-method=pm_card_visa
```

**Notes**
- `pm_card_visa` is a Stripe test payment method that always succeeds.
- Keep the `stripe listen` command running while testing.
- Ensure your local server is running on `localhost:8000`.

### Queue Workers

The project uses **named queues** for priority and isolation. Start all workers with:

```bash
php artisan queue:work \
  --queue=rider-matching,payments,rider-assigned,new-order,admin-order-escalation,order-status-change,cancel-order,default,refresh-user-preference
```

> **Queue priority**: `rider-matching` is first, so rider assignment jobs are processed before all other notifications, ensuring minimal order delays.

For production, use **Supervisor** to keep workers running persistently.

---

## Admin First Login (OTP Walkthrough)

Admin accounts use **Two-Factor Authentication (OTP)** on every login. The OTP is sent via email, so two things must be in place before attempting to log in:

1. **Queue worker is running**: OTP emails are dispatched as queued jobs.
2. **Mail is configured**: the project uses [Mailtrap](https://mailtrap.io) for local email testing.

### 1) Configure Mailtrap

Add your Mailtrap SMTP credentials to `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_mailtrap_username
MAIL_PASSWORD=your_mailtrap_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@marketplace.com"
MAIL_FROM_NAME="Marketplace Platform"
```

> Get your credentials from your [Mailtrap inbox](https://mailtrap.io) under **SMTP Settings**.

### 2) Start the Queue Worker

The OTP email is dispatched via a queued job, so the worker must be running or the email will never be sent:

```bash
php artisan queue:work \
  --queue=rider-matching,payments,rider-assigned,new-order,admin-order-escalation,order-status-change,cancel-order,default,refresh-user-preference
```

### 3) Login Step 1: Submit Credentials

`POST /api/v1/admin/auth/login`

```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

If credentials are valid, the response returns a short-lived `temp_token` instead of a full access token. No token is issued until OTP is verified.

```json
{
    "data": {
        "temp_token": "4mhyneZkYvYP6sR60vFgvVmCajwM6LoyRYyThjdQLMc3XqDBiB1FCpHcMRpjhiq6"
    },
    "status": true,
    "code": 200
}
```

At this point, an OTP code has been dispatched to the admin's email via the queue. Check your Mailtrap inbox for it.

### 4) Login Step 2: Verify OTP

`POST /api/v1/admin/auth/otp/verify`

```json
{
  "temp_token": "4mhyneZkYvYP6sR60vFgvVmCajwM6LoyRYyThjdQLMc3XqDBiB1FCpHcMRpjhiq6",
  "code": "355944"
}
```

On success, the full access and refresh tokens are issued:

```json
{
    "message": "Welcome back to your account!",
    "data": {
        "access_token": "1|eKxiNn2OGVci3QYieF2erGpn6L3l07rO54G6GVN96992af06",
        "refresh_token": "2|Ault4qJ7gA5C2eDoEJzYtpi5EcndXk4CIOXBj3FEa0aaf335",
        "user": {
            "id": "019d91b5-dfb9-7067-9608-f7a6ddc2df4c",
            "name": "Admin",
            "email": "admin@example.com",
            "role": 1
        }
    },
    "status": true,
    "code": 200
}
```

Use `access_token` as a Bearer token for all subsequent admin requests. The OTP is valid for **30 days** on trusted devices, so repeat logins from the same browser will skip the OTP step.

> **Web SPA**: from a stateful origin the same two-step flow returns a `user` and sets an httpOnly session cookie instead of `access_token` / `refresh_token`. See [Web (SPA) Login Flow](#web-spa-login-flow).

> **Troubleshooting**: If the OTP email never arrives, confirm the queue worker is running and that your Mailtrap credentials are correct. You can also check Laravel Telescope at `http://localhost:8000/telescope` to inspect the queued job and mail status.

---

## Web (SPA) Login Flow

Web clients authenticate **statefully**: no tokens are stored in the browser. The session lives in an **httpOnly cookie** that JavaScript cannot read, which is the main advantage over keeping a token in `localStorage`. The same auth endpoints are used as mobile; the API returns a session (not tokens) when the request comes from a stateful origin (see [`SANCTUM_STATEFUL_DOMAINS`](#web-spa-session-config)).

### 1) Prime the CSRF cookie

`GET /sanctum/csrf-cookie` → sets the `XSRF-TOKEN` (JS-readable) and session (httpOnly) cookies. Axios then returns the token automatically in the `X-XSRF-TOKEN` header on later requests.

### 2) Login

`POST /api/v1/customer/auth/login` with the `X-XSRF-TOKEN` header. The response contains only the `user` (**no tokens**) and sets the session cookie:

```json
{
    "message": "Welcome back to your account!",
    "data": {
        "user": {
            "id": "019ddc0a-3e2c-72c7-ac7c-80af5b99605b",
            "name": "Customer 1",
            "email": "customer1@demo.test",
            "role": 3
        }
    },
    "status": true,
    "code": 200
}
```

### 3) Authenticated requests

Send subsequent requests with the cookie. `auth:sanctum` resolves the user from the session, and the role middleware (`isAdmin`, `isCustomer`, …) authorizes by the user's `role`, **identical to the token path**. On a hard refresh the browser keeps the cookie but loses in-memory state, so the SPA re-hydrates by calling a "current user" endpoint: `200` → logged in (use `role` to render the UI), `401` → redirect to login.

```bash
# 1. CSRF cookie
curl -c jar.txt -H "Origin: http://localhost:5173" http://localhost:8000/sanctum/csrf-cookie

# 2. Login (send the decoded XSRF-TOKEN cookie back as the header)
curl -b jar.txt -c jar.txt -H "Origin: http://localhost:5173" -H "X-XSRF-TOKEN: <token>" \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"identifier":"customer1@demo.test","password":"password"}' \
  http://localhost:8000/api/v1/customer/auth/login

# 3. Cookie-authenticated request, no Authorization header
curl -b jar.txt -H "Origin: http://localhost:5173" -H "Accept: application/json" \
  http://localhost:8000/api/v1/customer/profile
```

> **Notes**: Login rotates the CSRF token (via session regeneration), so read the `XSRF-TOKEN` cookie fresh before each request; axios does this automatically. Token **refresh** (`/refresh`) is mobile-only and returns `403` for web sessions.

---

## API Overview

All endpoints follow the versioned prefix `/api/v1/`.

### Role-Based Route Groups

| Prefix | Guard | Description |
|---|---|---|
| `/api/v1/admin/auth/` | None | Admin login, logout, refresh, OTP |
| `/api/v1/admin/` | `admin` middleware | Admin management endpoints |
| `/api/v1/vendor/auth/` | None | Vendor login, register, verify, reset |
| `/api/v1/vendor/` | `vendor` middleware | Vendor store/order management |
| `/api/v1/customer/auth/` | None | Customer register, login, verify, reset |
| `/api/v1/customer/` | `customer` middleware | Customer orders, addresses, favorites |
| `/api/v1/rider/auth/` | None | Rider login, logout, refresh |
| `/api/v1/rider/` | `rider` middleware | Rider orders, location, profile |
| `/api/v1/support/auth/` | None | Support agent login, logout, refresh, OTP, password reset |
| `/api/v1/support/` | `isSupport` middleware | Ticket queue, claiming, replies, availability, profile |
| `/api/v1/` (public) | None | Business categories, stores, products |

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
<summary><strong>Support (Customer)</strong></summary>

```
GET    /api/v1/customer/support/availability            # Is the desk staffed?
POST   /api/v1/customer/support/tickets                 # Open a ticket
GET    /api/v1/customer/support/tickets                 # My tickets (+ unread counts)
GET    /api/v1/customer/support/tickets/{id}            # Ticket details
GET    /api/v1/customer/support/tickets/{id}/messages   # Conversation (cursor paginated)
POST   /api/v1/customer/support/tickets/{id}/messages   # Reply
POST   /api/v1/customer/support/tickets/{id}/read       # Mark the agent's messages read
```
</details>

<details>
<summary><strong>Support Desk (Agent)</strong></summary>

```
GET    /api/v1/support/availability                     # My presence
PATCH  /api/v1/support/availability                     # Go online / offline (heartbeat)
GET    /api/v1/support/tickets                          # Queue (?assignment=unassigned|mine)
GET    /api/v1/support/tickets/{id}                     # Ticket details
POST   /api/v1/support/tickets/{id}/claim               # Take the ticket
DELETE /api/v1/support/tickets/{id}/claim               # Hand it back to the queue
PATCH  /api/v1/support/tickets/{id}/status              # Resolve or close
GET    /api/v1/support/tickets/{id}/messages            # Conversation (cursor paginated)
POST   /api/v1/support/tickets/{id}/messages            # Reply
POST   /api/v1/support/tickets/{id}/read                # Mark the customer's messages read
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
 
### Auth Strategy (Dual)
- **Mobile clients** → Bearer token (Sanctum Personal Access Tokens); extended `PersonalAccessToken` model adds `session_id` to pair access/refresh tokens
- **Web SPA** → stateful **httpOnly session cookie**, CSRF-protected, not readable by JavaScript (no tokens exposed to the browser)
- **Server-side sessions** (`database` driver) are revocable: they can be invalidated individually on deactivation, password change, or per-device sign-out, which a client-stored session cannot
- The client is detected per request from its origin (`SANCTUM_STATEFUL_DOMAINS`), so one set of endpoints serves both; role authorization (`isAdmin`, `isCustomer`, …) is guard-agnostic and reads `user.role`
- Only `login` and the admin OTP `verify` branch on the client. `register`, password reset and email verification grant no credentials, so they are identical for both clients and are not duplicated per platform
- After login the branch follows **how the request authenticated** (Sanctum's `TransientToken` marks a cookie session), not the `Origin` header, so a Bearer token sent from the SPA's own origin still takes the token path
- Token refresh is a mobile-only concept, so web sessions are rejected from the `/refresh` endpoint
- A refresh retires the whole pair by `session_id`, so a stale access token cannot outlive the rotation
### Two-Factor Authentication
OTP-based 2FA is currently enabled for **Admin** accounts. Other roles can be enabled by adding them to `config/two_factor.php`.
 
Admin 2FA flow:
1. Credentials validated → OTP emailed if 2FA enabled
2. OTP submitted → token issued only after verification
3. Trusted devices bypass OTP for 30 days (browser cookie-based)
### CORS
- Allowed origins restricted to the single `FRONTEND_URL` env - no hardcoded origins
- Credentials support enabled for cookie-based SPA auth
- All origins allowed in local environment for testing tools (e.g. Postman, ApiDog)
### Origin Enforcement (`BlockDirectAccessMiddleware`)
Lightweight request filter that reduces noise from direct access attempts and automated scanners, and keeps foreign browsers away from the auth endpoints. A browser always attaches the origin of the page making the call and cannot forge it, so that header decides:

| Request | Result |
|---|---|
| Page origin present, first-party | Allowed |
| Page origin present, anything else | `404`, including requests carrying a Bearer token, so a stolen token cannot be replayed from another site |
| No page origin, Bearer token | Allowed (mobile client) |
| No page origin, `api/v1/*/auth/*` | Allowed, a client on its way to its first token has neither header nor token |
| No page origin, anything else | `404` |

- `OPTIONS` preflight requests are allowed through before origin validation
- `HandleCors` runs before `BlockDirectAccess` (Laravel's default global order) so blocked responses still carry CORS headers
- Bypassed in local and testing, so origin blocking is only observable in production. `tests/Feature/Auth/LoginOriginFilterTest.php` drives the middleware with the environment forced
### Rate Limiting (Custom Middleware)
```
Auth endpoints:         6 requests / minute
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

## Real-Time Support Chat (Reverb)

Messages, queue changes and desk availability are delivered over websockets by
**[Laravel Reverb](https://reverb.laravel.com)**. Broadcasts carry no authority:
every screen is driven by the REST API and degrades to it when the socket drops.

### Channels

| Channel | Subscribers | Events |
|---|---|---|
| `tickets.{id}` | the requester and any agent | `message.sent`, `ticket.updated` |
| `support.queue` | agents and admins | `ticket.updated` |
| `support.availability` | any authenticated user | `desk.availability` |

Authorisation is three callbacks in `routes/channels.php`, one per channel. The
handshake runs through `POST /api/v1/broadcasting/auth` under the same Sanctum
token and ability as the rest of the API, so token and cookie clients subscribe
identically. It is throttled as a read rather than as a write: the route carries
no name of its own, so it misses the staff exemption in
`RateLimiterThrottleMiddleware` and would otherwise be judged by its method, and
a reconnect re-authorises every open channel without anyone touching the page.
An account has one write budget, so counting those would have let a bad
connection throttle a customer out of checkout.

### Design notes

**Dispatch follows the commit.** The two events raised from inside a write,
`SupportMessageSent` and `SupportTicketUpdated`, implement
`ShouldDispatchAfterCommit`, so delivery waits for the outermost transaction
rather than the nearest one. Opening a ticket nests the first message inside the
ticket's own transaction, and a listener reacting mid-transaction would query a
row no other connection can see yet.

**The queue hears about moves, not writes.** `SupportTicketObserver` announces a
ticket when its status, its agent or its last message time changed, and stays
quiet otherwise: marking a conversation read touches the messages and not the
ticket, so it says nothing at all. The last message time is in that list because
the desk needs it. A reply reorders the queue and owes an unread mark to a ticket
nobody has open, and neither of those can be learned from the ticket's own
channel, which is the one channel an agent working elsewhere is not listening
to. A new ticket is announced by that same rule, through the opening message it
is created with, and so arrives with the time the queue sorts on already set.

**Payloads are reader-independent.** A broadcast reaches every subscriber at
once, so events carry `agent_id` instead of `is_mine` and omit unread counts.
Each console derives its own view.

**Presence requires no polling.** `RefreshSupportPresence` records an agent's
ordinary requests as their heartbeat from `terminate()`, after the response has
been sent, and no more than once a minute. Availability is announced only on
change, and asking for it re-evaluates it, so a console closed without signing
out is noticed by the next customer who looks rather than only by the sweep.
Neither console runs a timer.

Who counts as being on the desk is the `present` scope on `SupportAgentStatus`,
and it is asked in exactly two places: whether to tell a customer support is
open, and whose tickets the sweep hands back.

**Reads stay reads.** Reclaiming the tickets of an agent who vanished is written
work, so it belongs to `support:sweep-tickets` alone. Listing the queue does not
quietly reassign anything on the way past.

All three sweeps are triggered by the *absence* of an event, which nothing can
dispatch, so something has to look on a clock. Doing that lazily on the queue
listing would work, and would cost nothing while the desk is quiet, but it puts
row locks, writes and broadcasts behind a `GET`. The scheduler is already running
for six other commands, so the sweep costs one more entry rather than one more
process, and `onOneServer()` keeps it to a single host behind a load balancer.

**One event, two audiences.** `SupportTicketUpdated` is broadcast to the shared
queue and to the ticket's own channel, covering the agent list and the customer's
view of their own ticket without a second event class.

### Setup

Broadcasting is not enabled in a default Laravel installation:

```bash
php artisan install:broadcasting   # select Reverb when prompted, or pass --reverb
```

This publishes `config/broadcasting.php`, `config/reverb.php` and
`routes/channels.php`, and writes the `REVERB_*` credentials into `.env`. Set
`BROADCAST_CONNECTION=reverb` afterwards.

> The installer adds `channels:` to `withRouting()` in `bootstrap/app.php`, which
> registers an auth route on the `web` middleware. This project removes it and
> registers broadcasting once under `api/v1`, so token and cookie clients share a
> single endpoint.

### Local processes

```bash
php artisan serve          # the API
php artisan reverb:start   # the websocket server (--debug prints every frame)
php artisan schedule:work  # the ticket sweeps, every 15 minutes
```

Support broadcasts are dispatched inline rather than queued, so the chat requires
no worker. The named queues in [Queue Workers](#queue-workers) remain necessary
for the rest of the application.

### Demo consoles

Two Blade pages exercise the feature end to end and consume the same endpoints as
any other client. They are registered in `routes/web.php` only when the
application is running locally:

| Page | Local URL |
|---|---|
| `resources/views/support/customer.blade.php` | http://localhost:8000/support/customer |
| `resources/views/support/agent.blade.php` | http://localhost:8000/support/agent |

Each page takes an access token. Open the queue as the agent and go online, raise
a ticket as the customer, then claim it on the desk.

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

- **Redis TTL**: 5 minutes, stale riders auto-removed
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
  ├── Set payment_status = PAID (cash) or set to PAID directly if using visa via Stripe webhook (card)
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
| Vendor store list | Cached per vendor (60 days) |
| Notification listing | Cursor-based pagination (better than offset for large sets) |
| Settings | Cached indefinitely, cleared on admin update |
| Indexes | Added on `users.role` to avoid full table scans, leveraging B-tree indexes for O(log n) lookups instead of O(n); additional indexes on `orders`, `order_items`, `reviews` to optimize aggregation queries |
| N+1 prevention | `Model::preventLazyLoading()` enabled globally |
| Job payloads | Primitive IDs dispatched (not full models) to reduce queue memory |
| Notifications | Queued async on dedicated named queues |
| Race conditions | `lockForUpdate()` on order numbers, coupons, stock, reviews |
| Query optimization | Selective column retrieval, strategic joins over nested ORM relations where appropriate, raw queries for heavy aggregations, proper data types (e.g., `tinyint` for flags), and index-aware query design |

---

## Scheduled Commands

| Command | Schedule | Description |
|---|---|---|
| `DeleteExpiredTokens` | Daily at **2:00 AM** | Removes expired personal access tokens from the database |
| `DeleteExpiredTwoFactorData` | Daily at **1:00 AM** | Deletes unused expired OTP codes and stale trusted device records |
| `MarkStaleRidersUnavailable` | Every **10 minutes** | Marks riders as unavailable if they have not sent a location update in the last 10 minutes, which ensures stale GPS entries don't interfere with rider assignment |
| `CancelAbandonedOrders` | Every **5 minutes** | Cancels card orders whose payment was never completed after 30 minutes, releasing the stock, coupon and wallet balance they were holding |
| Activity log cleanup | Daily at **12:00 AM** | Prunes `spatie/laravel-activitylog` records older than **90 days** |
| `RefreshCustomerPreferences` | Daily at **03:00 AM** (full rebuild) | Rebuilds all customer preference scores from scratch |
| `SweepSupportTickets` | Every **15 minutes** | Returns tickets held by agents who went absent, resolves conversations the customer walked away from, and closes resolved tickets past their window. The interval tracks the tightest of the three thresholds in `config/support.php`, the 15 minutes an agent may be away, so a stranded ticket waits at most 30 minutes for another agent. On an idle desk a run is four indexed lookups that match nothing |

---

## Testing

The suite is written with **[Pest](https://pestphp.com/)** and runs against a real MySQL database.

### Test Database Setup

Create a dedicated schema once. It is wiped and re-migrated by the suite, so never point this at your development database:

```bash
mysql -u root -e "CREATE DATABASE marketplace_platform_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

`phpunit.xml` already directs tests at it:

```xml
<env name="DB_CONNECTION" value="mysql"/>
<env name="DB_DATABASE" value="marketplace_platform_test"/>
```

Connection host, user and password are inherited from your `.env`. Everything else is overridden for tests: cache and session run in memory, the queue runs synchronously, and mail is captured instead of sent.

### Running the Suite

```bash
php artisan test                          # everything
php artisan test --filter=CancelOrder     # one file or one test name
php artisan test tests/Feature/Auth       # one directory
php artisan test --parallel               # across CPU cores
```

The first run migrates the test schema automatically. Each test runs inside a transaction that is rolled back afterwards, so tests never leak state into one another.

### How the Tests Are Organised

| Path | Covers |
|------|--------|
| `tests/Feature/Order/PlaceOrderTest.php` | Pricing, stock, coupon and wallet rules at placement |
| `tests/Feature/Order/CancelOrderTest.php` | Cancellation compensation, as a place-then-cancel round trip |
| `tests/Feature/Order/OrderLifecycleTest.php` | The whole flow from HTTP placement through to delivery and payouts |
| `tests/Feature/Order/OrderListenersTest.php` | Each domain-event listener in isolation |
| `tests/Feature/Auth/` | Registration, login, tokens, email verification, password reset, 2FA |
| `tests/Feature/Auth/HybridAuthTest.php` | The session/token fork: which branch a request takes, and that neither leaks into the other |
| `tests/Feature/Auth/LoginOriginFilterTest.php` | That the production origin filter blocks foreign browsers without locking out mobile clients |
| `tests/Feature/Payment/StripeWebhookTest.php` | Webhook replay, out-of-order events, amount mismatch, refund-on-cancelled |
| `tests/Feature/Payment/StripeWebhookEndpointTest.php` | Signature verification over the real HTTP endpoint |
| `tests/Feature/Payment/WebhookReachabilityTest.php` | That the webhook survives the production request filters |
| `tests/Feature/Support/SupportTicketTest.php` | The support desk: opening, claiming, replying, presence, read receipts, cursor paging and the auto-close sweep |
| `tests/Unit/OrderStatusTest.php` | The order status transition graph |

> Stripe tests build `Stripe\Event` objects with `Event::constructFrom()` and sign payloads by hand, so the whole payment path is covered without a network call or a Stripe key.

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

Built with ❤️ using **PHP** . **Laravel** · **Redis** · **Stripe**

</div>