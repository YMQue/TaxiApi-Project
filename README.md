# 🚖 Rehla (رحلة) — Taxi / Ride-Hailing API

A Laravel REST API for a ride-hailing platform. One backend serves three clients: the **Rider app**, the **Driver app**, and the **Admin dashboard**. It covers the full trip lifecycle, a wallet system with platform commission, promo codes, ratings, notifications, and financial reports.

![Laravel](https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)

---

## ✨ Features

**Rider**
- OTP login (Laravel Sanctum)
- Fare estimate for every car type, with promo-code preview
- Request, track, and cancel rides; ride history
- Pay by **wallet** or **cash**
- Rate the driver after a completed ride

**Driver**
- OTP login (Laravel Sanctum)
- Go online/offline and share live location
- Receive ride requests, accept or reject, then arrived → start → complete
- Earnings credited to the driver wallet

**Admin**
- Multi-guard authentication with role-based access (`super` admin)
- Car type management (pricing: base fare, per km, per minute, minimum fare)
- Platform settings (commission rate, cancellation fee, search radius…)
- Financial reports (revenue, rides, top drivers, top riders) with pagination
- Wallet transaction history for any driver or rider

**Platform**
- Automatic driver matching with a request timeout (queued jobs)
- Commission on every ride, credited to the **super admin wallet**
- Cancellation fees
- Promo codes
- Push notifications for ride events

---

## 🧱 Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel |
| Auth | Laravel Sanctum, multiple guards (rider / driver / admin) |
| Database | MySQL |
| Queue | Laravel Queues (driver search and timeouts) |
| Architecture | Controller → Service → Repository |
| API testing | Postman collection (`postman/` folder) |

---

## 🏗️ Architecture

```
app/
├── Events/                  RideRequest, NoDriverFound
├── Exceptions/              BusinessRuleException, PromoCodeException, RatingException
├── Http/Controllers/        thin controllers
├── Jobs/                    SearchForDriver, DriverTimeout
├── Models/
├── Repositories/
│   ├── Contracts/           repository interfaces
│   └── Eloquent/            implementations (RideRepository, SettingRepository, ...)
└── Services/                WalletService, NotificationService, PromoCodeService
```

Controllers stay thin. Business rules live in services and repositories, and repositories are bound to their interfaces through the service container.

---

## 🔄 Ride Lifecycle

```
searching → accepted → arrived → in_progress → completed
    │           │          │
    └───────────┴──────────┴──→ cancelled
```

1. The rider requests a ride and a `SearchForDriver` job looks for the nearest online driver of the requested car type.
2. The driver gets a `RideRequest` event and has `ride_request_timeout_sec` seconds to respond (`DriverTimeout` job).
3. If nobody accepts, the search continues with the remaining drivers. When none are left, a `NoDriverFound` event is fired.
4. On completion, the fare is settled between the rider, the driver, and the platform.

---

## 💰 Wallet & Commission

Every user (rider, driver, super admin) has one wallet per `user_type`. All money movements are recorded in `wallet_transactions` with a `category` and a polymorphic reference to the ride.

For a completed ride:

| Payment | Rider | Driver | Super admin |
|---|---|---|---|
| **Wallet** | − fare paid | + driver earning | + commission |
| **Cash** | pays the driver directly | − commission (may go negative) | + commission |

- `commission = estimated_fare × commission_rate`
- `driver_earning = estimated_fare − commission`
- The platform absorbs promo-code discounts, so its net on a ride is `commission − discount`.
- **Cancellation fees:** a rider cancelling after a driver accepted pays the driver. A driver cancelling pays the platform.
- Wallet operations run inside database transactions with row locks (`lockForUpdate`) so balances cannot be corrupted by concurrent requests.

Transaction categories: `topup`, `ride_payment`, `ride_earning`, `commission`, `promo_discount`, `promo_compensation`, `cancellation_fee`, `cancellation_compensation`.

---

## ⚙️ Settings

Stored in the `settings` table as key/value rows and cached.

| Key | Default | Description |
|---|---|---|
| `commission_rate` | `0.20` | Platform commission as a fraction (0–1) |
| `cancellation_fee` | `5.00` | Fee charged for late cancellations |
| `driver_search_radius_km` | `5` | Search radius for nearby drivers |
| `ride_request_timeout_sec` | `30` | Time a driver has to accept a request |
| `min_withdrawal_amount` | `50.00` | Minimum wallet withdrawal |
| `otp_expiry_minutes` | `5` | OTP lifetime |
| `otp_max_attempts` | `3` | Max OTP attempts |
| `currency` | `SAR` | Currency |

---

## 🚀 Getting Started

### Requirements
- PHP 8.2+
- Composer
- MySQL 8+
- Node.js (only if you build assets)

### Installation

```bash
git clone https://github.com/YMQue/TaxiApi-Project.git
cd TaxiApi-Project

composer install
cp .env.example .env
php artisan key:generate
```

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taxi
DB_USERNAME=root
DB_PASSWORD=

QUEUE_CONNECTION=database
CACHE_STORE=database       # do not use "array": the queue worker would not see cached data

SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=change-me-please
```

Run the migrations and seeders:

```bash
php artisan migrate --seed
```

The seeders create:
- the default **settings**
- sample **car types**
- the **super admin** account and its **wallet**

Start the app and the queue worker (the worker is required for driver matching):

```bash
php artisan serve
php artisan queue:work
```

> After changing code, settings, or `.env`, restart the worker with `php artisan queue:restart`.

---

## 📡 API Overview

All endpoints are under `/api` and return JSON. The full list of rider, driver, and admin requests is in the **Postman collection** in the [`postman/`](postman) folder.

### Admin — car types

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/car-types` | List car types |
| POST | `/api/admin/car-types` | Create a car type |
| PUT | `/api/admin/car-types/{id}` | Update prices or details |
| DELETE | `/api/admin/car-types/{id}` | Delete a car type |

### Admin — financial reports (super admin only)

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/admin/reports/revenue` | Revenue and commission for a period, with daily breakdown |
| GET | `/api/admin/reports/rides` | Ride statistics and a paginated ride list |
| GET | `/api/admin/reports/drivers` | Top drivers and their earnings |
| GET | `/api/admin/reports/riders` | Most active riders and their spending |
| GET | `/api/admin/reports/drivers/{driver}/transactions` | Wallet history of a driver |
| GET | `/api/admin/reports/riders/{rider}/transactions` | Wallet history of a rider |

Common query parameters: `from`, `to` (dates), `page`, `per_page` (max 100). The `rides` report also accepts `status`, and the transaction endpoints accept `category`.

Example response shape:

```json
{
  "from": "2026-10-01T00:00:00.000000Z",
  "to": "2026-10-31T23:59:59.000000Z",
  "message": null,
  "summary": { "rides_count": 120, "commission": 2430.5 },
  "current_page": 1,
  "data": [],
  "per_page": 15,
  "total": 30,
  "last_page": 2
}
```

An empty period returns `200` with an empty `data` array and a `message`. An unknown driver or rider ID returns `404`.

---

## 🧪 Testing

```bash
php artisan test
```

Import the collection from the `postman/` folder to try every endpoint manually.

---

## 🗺️ Roadmap

- [ ] Wallet top-up and withdrawal requests
- [ ] Real-time driver tracking
- [ ] Surge pricing
- [ ] Driver document verification workflow
- [ ] Rider/driver chat

---

## 👤 Author

**YMQue** — [github.com/YMQue](https://github.com/YMQue)
