# Billing Module — DDD Directory Structure

## Overview

- **Pragmatic DDD** — Laravel contracts permitted where they reduce noise
- **Single Action Controllers** — one class, one responsibility, `__invoke`
- **Spatie Webhook Client** — handles signature verification, payload storage, queuing
- **Provider agnostic Domain** — Stripe specifics absorbed by Infrastructure only

---

## Full Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Subscriptions/
│   │   │   └── CreateSubscriptionController.php   # __invoke → CreateSubscription action
│   │   └── Webhooks/
│   │       └── StripeWebhookController.php        # Handed entirely to Spatie - stays thin
│   └── Requests/
│       └── Subscriptions/
│           └── CreateSubscriptionHttpRequest.php  # Laravel FormRequest - validation only
├── Console/
│   └── Commands/
│       └── Subscriptions/
│           └── CreateSubscriptionCommand.php      # Builds DTO, calls same Action as controller
└── Providers/
    └── BillingServiceProvider.php                 # Binds interfaces to implementations


src/
└── Billing/                                       # Bounded Context
    │
    ├── Domain/                                    # No framework dependencies (ideally)
    │   ├── Entities/
    │   │   └── Subscription.php                   # Aggregate root - pure PHP
    │   │
    │   ├── ValueObjects/
    │   │   ├── SubscriptionStatus.php             # Backed enum: active|pending|past_due|cancelled
    │   │   ├── SubscriptionId.php                 # Wraps UUID - avoids primitive obsession
    │   │   └── Money.php                          # Amount + currency - if handling amounts
    │   │
    │   ├── Contracts/
    │   │   ├── SubscriptionRepositoryInterface.php  # Persistence contract
    │   │   └── PaymentGatewayInterface.php          # Gateway contract - thin, provider agnostic
    │   │
    │   ├── Events/
    │   │   ├── SubscriptionCreated.php            # Dispatched after successful subscription
    │   │   └── SubscriptionPaymentFailed.php      # Raised when webhook signals failure
    │   │
    │   └── Exceptions/
    │       ├── SubscriptionException.php
    │       └── PaymentFailedException.php
    │
    ├── Application/                               # Orchestration - knows Domain, not Infrastructure
    │   ├── Actions/
    │   │   └── CreateSubscription.php             # Core use case
    │   │
    │   └── DTOs/
    │       ├── CreateSubscriptionDTO.php          # Input - built by controller OR command
    │       └── SubscriptionResult.php             # Output - normalised from gateway response
    │
    └── Infrastructure/                            # All third-party and framework concerns
        │
        ├── Stripe/
        │   ├── StripePaymentGateway.php           # Implements PaymentGatewayInterface
        │   ├── ProcessStripeWebhook.php           # Implements Spatie ProcessWebhookJob
        │   └── StripeWebhookProfile.php           # Implements Spatie WebhookProfile
        │                                          # Declares which events we care about
        │
        └── Persistence/
            ├── Migrations/                        # Module-specific migrations
            │   ├── create_subscriptions_table.php
            │   └── create_webhook_calls_table.php
            ├── EloquentSubscriptionRepository.php # Implements SubscriptionRepositoryInterface
            └── SubscriptionModel.php              # Eloquent model - Infrastructure only
```

---

## How The Layers Communicate

```
HTTP Request / CLI Command
        │
        ▼
[ Controller / Command ]        # Builds DTO from input source
        │
        ▼
[ Application Action ]          # Orchestrates - calls Gateway + Repository
        │
        ├──▶ [ PaymentGatewayInterface ] ──▶ [ StripePaymentGateway ]
        │                                            │
        │                                     Stripe SDK call
        │                                            │
        │                                    returns SubscriptionResult
        │
        ├──▶ [ Domain Entity ]              # Subscription::create() from result
        │
        └──▶ [ RepositoryInterface ] ──▶ [ EloquentSubscriptionRepository ]
                                                     │
                                              persists to DB


Stripe Webhook
        │
        ▼
[ StripeWebhookController ]     # Spatie handles signature + stores raw payload
        │
        ▼
[ ProcessStripeWebhook ]        # Your job class - Infrastructure layer
        │
        ▼
