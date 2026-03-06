# Stripe Billing DDD Integration

A Laravel 12 implementation of a Stripe billing system using Domain-Driven Design (DDD). This project demonstrates a clean, provider-agnostic approach to subscription management, including price modifications, partial refunds, and robust webhook processing.

## Features

- **Recurring Subscriptions**: Add users to recurring payment plans with confirmation logic.
- **Price Modifications**: Update a subscription's periodic costs with automated user notifications.
- **Partial Refunds**: Automatically issue refunds when a subscription's price decreases.
- **Payment Retries**: Support for retrying failed payments via dedicated endpoints and webhook alerts.
- **Clean Architecture**: Strict separation between Domain, Application, and Infrastructure layers.
- **Webhook Integration**: Securely verified Stripe signatures and idempotent processing for `invoice.paid`, `customer.subscription.updated`, and more.

## Setup

1. **Install Dependencies**:
   ```bash
   ./vendor/bin/sail composer install
   ```

2. **Environment Configuration**:
   Copy `.env.example` to `.env` and configure:
   - `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`
   - Database credentials

3. **Database & Migrations**:
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

## Usage

### Endpoints

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/subscriptions` | Create a new subscription. |
| `PATCH` | `/api/subscriptions/{id}` | Update an existing subscription (triggers price change logic). |
| `POST` | `/api/subscriptions/retry` | Retry a failed payment for a specific invoice. |

### Example: Create a Subscription
Send a `POST` request to `/api/subscriptions`:
- **Payload**:
  ```json
  {
    "customer_id": "cus_123456789",
    "plan_id": "price_H5ggY9IzS9s2a1",
    "payment_method_id": "pm_card_visa"
  }
  ```

### Example: Update a Subscription
Send a `PATCH` request to `/api/subscriptions/sub_12345`:
- **Payload**:
  ```json
  {
    "plan_id": "price_NEW_PLAN_ID"
  }
  ```

### CLI Synchronisation
A maintenance command is available to pull existing Stripe objects into your database. Useful when bootstrapping a new environment or recovering missed webhooks.

```bash
# run from the project root
./vendor/bin/sail artisan billing:stripe-sync
```

The command will iterate over all products, prices, customers and subscriptions in your Stripe account and upsert local records.
## Architecture — Billing Bounded Context

The project follows a strict Domain-Driven Design layout:

- **Domain**: Pure PHP entities (`Subscription`), Value Objects (`Money`, `SubscriptionStatus`), and Contracts (`PaymentGatewayInterface`). No framework dependencies.
- **Application**: Orchestrates business logic via Actions (`CreateSubscription`, `UpdateSubscription`, `RetryPayment`) and DTOs.
- **Infrastructure**: Implementation details for Stripe (`StripePaymentGateway`), persistence (`EloquentSubscriptionRepository`), and webhooks (`ProcessStripeWebhook`).

## Webhook Handling

Webhooks are handled via `spatie/laravel-webhook-client`.
- **Endpoint**: `/api/stripe-webhooks` (Configure Stripe to point here).
- **Processing**: `ProcessStripeWebhook` job handles idempotent updates and dispatches domain events.
- **Verification**: `StripeSignatureValidator` ensures payload integrity using the Stripe SDK.

## Quality & Testing

To ensure code quality and project integrity:

```bash
# Run PHPStan (Level 6)
./vendor/bin/sail composer analyze

# Run Pint (Linting)
./vendor/bin/sail composer lint

# Run Tests
./vendor/bin/sail composer test
```

---
`composer lint && composer analyze`

