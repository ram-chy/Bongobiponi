# Bongobiponi — System Architecture

Book Shop Management System / ERP. A **modular monolith** composed of two decoupled applications:

- **Backend** — Laravel 13 REST API (JWT-secured, MySQL)
- **Frontend** — Next.js 16 (App Router) SPA consuming the API

> Design intent (from `prompt/BLUEPRINT.md`): Clean Architecture, SOLID, thin controllers, service-layer business logic, repository pattern, inventory as the source of truth, and every stock movement flowing through the Inventory Transaction system.

---

## 1. High-Level Overview

```
┌────────────────────────────┐        HTTP / JSON (JWT Bearer)        ┌────────────────────────────┐
│        FRONTEND             │ ─────────────────────────────────────▶ │         BACKEND            │
│  Next.js 16 / React 19      │                                        │  Laravel 13 / PHP 8.3      │
│  App Router SPA             │ ◀───────────────────────────────────── │  REST API at /api          │
│  Tailwind v4 + shadcn/ui    │                                        │  tymon/jwt-auth            │
└────────────────────────────┘                                        └─────────────┬──────────────┘
                                                                                     │
                                                                                     ▼
                                                                          ┌────────────────────────┐
                                                                          │        MySQL            │
                                                                          │  (~40 migration tables)  │
                                                                          └────────────────────────┘
```

- Frontend dev server: `http://localhost:3000`
- Backend API: `http://localhost:8000/api` (`NEXT_PUBLIC_API_URL`)
- Both apps share the same git repo; backend tests are green (104/104).

---

## 2. Repository Layout

```
Bongobiponi/
├── backend/                  # Laravel 13 API
│   ├── app/
│   │   ├── Enums/            # InventoryTransactionType, InvoiceStatus
│   │   ├── Helpers/          # JWTHelper
│   │   ├── Http/
│   │   │   ├── Controllers/  # Thin resource controllers + Traits/ApiResponse
│   │   │   ├── Middleware/   # token.version, role.admin, role.manager, throttle
│   │   │   ├── Requests/     # Form Request validation (Store*/Update*)
│   │   │   └── Resources/    # API Resource / Collection transformation layer
│   │   ├── Mail/             # SendOtpMail
│   │   ├── Models/           # Eloquent models (+ Scopes/CreatedByScope, Traits)
│   │   ├── Policies/         # Per-module authorization (CrudPolicyTrait)
│   │   ├── Providers/        # App, Auth, JWT, RouteServiceProvider…
│   │   ├── Repositories/     # Data-access layer (InventoryRepository, UserRepository)
│   │   └── Services/         # Business logic (one per module + engines)
│   ├── config/               # incl. jwt.php, inventory.php (allow_negative_stock)
│   ├── database/
│   │   ├── migrations/       # ~40 versioned migrations
│   │   ├── factories/        # UserFactory, BookFactory, …
│   │   └── seeders/          # Roles, master data, inventory test data
│   ├── routes/api.php        # All API routes (auth, throttle, role groups)
│   ├── postman/              # Postman collections
│   ├── tests/                # Feature + Unit (PHPUnit)
│   └── composer.json
├── frontend/                 # Next.js 16 SPA
│   ├── src/
│   │   ├── app/              # App Router pages ((auth), (dashboard))
│   │   ├── components/       # ui, common (EntityPage, guards), layout, tables, …
│   │   ├── features/         # Feature-based modules (auth, orders, purchases, …)
│   │   ├── hooks/            # useEntityList, useEntityDelete, usePermissions, …
│   │   ├── lib/              # axios client, api-errors, permissions, utils
│   │   ├── providers/        # QueryProvider, ThemeProvider
│   │   ├── services/         # One API service module per backend resource
│   │   ├── store/            # Zustand (auth-store, sidebar-store)
│   │   ├── types/            # TypeScript domain types
│   │   └── constants/        # Navigation config
│   ├── public/
│   └── package.json
├── prompt/                   # Design docs, phase plans, agent instructions
└── WORK_LOG.md               # Session log / change history
```

---

## 3. Backend Architecture

### 3.1 Layered Request Flow

Every module follows the same vertical slice:

