# Stripe Billing DDD Integration

A Laravel 12 implementation of a Stripe billing system using Domain-Driven Design (DDD).

## Setup

1. **Install Dependencies**:
   ```bash
   composer install
   ```

2. **Environment Configuration**:
   Copy `.env.example` to `.env` and configure:
   - `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
   - Database credentials

3. **Database & Migrations**:
   ```bash
   php artisan migrate
   ```

## Usage

### Create a Subscription
Send a `POST` request to `/api/subscriptions`:
- **Endpoint**: `POST /api/subscriptions`
- **Payload**:
  ```json
  {
    "plan_id": "price_H5ggY9IzS9s2a1",
    "payment_method_id": "pm_card_visa"
  }
  ```
- **Auth**: Requires Sanctum authentication.

## Webhook Handling

Webhooks are handled via `spatie/laravel-webhook-client`.
- **Endpoint**: `/webhook-client-static/default` (Stripe points here).
- **Processing**: `ProcessStripeWebhook` job parses the payload and executes the corresponding Domain Action (e.g., `CancelSubscription`).
- **Profile**: `StripeWebhookProfile` filters events to ensure only relevant Stripe events are processed.

## Assumptions

- **Architecture**: Strict DDD layers (Domain, Application, Infrastructure).
- **Auth**: Users are authenticated via Laravel Sanctum.
- **Provider**: Stripe is the primary payment gateway.
- **Reliability**: Webhooks are the source of truth for subscription state updates.

---
`composer lint && composer analyze`
