# PHASE_05_BACKEND_INVENTORY_ENGINE.md

# Phase 5 - Inventory Engine (Backend)

Version: 1.0

Framework: Laravel API

Architecture: Repository + Service

Status: Backend Only

---

# Read Before Starting

Read these documents completely before making any changes.

- BACKEND_BLUEPRINT.md
- PROJECT_RULES.md

Analyze the existing architecture.

Understand how Purchase and Receive Order currently work.

Do not assume anything.

---

# Existing Modules

Completed

- Authentication
- User Management
- Supplier
- Publisher
- Author
- Category
- Book
- Receive Order
- Purchase

Do not redesign existing modules.

Reuse existing architecture.

---

# Objective

Implement the Inventory Engine.

Inventory becomes the only module responsible for stock movement.

No other module may directly update stock.

Purchase, Sales, Returns, Damage, Adjustment and future modules must use
Inventory Service.

Inventory is the source of truth.

---

# Inventory Philosophy

Wrong

Purchase

↓

Update Book Stock

Correct

Purchase

↓

Inventory Service

↓

Inventory Transaction

↓

Stock Updated

---

# Responsibilities

Inventory Engine must

- Record every stock movement
- Calculate current stock
- Maintain stock consistency
- Support future modules
- Provide inventory history
- Prevent negative stock (configurable)

---

# Database

Create

inventory_transactions

Fields

- id
- transaction_no
- transaction_type
- reference_type
- reference_id
- book_id
- quantity_in
- quantity_out
- balance_after
- transaction_date
- remarks
- created_by
- updated_by
- timestamps

---

Create

stocks

Fields

- id
- book_id
- current_quantity
- last_transaction_id
- timestamps

---

# Relationships

Book

1

↓

Many

Inventory Transactions

Book

1

↓

One

Stock

---

# Transaction Types

Create Enum

InventoryTransactionType

Values

OPENING

PURCHASE

PURCHASE_RETURN

SALE

SALE_RETURN

ADJUSTMENT

DAMAGE

TRANSFER_IN

TRANSFER_OUT

The enum should be reusable across the application.

---

# Reference Types

Inventory transactions must support

Purchase

Sale

Purchase Return

Sale Return

Adjustment

Damage

Future Transfer

Store

reference_type

reference_id

Use Laravel Morph Map or an equivalent strategy.

Do not hardcode module names.

---

# Transaction Number

Generate automatically.

Example

INV-2026-000001

Unique

Sequential

Read-only

---

# Inventory Service

Create a dedicated

InventoryService

Responsibilities

increaseStock()

decreaseStock()

adjustStock()

reverseTransaction()

getCurrentStock()

recalculateStock()

validateStock()

No controller or repository should update stock directly.

---

# Business Rules

Only Inventory Service updates stocks.

Every stock movement must create an inventory transaction.

Current stock must always equal

SUM(quantity_in)

-

SUM(quantity_out)

The stocks table is a cached balance.

inventory_transactions remain the source of truth.

---

# Negative Stock

Support configuration.

Allow

true

false

If disabled

Prevent transactions that create negative stock.

Throw meaningful exceptions.

---

# Purchase Integration

Refactor Purchase.

When Purchase becomes Confirmed

Call

InventoryService::increaseStock()

Do not update stock elsewhere.

---

# Future Sales Integration

Do not implement Sales.

Only prepare InventoryService.

Sales will later call

InventoryService::decreaseStock()

---

# Adjustment Support

Support

Increase

Decrease

Adjustments should generate inventory transactions.

---

# Damage Support

Support

Damaged quantity

Inventory decreases.

---

# Opening Stock

Support opening stock.

Generate inventory transaction.

---

# Stock Recalculation

Implement

recalculateStock(bookId)

It should

Read every inventory transaction

Recalculate

Update stocks table

Useful for future maintenance.

---

# Stock Lookup

Implement

getCurrentStock(bookId)

Always return

Current Quantity

Last Updated

Book

---

# Backend Requirements

Implement

Migration

Models

Relationships

Enum

Repository Interface

Repository

Inventory Service

Controller

API Resource

Form Requests

Routes

Validation

Exception Handling

---

# API Endpoints

Examples

GET

/api/inventory

GET

/api/inventory/{book}

GET

/api/inventory/ledger/{book}

POST

/api/inventory/opening

POST

/api/inventory/adjustment

POST

/api/inventory/damage

No endpoint should modify stock directly except through InventoryService.

---

# Search

Book

Transaction Number

Reference Number

Reference Type

Transaction Type

---

# Filters

Date Range

Book

Transaction Type

Reference Type

---

# Pagination

Server-side pagination only.

---

# Performance

Use eager loading.

Avoid N+1 queries.

Index

book_id

reference_id

reference_type

transaction_date

transaction_no

---

# Error Handling

Return proper HTTP status codes.

Use custom exceptions.

Never expose stack traces.

---

# Logging

Log

Failed stock updates

Failed recalculations

Unexpected inventory exceptions

---

# Testing

Verify

Purchase creates inventory transaction

Current stock updates

Negative stock prevention

Recalculation works

Transaction rollback works

---

# Opencode Workflow

Before implementation

Explain

1. Database design
2. Relationships
3. Service design
4. Repository design
5. Files to create
6. Files to modify
7. Purchase integration
8. Risks

Wait for approval.

Implement step by step.

After every step

Summarize

Created files

Modified files

Business rules implemented

Remaining tasks

Then stop.

---

# Restrictions

Do NOT

Modify Authentication

Modify Supplier

Modify Book

Rewrite Purchase

Rewrite Receive Order

Modify unrelated modules

Duplicate stock logic

Update stock outside InventoryService

Run migrations automatically

Run seeders

Drop tables

Rename existing tables

---

# Definition of Done

Inventory Engine is complete only if

✓ Inventory Transactions implemented

✓ Stocks table implemented

✓ Inventory Service implemented

✓ Purchase integration completed

✓ Opening Stock supported

✓ Stock Adjustment supported

✓ Damage supported

✓ Stock lookup implemented

✓ Stock recalculation implemented

✓ Negative stock validation implemented

✓ Search implemented

✓ Filtering implemented

✓ Pagination implemented

✓ Exception handling completed

✓ Logging completed

✓ No breaking changes

Stop after completing Phase 5.

Do NOT implement Sales Integration until approval.