```
HTTP Request
   │
   ▼
routes/api.php  ── middleware chain: auth:api → token.version → throttle:api → role.*
   │
   ▼
Controller  ────────────── thin, only HTTP concerns; uses Traits/ApiResponse
   │
   ▼
Form Request (Store*/Update*)  ── validation rules + authorize()
   │
   ▼
Service  ──────────────── ALL business logic lives here
   │          │
   │          ├─ Repositories ── DB access only (optional, e.g. Inventory)
   │          ├─ Eloquent Model ── relations, casts, scopes
   │          └─ Engines (SafeDelete, SerialGenerator, StatusTransition, PDF)
   │
   ▼
API Resource / Collection  ── response shape, transforms dates/money/relations
   │
   ▼
JSON response (consistent envelope via Traits/ApiResponse)
```

### 3.2 Layer Responsibilities

| Layer | Location | Responsibility |
|---|---|---|
| Routes | `routes/api.php` | Public (register/login/forgot-password), protected (auth), role-gated groups (`role.admin`, `role.manager`) |
| Controllers | `app/Http/Controllers` | Handle HTTP semantics, delegate to Services, format responses via `Traits/ApiResponse` |
| Requests | `app/Http/Requests` | Validation + authorization (422 errors are structured for the frontend form mapper) |
| Services | `app/Services` | Orchestration, transactions, status transitions, serial generation, inventory effects |
| Repositories | `app/Repositories` | Database persistence helpers (users, inventory) |
| Models | `app/Models` | Eloquent ORM; `$fillable`, casts, relations, global scope `CreatedByScope` + `FilterByCreatedBy` trait for row-level scoping |
| Policies | `app/Policies` | Per-entity authorization; `CrudPolicyTrait` provides standard CRUD checks, custom `viewAny` overrides |
| Resources | `app/Http/Resources` | Response shaping (Collection + single Resource per module) |
| Enums | `app/Enums` | `InventoryTransactionType`, `InvoiceStatus` |

### 3.3 Key Cross-Cutting Services (Engines)

Reusable infrastructure shared by modules:

- **`SafeDeleteEngine`** — reusable delete protection for master data. Reflectively discovers blocking relationships (HasOne/HasMany/BelongsToMany/MorphMany) and blocks deletion if any reference exists; also enforces "latest record only" deletion. Returns a structured `SafeDeleteResult` (blocked reason + reference module/count/samples) that the frontend renders as a confirm dialog.
- **`SerialGeneratorService`** + per-module serial generators (`OrderSerialGeneratorService`, `InvoiceSerialGeneratorService`, `DeliveryChallanSerialGeneratorService`, `PaymentSerialGeneratorService`, `PurchaseSerialGeneratorService`, `ExpenseSerialGeneratorService`, `InventorySerialGeneratorService`) — document number generation.
- **`PdfEngineService`** + `OrderPDFService`, `DeliveryChallanPDFService`, `InvoicePDFService` — dompdf-based document downloads (`.../download-pdf` routes).
- **`InventoryService`** — the single owner of stock. Every stock mutation (purchase confirm, order delivery, damage, adjustment, opening) creates an `InventoryTransaction` row. Ledger built from transactions, never from stored totals. Config `inventory.allow_negative_stock` (default false).
- **`ActivityLogService`** — audit trail backed by `activity_logs` table.
- **`DocumentReferenceService`** / **`SourceDocumentOwnershipService`** — cross-document lineage (order → delivery challan → invoice → payment).
- **`StatusTransitionService`** classes (`DeliveryChallanStatusTransitionService`, `InvoiceStatusTransitionService`) — enforce legal document status changes.

### 3.4 Auth & Authorization

- **JWT** via `tymon/jwt-auth` (`config/jwt.php`), guards `auth:api`.
- **`token.version` middleware** — invalidates tokens when a user's `token_version` increments (e.g. password change / logout-all). Tokens cached with `token_version` in the claim.
- **`JWTHelper`** — claim generation/read helpers.
- **Roles** — `roles` table: `admin`, `manager`, `regular_user`. Route groups `role.admin`, `role.manager`. `role_id` is intentionally **not** `$fillable` — role changes go through `forceFill` in `UserRepository::updateRole()`.
- **Policies** — per-module authorization gates; `CustomerPolicy` overrides `viewAny → true` and `CustomerService` scopes listing by `created_by` for non-admin/manager users (data isolation).
- **Password reset** — OTP flow: `password_reset_otps` stores a `sha256` hash of the OTP (needs 64-char column), with `failed_attempts` and expiry; emails sent via `SendOtpMail`.

### 3.5 API Surface (routes/api.php)

