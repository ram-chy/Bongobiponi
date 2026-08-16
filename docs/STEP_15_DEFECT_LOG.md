# STEP 15 — Defect Log

## Project

**Bongobiponi — Book Store Management System / ERP**

## Bug Policy Summary

Classification used during STEP 15 UAT:

| Severity | Meaning                                                        |
| -------- | -------------------------------------------------------------- |
| P0       | Data corruption / security breach / financial integrity issue  |
| P1       | Blocks a customer-required workflow; must be fixed             |
| P2       | Functional defect with a workaround                            |
| P3       | Cosmetic / minor                                               |

Only **confirmed** defects were fixed. Each fix includes a regression test and
is re-verified via the live API.

---

## Defect UAT-001 — `book_id` is lost on order creation

| Attribute | Value |
| --------- | ----- |
| Severity  | P1    |
| Status    | **FIXED & VERIFIED** |
| Location  | `frontend/src/features/orders/components/order-form.tsx`, `backend/app/Http/Requests/StoreOrderRequest.php`, `backend/app/Services/OrderService.php` |

### Symptom

Every order item was created with `book_id = NULL`. Because of this:

- `GET /api/orders/{id}/availability` always returned `unverifiable` instead of
  `fully_available` / `unavailable`, so availability, `To Procure` / `To Pack`
  decisions and the reserve/`To Pack` workflow never worked.
- Purchase → Order synchronization had no `book_id` to match against.
- Dashboard COGS was always `0`, so **profit was overstated to equal sales**
  (financial-integrity consequence of the same defect).

### Reproduction (confirmed before fix)

```
POST /api/orders  (item without book_id)
→ order_items.book_id = NULL  (verified in DB and in OrderItemResource response)
GET /api/orders/{id}/availability → status = "unverifiable"
Dashboard → sales_value = profit (COGS always 0)
```

### Root cause

Three independent gaps that all dropped the `book_id`:

1. `StoreOrderRequest` did not accept `items.*.book_id` in validation.
2. `OrderService::prepareItems()` never mapped `book_id` into the created item.
3. The frontend submit payload in `order-form.tsx` explicitly stripped
   `book_id` from each item before sending.

### Fix

1. `StoreOrderRequest.php` — added rule
   `'items.*.book_id' => 'nullable|exists:books,id'`.
2. `OrderService::prepareItems()` — persisted `'book_id' => $item['book_id'] ?? null`.
3. `order-form.tsx` — submit payload now includes `book_id: item.book_id || null`.
4. `UpdateOrderRequest.php` — added the same `items.*.book_id` rule.
5. `OrderItemResource.php` — exposed `book_id` in the API response.
6. Frontend type `OrderItem` / `OrderItemFormData` — added `book_id`.

### Regression tests

`tests/Feature/OrderTest.php`:

- `test_manual_order_persists_book_id_on_items`
- `test_order_requires_valid_book_id_when_provided`

### Verification after fix (live API)

```
POST /api/orders  (book_id = 1, qty = 2)
→ order_items.book_id = 1        ✓ persisted
GET /api/orders/4/availability   → status = "fully_available"   ✓
POST /api/orders/4/status to_pack → success                     ✓
Unavailable book (book_id = 3):
  availability → "unavailable", to_pack blocked ("Insufficient stock") ✓
  to_procure → success                                             ✓
Dashboard: sales_value 1200, profit 1000  → COGS now 200 (nonzero) ✓
```

---

## Defect UAT-002 — Invoice creation crashes on removed `quotation` relationship

| Attribute | Value |
| --------- | ----- |
| Severity  | P1    |
| Status    | **FIXED & VERIFIED** |
| Location  | `backend/app/Services/InvoiceService.php`, `backend/app/Http/Controllers/InvoiceController.php` |

### Symptom

`POST /api/invoices` returned HTTP 500:

```
Call to undefined relationship [quotation] on model [App\Models\DeliveryChallanItem]
```

The Quotations module was removed earlier, but several eager loads still
referenced a non-existent `quotation` relation on `DeliveryChallanItem` /
`InvoiceItem`. The entire Invoice + Payment UAT section was blocked.

### Reproduction (confirmed before fix)

```
GET /api/delivery-challans/{id}/invoiceable-items  → 500 (relation quotation)
POST /api/invoices                                 → 500
GET /api/invoices/{id}                             → 500 (relation salesOrder)
```

### Root cause

`InvoiceService` eager-loaded `items.quotation` / `quotation` (store, update,
`getInvoiceableItems`, `prepareItems`) and `InvoiceController::show` also
eager-loaded `items.salesOrder` / `items.salesOrderItem` / `items.quotation`.
None of these relations exist on `DeliveryChallanItem` / `InvoiceItem` anymore.
No existing test exercised invoice creation through the API, so the suite
remained green.

### Fix

Removed the dangling eager loads (`quotation`, `salesOrder`, `salesOrderItem`)
from `InvoiceService` and `InvoiceController`. Kept the `quotation_id` /
`quotation_item_id` column mappings and the null-safe `relationLoaded`
guards in `InvoiceItemResource` / `DeliveryChallanItemResource` (still
optional columns).