[ match() on event type ] ──▶ [ Application Action ] ──▶ same flow as above
```

---

## Key Files Explained

### `PaymentGatewayInterface.php`
Lives in Domain/Contracts. Deliberately thin — absorbs provider differences via DTOs.
```php
interface PaymentGatewayInterface
{
    public function subscribe(string $customerId, CreateSubscriptionDTO $dto): SubscriptionResult;
}
```

### `SubscriptionStatus.php`
Backed enum prevents invalid states being passed around as raw strings.
```php
enum SubscriptionStatus: string
{
    case Active    = 'active';
    case Pending   = 'pending';    // Important for async flows like Direct Debit
    case PastDue   = 'past_due';
    case Cancelled = 'cancelled';
}
```

### `CreateSubscriptionDTO.php`
The bridge between input source (HTTP or CLI) and the Action.
Built by the controller via `fromRequest()` or by the command directly.
```php
readonly class CreateSubscriptionDTO
{
    public function __construct(
        public string $customerId,
        public string $planId,
        public string $paymentMethodId,
    ) {}

    public static function fromRequest(CreateSubscriptionHttpRequest $request): self
    {
        return new self(
            customerId:      $request->user()->stripe_customer_id,
            planId:          $request->input('plan_id'),
            paymentMethodId: $request->input('payment_method_id'),
        );
    }
}
```

### `CreateSubscription.php` (Action)
Zero knowledge of HTTP or CLI. Zero knowledge of Stripe.
```php
class CreateSubscription
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private SubscriptionRepositoryInterface $repository,
    ) {}

    public function execute(CreateSubscriptionDTO $dto): Subscription
    {
        // 1. Call gateway (outside any DB transaction intentionally)
        $result = $this->gateway->subscribe($dto->customerId, $dto);

        // 2. Build domain entity from normalised result
        $subscription = Subscription::create(
            customerId: $dto->customerId,
            planId:     $dto->planId,
            status:     $result->status,
            gatewayId:  $result->gatewaySubscriptionId,
        );

        // 3. Persist
        $this->repository->save($subscription);

        // 4. Dispatch domain event
        event(new SubscriptionCreated($subscription));

        return $subscription;
    }
}
```

### `ProcessStripeWebhook.php`
Spatie stores the raw payload before this job runs — audit trail + idempotency for free.
```php
class ProcessStripeWebhook extends ProcessWebhookJob
{
    public function handle(): void
    {
        $type    = $this->webhookCall->payload['type'];
        $data    = $this->webhookCall->payload['data']['object'];

        match($type) {
            'customer.subscription.updated' => // handle status changes,
            default => null,
        };
    }
}
```

### `StripeWebhookProfile.php`
Declares which events Spatie should process vs discard. Keeps ProcessStripeWebhook clean.
```php
class StripeWebhookProfile implements WebhookProfile
{
    public function shouldProcess(Request $request): bool
    {
        return in_array($request->json('type'), [
            'customer.subscription.created',
            'customer.subscription.updated',
            'invoice.payment_failed',
        ]);
    }
}
```

### `BillingServiceProvider.php`
Where the "factory" decision actually lives — container binding, not a factory class.
```php
class BillingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PaymentGatewayInterface::class, StripePaymentGateway::class);
        $this->app->bind(SubscriptionRepositoryInterface::class, EloquentSubscriptionRepository::class);
    }
}
```

### `CreateSubscriptionController.php`
Thin. Builds DTO. Calls action. Returns response.
```php
class CreateSubscriptionController
{
    public function __invoke(
        CreateSubscriptionHttpRequest $request,
        CreateSubscription $action,
    ): JsonResponse {
        $subscription = $action->execute(
            CreateSubscriptionDTO::fromRequest($request)
        );

        return response()->json($subscription, 201);
    }
}
```

### `CreateSubscriptionCommand.php`
Identical outcome to the controller. Same action, different input source.
```php
class CreateSubscriptionCommand extends Command
{
    protected $signature = 'billing:subscribe
                            {customerId}
                            {planId}
                            {paymentMethodId}';

    public function handle(CreateSubscription $action): void
    {
        $subscription = $action->execute(new CreateSubscriptionDTO(
            customerId:      $this->argument('customerId'),
            planId:          $this->argument('planId'),
            paymentMethodId: $this->argument('paymentMethodId'),
        ));

        $this->info("Subscription created: {$subscription->id}");
    }
}
```

---

## Interview Talking Points

**On the Factory being replaced by the Service Provider**
> "Rather than a factory class inside the Application layer, I'm using Laravel's service container to resolve the correct gateway implementation. The Action just type-hints the interface — the container does the rest. If we add GoCardless later, we update one binding in the service provider, nothing else changes."

**On Spatie Webhook Client**
> "Spatie handles the infrastructure concerns of webhook processing — signature verification, raw payload storage, and queuing. This means every webhook is stored before it's processed, which gives us an audit trail and makes it straightforward to replay failed events. My code only has to care about what to do with the event, not how to receive it safely."

**On CLI and HTTP using the same Action**
> "Both the controller and the command build a DTO and call the same Action. The Action has no knowledge of how it was triggered. This means the business logic is tested once, not twice."

**On the dual-write problem (Stripe charge + DB persist)**
> "The gateway call deliberately sits outside any database transaction. If Stripe succeeds but our DB write fails, we'd catch that via the webhook — Stripe will send a subscription created event regardless. In production I'd also look at idempotency keys on the Stripe side and potentially an outbox pattern."

**On GoCardless (if asked)**
> "The structure supports it without changes to the Domain or Application layers. GoCardless would be a new Infrastructure class implementing the same PaymentGatewayInterface, with the binding resolved conditionally in the service provider based on the payment method type."