Public (throttled):
- `POST /api/register`, `POST /api/login` (throttle 5/min)
- `POST /api/forgot-password`, `POST /api/verify-otp`, `POST /api/reset-password` (throttle 10/min)

Protected (auth:api + token.version + throttle:api):
- `GET /api/me`, `POST /api/logout`, `POST /api/refresh`
- `GET /api/users`, `GET /api/users/{id}`, `PUT /api/users/{user}/role` (admin only)
- CRUD resource groups with `POST /{resource}/{id}/restore` (soft delete): customers, suppliers, publishers, authors, categories, books, purchases, orders, delivery-challans, invoices, payments, expense-categories, expenses
- Extra endpoints: `POST /api/books/upload-cover`, `POST /api/purchases/{purchase}/confirm|cancel`, inventory endpoints (`GET /api/inventory`, `GET /api/inventory/ledger/{bookId}`, `GET /api/inventory/{bookId}`, and manager-only `POST opening/adjustment/damage`), document download-PDF routes, `GET /api/orders/{order}/remaining-items`, `GET /api/delivery-challans/{dc}/invoiceable-items`, `GET /api/customers/{customerId}/due-invoices`, `GET /api/expenses/{expense}/download-attachment`

### 3.6 Database

~40 versioned migrations. Notable domains:

- **Identity/Auth**: users, roles, password_reset_otps, personal_access_tokens (dropped legacy token tables), jobs/cache
- **Master data**: customers, suppliers, publishers, authors, categories, books (+ book cover upload)
- **Procurement**: receive_orders, purchases + purchase_items (+ publisher_id), purchases → confirm/cancel transitions
- **Inventory**: inventory_transactions (ledger), stock
- **Sales**: orders → order_items → order_item_conversions → delivery_challans → dc_items → invoices → invoice_items → payments → payment_items
- **Financial**: expenses, expense_categories (attachment downloads)
- **Cross-cutting**: document_references, activity_logs

Schema conventions (from BLUEPRINT): plural snake_case tables, soft deletes on master data, additive migrations only, cross-document cascades fixed by dedicated migration batches, performance indexes on core/master tables.

### 3.7 Testing

- PHPUnit (`composer test` / `php vendor/bin/phpunit` from `backend/`)
- Feature tests per module: CustomerTest, OrderTest, ExpenseTest, PaymentTest, RoleTest, PaginationTest, PasswordResetTokenExpiryTest, ExpenseAttachmentSecurityTest
- Factories + `InventoryTestSeeder` (100+ records per table) drive the suite; 104 tests / 247 assertions green.

---

## 4. Frontend Architecture

### 4.1 Stack

Next.js 16 (App Router) · React 19 · TypeScript (strict) · Tailwind CSS v4 · shadcn/ui (base-ui primitives) · React Hook Form + Zod · TanStack Query + TanStack Table · Zustand · Axios · Sonner · next-themes · Lucide icons.

### 4.2 Feature-Based Structure

Each business module is self-contained under `src/features/<module>/`:

```
src/features/customers/
├── components/    # module-specific UI (forms, dialogs, cells)
├── config/        # EntityConfig (columns, routes, filters) if used
└── hooks/         # module-specific data hooks
```

Shared infrastructure lives outside features:

- **`components/common/entity-page.tsx`** — a generic, config-driven CRUD list page (`EntityConfig<T>` in `types/entity.ts`). Modules only supply columns, endpoints, routes, and filters; the component renders table, search, sort, pagination, filters, and actions.
- **`components/common/`** — `auth-guard`, `guest-guard`, `role-guard`, `row-actions`.
- **`components/tables/data-table.tsx`** — TanStack Table wrapper (sorting, hideable columns, pagination).
- **`components/layout/`** — DashboardLayout, Navbar, Sidebar (collapsible accordion driven by `sidebar-store`), breadcrumb, command-palette, loaders, dialogs.
- **`hooks/`** — `use-entity-list`, `use-entity-delete`, `use-customer-search`, `use-permissions`.
- **`services/`** — one typed API service per backend resource (author-service, book-service, order-service, …). All share the single axios instance.

### 4.3 Routing / Pages

- `(auth)` route group: `/login`, `/register`, `/forgot-password`.
- `(dashboard)` route group with `layout.tsx` applying `AuthGuard` + dashboard chrome. Per-resource route trees: `/<module>`, `/<module>/create`, `/<module>/[id]`, `/<module>/[id]/edit`. Inventory has sub-views: `stock`, `ledger`, `opening`, `adjustment`, `damage`.
- Global `error.tsx`, `not-found.tsx`, `forbidden.tsx`.