### Regression tests

New `tests/Feature/InvoiceTest.php` covering the full flow:

- `test_can_list_invoiceable_items_for_delivery_challan`
- `test_can_create_invoice_from_delivery_challan_items`
- `test_can_show_invoice_after_creation`

### Verification after fix (live API)

```
GET  /api/delivery-challans/2/invoiceable-items → 200, 1 item (qty 3) ✓
POST /api/invoices (dc item 1, qty 3 @ 150)      → 201, BBINV/001/26,
                                                  grand_total 450.00  ✓
GET  /api/invoices/1                             → 200                ✓
POST /api/payments (invoice 1, 450)              → 201                ✓
GET  /api/invoices/1/download-pdf                → 200 (PDF ~880 KB)  ✓
```

---

## Defect UAT-003 — Catalogue XLSX export fails with HTTP 500

| Attribute | Value |
| --------- | ----- |
| Severity  | P2    |
| Status    | **FIXED & VERIFIED** |
| Location  | `backend/app/Services/SpreadsheetExporterService.php` |

### Symptom

`GET /api/books/export` returned HTTP 500 on the live Windows environment.

### Reproduction (confirmed before fix)

```
GET /api/books/export → 500
Log: tempnam('C://WINDOWS', 'xlsx_') — system temporary directory is not writable
```

### Root cause

`toXlsx()` called `tempnam(sys_get_temp_dir(), 'xlsx_')`. On this machine
`sys_get_temp_dir()` resolves to `C:\WINDOWS`, which is not writable for the
web/CLI user, so `tempnam()` emitted a warning that Laravel converted into a
500. The test suite ran in a writable temporary directory, so
`CatalogueExportTest` passed.

### Fix

Added `writableTempDirectory()` which prefers `sys_get_temp_dir()` when
writable and otherwise falls back to a Laravel-writable directory
(`storage_path('app/private')`, then `storage_path('framework/cache')`).

### Regression test

`CatalogueExportTest` (existing, re-run green) plus live re-verification.

### Verification after fix (live API)

```
GET /api/books/export → 200, valid "Microsoft Excel 2007+" file (~2 KB) ✓
```

---

## Defect UAT-004 — Pre-book flag cannot be set through API or UI

| Attribute | Value |
| --------- | ----- |
| Severity  | P1    |
| Status    | **FIXED & VERIFIED** |
| Location  | `backend/app/Http/Requests/StoreOrderRequest.php`, `UpdateOrderRequest.php`, `OrderResource.php`, `frontend/src/features/orders/components/order-form.tsx`, `edit-order-page.tsx`, `types/order.ts` |

### Symptom

The customer requirement "Pre-book checkbox" was declared implemented, the
`pre_book` column existed (default `false`), but:

- The API never accepted `pre_book` (not in request validation) → it was always
  silently dropped to the DB default `false`.
- The frontend order form had **no** pre-book checkbox, and `Order` /
  `OrderFormData` types had no `pre_book` field.
- Pre-book could therefore never be switched ON by any user.

### Reproduction (confirmed before fix)

```
POST /api/orders {pre_book: true, ...} → orders.pre_book = false  (dropped)
Frontend order form: no pre-book control exists (grep found none)
```

### Fix

1. `StoreOrderRequest.php` — added `'pre_book' => 'nullable|boolean'`.
2. `UpdateOrderRequest.php` — added `'pre_book' => 'nullable|boolean'`.
3. `OrderResource.php` — exposed `pre_book` (cast to bool) in the API.
4. Frontend `order-form.tsx` — added a pre-book checkbox (default OFF,
   unchanged), submitted as `pre_book` boolean.
5. Frontend `edit-order-page.tsx` — pre-fills `pre_book` and real `book_id`
   when editing.
6. Frontend types `Order`, `OrderFormData` — added `pre_book`.

### Regression tests

`tests/Feature/OrderTest.php`:

- `test_manual_order_defaults_pre_book_to_false`
- `test_manual_order_persists_pre_book_flag`

### Verification after fix (live API)

```
POST /api/orders {pre_book: true} → orders.pre_book = true   ✓
POST /api/orders (no pre_book)     → orders.pre_book = false  ✓ (default OFF kept)
```

---

## Summary

| ID       | Severity | Module           | Root cause                                 | Fixed | Regression test |
| -------- | -------- | ---------------- | ------------------------------------------ | ----- | --------------- |
| UAT-001  | P1       | Order / Dashboard| `book_id` dropped at 3 layers              | Yes   | OrderTest       |
| UAT-002  | P1       | Invoice / Payment| Eager-load of removed `quotation` relation | Yes   | InvoiceTest     |
| UAT-003  | P2       | Catalogue Export | Unwritable system temp dir                 | Yes   | CatalogueExportTest |
| UAT-004  | P1       | Pre-book         | `pre_book` not accepted in API / no UI     | Yes   | OrderTest       |

All four defects are confirmed, fixed, regression-tested and re-verified live.
No other defects were confirmed during STEP 15 UAT.
