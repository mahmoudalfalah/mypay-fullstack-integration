# Auto Parts E-Commerce — MyPay Payment Integration

**Note:** Curated excerpt from a private production codebase — not a working clone.

> Extracted from the same private production codebase as the [architecture showcase](https://github.com/mahmoudalfalah/ecommerce-architecture-showcase). This covers everything that happens between "place order" and "order confirmed" — stock reservation, payment gateway, webhook fulfillment, and the callback page.

**Live:** [autoparts.malfalah.com](https://autoparts.malfalah.com) &nbsp;|&nbsp; **Admin:** [autoparts.malfalah.com/admin](https://autoparts.malfalah.com/admin) &nbsp;|&nbsp; `demo@malfalah.com` / `logindemo` _(read-only)_

![React](https://img.shields.io/badge/React-18-61DAFB?logo=react&logoColor=white&labelColor=20232a)
![TypeScript](https://img.shields.io/badge/TypeScript-5-3178C6?logo=typescript&logoColor=white&labelColor=1a1a2e)
![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white&labelColor=1a1a1a)
![TanStack Query](https://img.shields.io/badge/TanStack_Query-5-FF4154?logo=reactquery&logoColor=white&labelColor=1a1a1a)

---

## The problem this solves

Payment flows have a timing problem. The customer fills the form, gets redirected to the payment page, pays — and somewhere in that window, another customer could buy the last unit of the same product. Or the payment gateway could fire the webhook twice. Or the customer abandons checkout entirely and their reserved stock never comes back.

This integration handles all of that.

---

## Backend

### `CheckoutService.php` — the core flow

When an order is submitted, three things happen inside a single DB transaction:

1. Stock is validated and **pessimistically locked** (`lockForUpdate`) so no other request can touch those rows until the transaction completes.
2. A `CheckoutSession` is created — it stores the full order snapshot and a token the frontend uses to poll status.
3. The payment is created via `MyPayGateway`, and the customer is redirected to the payment URL.

If the gateway call fails, the session is marked `payment_creation_failed` and reservations are cancelled — no partial state left behind.

**Idempotency in `fulfill()`.** When the webhook fires, `fulfill()` runs inside a transaction with `lockForUpdate` on the session. If the session is already `completed` (webhook fired twice), it returns the existing order instead of creating a second one. The order doesn't get placed twice.

---

### `MyPayWebhookService.php` — don't trust the payload

Before touching anything, the service verifies the HMAC-SHA256 signature against the raw request body. Only after that does it parse the JSON and hand off to `CheckoutService::fulfill()`. The webhook route is also excluded from CSRF verification — that's intentional, since MyPay can't send a CSRF token.

---

### `ReleaseExpiredStock.php` — the cleanup job

Reservations have two expiry guards. The first is the `expires_at` column on the reservation itself — `getAvailableStockAttribute()` only counts reservations where `expires_at` is still in the future, so expired ones are ignored automatically without needing a cleanup step. The second is this artisan command — it finds all `initiated` or `payment_pending` sessions past their `expires_at`, marks them `expired`, and explicitly updates the reservation rows. Runs on a schedule. Belt and suspenders.

---

### `ProductStockReservation` — how stock is held without touching the real stock column

Instead of decrementing `products.stock` at checkout start (and having to undo it if payment fails), I create reservation rows. `Product::getAvailableStockAttribute()` computes available stock dynamically:

```php
return max(0, $this->stock - $reservedQuantity);
```

The real stock only decrements when `fulfill()` runs — after payment is confirmed.

---

## Frontend

### `useCheckoutCallbackPage.ts` — the callback page problem

After payment, MyPay redirects back to `/checkout/callback?token=...`. The page polls the session status until it resolves. The tricky part: once we get a terminal status, we lock it in a ref — because when the customer navigates away, TanStack Query clears `data` and the status briefly becomes undefined before the page fully unmounts. Without the lock, the UI flickers to failed for a split second.

```ts
if (currentStatus === "success") {
  lockedDataRef.current = {
    status: "success",
    orderNumber: data.orderNumber || "",
  };
}

return { status: currentStatus, orderNumber: data.orderNumber || "" };
```

### `useCheckoutPage.ts` — cart integrity before submit

Before the customer can even submit, the hook runs hydration against the live API. Three things can go wrong:

1. **Product was deleted** — removed from cart, customer is redirected back.
2. **Product became invisible** — same, but it'll return to their cart if the admin re-enables it.
3. **Stock dropped below requested quantity** — checkout is blocked, the specific item is flagged in the UI.

This runs on mount, not on submit — so the customer sees the problem before they fill the form, not after.

---

## File structure

```
api/
└── app/
    ├── Console/Commands/ReleaseExpiredStock.php
    ├── Enums/                        # CheckoutSessionStatus, ProductReservationStatus
    ├── Exceptions/
    │   ├── Checkout/                 # SessionNotFoundException, SessionExpiredException
    │   ├── Inventory/                # InsufficientStockException, ProductUnavailableException
    │   └── MyPay/                    # Gateway + Webhook exception hierarchy
    ├── Http/
    │   ├── Controllers/Store/        # CheckoutController, MyPayWebhookController
    │   ├── Middleware/               # ReadOnlyDemoMiddleware
    │   ├── Requests/Store/           # StoreOrderRequest
    │   └── Resources/Store/          # CheckoutSessionResource, ProductResource
    ├── Models/                       # CheckoutSession, ProductStockReservation, Product
    └── Services/Store/
        ├── Payment/MyPayGateway.php
        ├── CheckoutService.php
        ├── MyPayWebhookService.php
        └── OrderService.php

client/src/features/checkout/
├── configs/    # Status → UI content mapping
├── hooks/      # useCheckoutPage, useCheckoutCallbackPage, useCheckoutStatus
├── pages/      # CheckoutCallbackPage
├── services/   # checkout.service.ts
└── types/      # CheckoutSession, CallbackContentConfig
```

---

> Full production repo is private per client agreement. Happy to walk through any part of this in more detail.