### 4.4 State & Data Flow

- **Server state** → TanStack Query (`QueryProvider`); hooks like `useEntityList` encapsulate query + pagination + filters.
- **Client state** → Zustand: `auth-store` (token, user, role, login/logout), `sidebar-store` (collapse state).
- **HTTP** → single axios instance in `lib/axios.ts`:
  - Base URL from `NEXT_PUBLIC_API_URL`; `withCredentials`.
  - Request interceptor attaches `Authorization: Bearer <token>` from in-memory store, hydrating from the non-HttpOnly `auth_token` cookie after page refresh. Strips `Content-Type` for FormData (file uploads).
  - Response interceptor: any `401` → logout + redirect to `/login`.
- **Forms** → React Hook Form + Zod schema; backend Laravel 422 validation errors mapped to fields via `lib/api-errors.ts`.
- **Permissions** → `usePermissions` / `RoleGuard` gate UI by role (`admin`, `manager`, `regular_user`).

### 4.5 Auth Token Flow

```
login → backend returns JWT + sets auth_token cookie
      → auth-store holds token in memory
request → interceptor injects Bearer token (memory → cookie fallback)
401     → interceptor logs out + navigates to /login
```

---

## 5. Module Inventory

| Module | Backend | Frontend | Notes |
|---|---|---|---|
| Auth | AuthController, PasswordResetController | features/auth | JWT login/register, OTP password reset |
| User Management | UserController, UserService, UserRepository | features/user-management | Admin role assignment |
| Customers | CustomerController/Service/Policy | features/customers | Reference implementation; `created_by` scoping |
| Suppliers / Publishers / Authors / Categories | Resource CRUD + restore | features/suppliers, publishers, authors, categories | Master data; SafeDeleteEngine |
| Books | BookController, cover upload | features/books | GD-compressed cover images (≤500KB) |
| Receive Orders | — | — | Domain planned; purchases cover receiving |
| Purchases | PurchaseController/Service, confirm/cancel | features/purchases | Only confirmed purchases touch inventory |
| Inventory | InventoryController/Service | features/inventory (stock/ledger/opening/adjustment/damage) | Single source of truth; transaction ledger |
| Orders (Sales) | OrderController/Service, PDF | features/orders | Feeds delivery challans |
| Delivery Challans | DeliveryChallanController/Service, remaining-items, PDF | features/delivery-challans | Status transitions; order → DC conversion |
| Invoices | InvoiceController/Service, invoiceable-items, PDF | features/invoices | DC → invoice conversion; payment tracking |
| Payments | PaymentController/Service, due-invoices, PDF | features/payments | Payment items, paid amount/status on invoices |
| Expenses | ExpenseController/Service, attachment download | features/expenses | ExpenseCategory master data |

---

## 6. Cross-Document Sales Chain

```
Orders ─▶ OrderItems ─▶ Delivery Challans ─▶ Invoices ─▶ Payments
   │          │               │                  │
   └── OrderItemConversions ──┘   InvoiceItems    └── PaymentItems
```

- Conversions/ownership tracked via `order_item_conversions`, `sales_order_item_conversions`, `document_references`, and `SourceDocumentOwnershipService`.
- Deleting an upstream document cascades correctly to downstream records (dedicated cascade-fix migrations).
- Inventory is only debited at confirmation/delivery; document statuses (draft → confirmed) gate stock effects.

---

## 7. Environment & Config

- `backend/.env` — DB, `JWT_SECRET`, mail (smtp), `MAIL_MAILER`, file storage.
- `backend/config/inventory.php` — `allow_negative_stock` (false).
- `frontend/.env.local` — `NEXT_PUBLIC_API_URL=http://localhost:8000/api`.
- `frontend/next.config.ts` — remote image patterns (incl. `http` protocol for dev cover uploads).
- `php.ini` — `upload_tmp_dir` set to `backend/storage/php-upload-tmp/` (book cover uploads).

## 8. Development Workflow & Conventions

- Feature branches off `develop`; merge only after review + tests.
- API contracts are stable; additive migrations preferred; destructive changes need approval.
- Backend changes → run `php vendor/bin/phpunit` from `backend/`; frontend → `npm run lint` / `npm run build`.
- Full change history maintained in `WORK_LOG.md`; module specs in `prompt/`.
