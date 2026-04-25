# Food ordering API

A complete, production-ready REST API built with **Laravel 13.6.0** for managing a restaurant (pizzeria) ordering system. The project stands out with its solid architecture, strict typing, implementation of design patterns (Services, Form Requests, Policies, API Resources), and near 100% test coverage using Pest PHP.

---

## Key Features

- **Guest-Only Ordering System:** There is no "Customer" role in the system. Clients order food as guests without creating an account or logging in.
- **Staff-Exclusive Accounts:** The `users` table is strictly reserved for employees. The system implements three distinct roles via the `UserRole` Enum: `ADMIN`, `CHEF`, and `DELIVERY`. All protected API routes are secured via Laravel Sanctum.
- **Order State Machine:** Orders follow a strict state transition path (`OrderStatus` Enum): `PENDING` -> `PREPARING` -> `READY` -> `DELIVERING` -> `DELIVERED` (or `CANCELLED`).
- **Strict Authorization Policies:**
    - **Chef:** Can only transition orders from `Preparing` to `Ready`. Fetches only relevant, recent (last 24h) orders from the API.
    - **Delivery:** Can transition orders through delivery stages (`Ready` -> `Delivering` -> `Delivered`).
    - **Admin:** Has full control and access to all endpoints with pagination.
- **Data Protection (API Resources):** The system dynamically hides sensitive data based on the authenticated user's role.
- **Secure Payments & Webhooks:** Payments are processed via Stripe. An order's status changes to `PREPARING` and triggers the `OrderPaid` event (which queues an email confirmation) **only** via a secure Stripe Webhook that strictly verifies the cryptographic `Stripe-Signature`.

---

## Technology Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 13.6.0 (PHP 8.2+) |
| Testing | Pest PHP |
| Database | SQLite / MySQL / PostgreSQL (configurable) |
| Queue Driver | Database (Laravel Queues) |
| Payments | Stripe API |

---

## Setup & Installation

Follow these steps to run the API locally using Laravel Sail.

### 1. Clone the repository

```bash
git clone <repository-url>
cd <repository-name>
```

### 2. Install PHP dependencies

If you don't have PHP installed locally, use Docker to install the dependencies:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    composer install --ignore-platform-reqs
```

### 3. Configure the environment file

```bash
cp .env.example .env
```

Open `.env` and make sure the following values are set correctly:

```dotenv
APP_URL=http://localhost
DB_CONNECTION=sqlite

QUEUE_CONNECTION=database

STRIPE_KEY=your_stripe_public_key
STRIPE_SECRET=your_stripe_secret_key
STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
```

> **Important:** The `QUEUE_CONNECTION` must be set to `database`. The payment confirmation system dispatches emails to a queue. If the queue worker is not running, clients will not receive order confirmations.

### 4. Start the Sail environment

```bash
./vendor/bin/sail up -d
```

### 5. Generate the application key and run migrations

```bash
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
```

**What the seeder creates:**
The seeder generates default staff accounts and automatically creates **Sanctum Bearer Tokens** for each role, printing them directly to your terminal:
- **Admin:** `admin@test.com` (Token printed in console)
- **Chef:** `chef@test.com` (Token printed in console)
- **Delivery:** `delivery@test.com` (Token printed in console)

### 6. Configure HTTP Client (Testing API)
If you are using an IDE with a built-in HTTP client (like PhpStorm or VS Code REST Client), copy the tokens printed by the seeder and paste them into your `http-client.env.json` file:

```json
{
    "local": {
        "baseUrl": "http://localhost/api",
        "adminToken": "1|your_admin_token_here",
        "chefToken": "2|your_chef_token_here",
        "deliveryToken": "3|your_delivery_token_here"
    }
}
```

### 7. Start the queue worker

You must start the queue worker in a separate terminal to process asynchronous tasks like sending order confirmation emails:

```bash
./vendor/bin/sail artisan queue:work
```

---

## Testing Stripe Webhooks Locally

To test the payment flow locally without deploying to a live server, you need the [Stripe CLI](https://stripe.com/docs/stripe-cli).

1. Log in to your Stripe account:
```bash
stripe login
```

2. Forward Stripe events to your local API webhook endpoint:
```bash
stripe listen --forward-to localhost/api/webhook/stripe
```

3. Copy the webhook signing secret provided in the terminal output and paste it as `STRIPE_WEBHOOK_SECRET` in your `.env` file.

---

## API Data Protection (Example)

The API heavily utilizes Laravel API Resources (`$this->when()`) to prevent data leaks. Below is an example of how the exact same endpoint (`GET /api/orders`) responds differently based on the authenticated user's role.

### Admin Response (Full Data)
```json
{
  "id": 105,
  "status": "ready",
  "total_price": "45.50",
  "items": [...],
  "client_address": "123 Main St, NY",
  "client_phone": "+1234567890",
  "client_email": "guest@example.com",
  "created_at": "12 October 2024, 14:30",
  "updated_at": "12 October 2024, 14:45"
}
```

### Chef Response (Restricted Data)
```json
{
  "id": 105,
  "status": "ready",
  "total_price": "45.50",
  "items": [...]
  // Address, phone, email, and timestamps are completely stripped from the payload.
}
```

---

## Testing

The test suite uses Pest PHP and provides near 100% coverage, addressing complex business logic and edge cases:

- **Security & Authorization:** Verifying role-based access control (HTTP 401 & 403) and protecting state machine transitions via Policies.
- **Advanced API Resource Testing:** Using `AssertableJson` to ensure sensitive data does not leak to unauthorized staff members.
- **Time Traveling:** Utilizing Laravel's time manipulation to ensure queries correctly filter orders from the last 24 hours.
- **Webhook Mocking:** Cryptographically signing test payloads (`hash_hmac`) to securely mock and verify Stripe webhook integration without hitting external APIs.

Run the full test suite:

```bash
./vendor/bin/sail pest
```

---

## Quick-Start Checklist

| Step | Command / Action |
|---|---|
| Install dependencies | `composer install` (via Docker if needed) |
| Copy env file | `cp .env.example .env` |
| Configure Queues | Set `QUEUE_CONNECTION=database` |
| Start Sail | `./vendor/bin/sail up -d` |
| Run migrations & seed | `./vendor/bin/sail artisan migrate --seed` |
| Configure HTTP Client | Add generated tokens to `http-client.env.json` |
| **Start Queue Worker** | `./vendor/bin/sail artisan queue:work` ⚠️ |
| Forward Webhooks | `stripe listen --forward-to localhost/api/webhook/stripe` |
| Run tests | `./vendor/bin/sail pest` |
