# Korvexa POS - Backend API & Sync Engine

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Sanctum](https://img.shields.io/badge/Auth-Laravel%20Sanctum-red?style=for-the-badge)
![License](https://img.shields.io/badge/License-Proprietary-blue?style=for-the-badge)

**Korvexa POS Backend** is the centralized Cloud API, Tenant Management, License Verification, and Sync Engine server supporting the Korvexa POS Desktop application ecosystem. Built on Laravel 11, it provides real-time multi-tenant database isolation, offline-first bidirection delta synchronization, customer credit (Khata) ledgers, register shift management, and administrative control.

---

## 🚀 Key Features

- **Multi-Tenant Isolation**: Enforces tenant scoping (`TenantScope` middleware) across all database models and API endpoints.
- **Offline-First Sync Engine**: Bi-directional delta synchronization (`/api/sync/push` & `/api/sync/pull`) tracking entity timestamps and version tags for conflict resolution.
- **License Management**: Automated license key validation, hardware fingerprint binding, device limits, and expiry check middleware (`CheckLicense`).
- **Khata / Store Credit Ledger**: Customer debt tracking, partial repayments, and automated credit statements.
- **Cash Register & Shift Management**: Real-time shift tracking, cash movement logging (cash in / cash out), drawer balancing, and closing reports.
- **Role-Based Access & Staff Control**: Fine-grained cashier, manager, and administrator permissions using Laravel Sanctum authentication tokens.
- **SuperAdmin Portal**: Built-in Laravel Blade web admin portal (`/admin`) for managing tenants, viewing sync logs, and issuing license keys.

---

## 🛠️ Tech Stack & Dependencies

- **Framework**: Laravel 11.x
- **Language**: PHP 8.2+
- **Database Support**: SQLite / MySQL / PostgreSQL / MariaDB
- **Authentication**: Laravel Sanctum (Bearer Tokens)
- **Architecture**: Service Repository Pattern & Middleware Pipeline

---

## 📂 System Architecture & Directory Structure

```
backend/
├── app/
│   ├── Console/Commands/      # CLI Commands (SuperAdmin & License Generator)
│   ├── Http/
│   │   ├── Controllers/       # API & Admin Blade Controllers
│   │   └── Middleware/        # TenantScope, CheckLicense, SuperAdminMiddleware
│   ├── Models/                # Eloquent Models with BelongsToTenant trait
│   ├── Providers/             # App & Service Providers
│   └── Services/              # Core Services (SyncService, LicenseService)
├── config/                    # System & Package Configuration
├── database/
│   ├── migrations/            # Database Schema Migrations
│   └── seeders/               # System Seeders & Initial Data
├── resources/
│   └── views/admin/           # SuperAdmin Blade Dashboard Views
├── routes/
│   ├── api.php                # RESTful API & Sync Routes
│   └── web.php                # SuperAdmin Web Routes
└── tests/                     # Feature & Unit Test Suites
```

---

## 🔑 Custom CLI Commands

### 1. Create Super Admin User
Generates a master administrator account for accessing the `/admin` dashboard portal.
```bash
php artisan make:super-admin
```

### 2. Generate Tenant License Key
Issues a new hardware-bindable license key for a client deployment.
```bash
php artisan license:generate {tenant_id} --days=365 --devices=5
```

---

## 🌐 API Route Summary

### Public & License Endpoints
| Method | Endpoint | Description |
| :--- | :--- | :--- |
| `GET` | `/api/ping` | Health check endpoint |
| `POST` | `/api/license/activate` | Activate license with hardware key |
| `POST` | `/api/license/validate` | Validate key & check device count |
| `POST` | `/api/auth/login` | Authenticate cashier / manager |

### Protected Core API (`auth:sanctum`, `tenant.scope`, `license.check`)
| Category | Endpoints |
| :--- | :--- |
| **Products** | `GET/POST /api/products`, `PUT/DELETE /api/products/{id}` |
| **Orders** | `GET/POST /api/orders`, `POST /api/orders/{id}/refund` |
| **Khata** | `GET /api/khata/ledger`, `POST /api/khata/collect-repayment` |
| **Shifts** | `GET /api/shift/active`, `POST /api/shift/start`, `POST /api/shift/close` |
| **Sync Engine**| `POST /api/sync/push`, `GET /api/sync/pull`, `GET /api/sync/logs` |

---

## ⚙️ Installation & Local Setup

### Prerequisites
- PHP `>= 8.2` with OpenSSL, PDO, Mbstring, and Tokenizer extensions
- Composer 2.x
- SQLite or MySQL server

### Steps
1. **Clone & Navigate**
   ```bash
   git clone https://github.com/muhumair2025/korvexa-pos-backend.git
   cd korvexa-pos-backend
   ```

2. **Install Dependencies**
   ```bash
   composer install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run Migrations & Seed Database**
   ```bash
   php artisan migrate --seed
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```
   The backend API will be running at `http://127.0.0.1:8000`.

---

## 🧪 Testing

Run the automated PHPUnit feature and unit test suites:
```bash
php artisan test
```

---

## 📄 License

This backend repository is proprietary software powering the Korvexa POS platform. All rights reserved.
