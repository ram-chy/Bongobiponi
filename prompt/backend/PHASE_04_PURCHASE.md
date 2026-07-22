# PHASE_04_PURCHASE.md

# Phase 4 - Purchase Management

Version: 1.0

Module: Purchase

Architecture: Laravel API + Next.js

Status: Backend + Frontend

---

# Read Before Starting

Read completely before making changes.

- BACKEND_BLUEPRINT.md
- FRONTEND_BLUEPRINT.md
- PROJECT_RULES.md

Analyze the existing architecture first.

Do not modify unrelated modules.

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

Reuse existing code whenever possible.

---

# Objective

Implement the Purchase module.

A Purchase confirms that books have been physically received from a supplier.

Purchase DOES NOT directly modify stock.

Inventory transactions will be implemented in Phase 5.

For now, Purchase only records the transaction and updates Receive Order
progress.

---

# Business Workflow

Supplier

↓

Receive Order

↓

Purchase

↓

Inventory Engine (Phase 5)

↓

Sales

---

# Business Rules

Purchase may be created

- From a Receive Order
- Directly (Manual Purchase)

Manual Purchase should be configurable.

Default: Allow Manual Purchase.

---

# Purchase Status

Draft

Confirmed

Cancelled

Only Confirmed purchases can later generate inventory transactions.

Cancelled purchases cannot be edited.

---

# Purchase Types

Receive Order Purchase

Manual Purchase

Store purchase_type.

---

# Purchase Number

Generate automatically.

Example

PO-2026-000001

Sequential

Unique

Read-only

---

# Database

## purchases

Fields

- id
- purchase_no
- purchase_type
- receive_order_id (nullable)
- supplier_id
- invoice_no
- invoice_date
- purchase_date
- notes
- status
- created_by
- updated_by
- timestamps
- soft deletes

---

## purchase_items

Fields

- id
- purchase_id
- book_id
- ordered_quantity
- received_quantity
- purchase_price
- total
- remarks
- timestamps

---

# Relationships

Supplier

1

↓

Many

Purchases

Purchase

1

↓

Many

Purchase Items

Book

1

↓

Many

Purchase Items

Receive Order

1

↓

Many

Purchases

---

# Receive Order Integration

When Purchase originates from Receive Order

Load

Supplier

Items

Ordered Quantity

Purchase Price

Automatically.

Users may adjust

Received Quantity

Purchase Price

Remarks

---

# Receive Order Progress

Update Receive Order progress.

Example

Ordered

100

Purchased

40

Remaining

60

Status

Partially Received

After

100

↓

100

Status

Completed

Do NOT update inventory.

---

# CRUD

Implement

List

Details

Create

Update

View

Soft Delete

Restore (optional)

---

# List Page

Server-side Pagination

Search

Sorting

Filtering

Status Badge

Supplier

Purchase Type

Purchase Number

Invoice Number

Purchase Date

Action Menu

---

# Filters

Supplier

Purchase Type

Status

Date Range

Invoice Number

Purchase Number

---

# Search

Purchase Number

Invoice Number

Supplier

Reference Number

---

# Form

Header

Purchase Type

Supplier

Receive Order

Invoice Number

Invoice Date

Purchase Date

Status

Notes

Items

Dynamic rows

Book

Ordered Quantity

Received Quantity

Purchase Price

Total

Remarks

---

# Validation

Purchase must contain at least one item.

Book cannot be duplicated.

Received Quantity

> 0

Purchase Price

> = 0

Manual Purchase

Requires Supplier.

Receive Order Purchase

Requires Receive Order.

---

# Backend

Implement

Migration

Models

Relationships

Repositories

Repository Interfaces

Services

Controllers

Form Requests

Resources

Routes

Validation

Search

Filtering

Pagination

Sorting

Soft Delete

---

# Frontend

Pages

Purchase List

Create Purchase

Edit Purchase

View Purchase

Use

React Hook Form

Zod

TanStack Query

Axios

React Toastify

Reuse existing components.

---

# UI Components

Purchase Table

Dynamic Item Table

Search Box

Filters

Pagination

Status Badge

Date Picker

Supplier Select

Receive Order Select

Loading Skeleton

Breadcrumb

Action Dropdown

Delete Confirmation

---

# API

Consume existing Laravel API.

Never change response format.

Handle

200

201

401

403

404

422

500

Properly.

---

# Error Handling

Friendly messages.

Backend validation.

Network failures.

Unauthorized requests.

No raw exceptions.

---

# Performance

Server-side pagination.

Server-side search.

Eager loading.

Avoid N+1 queries.

Lazy loading where appropriate.

---

# Audit

Display

Created By

Updated By

Created At

Updated At

---

# Opencode Workflow

Before coding

Explain

- Database changes
- Files to create
- Files to modify
- API endpoints
- Relationships
- Risks

Wait for approval.

Implement step by step.

After every completed step

Summarize

- Files created
- Files modified
- APIs added
- UI completed
- Remaining tasks

Stop and wait.

---

# Restrictions

Do NOT

Implement Inventory

Modify Sales

Modify Authentication

Rewrite Receive Order

Modify Book module

Run migrations automatically

Run seeders

Drop tables

Rename existing tables

Modify unrelated files

---

# Definition of Done

Purchase module is complete only if

✓ Purchase CRUD completed

✓ Purchase Items completed

✓ Receive Order integration completed

✓ Receive Order progress updated

✓ Search completed

✓ Filtering completed

✓ Pagination completed

✓ Validation completed

✓ API Resources completed

✓ Repository implemented

✓ Service implemented

✓ Responsive UI completed

✓ Loading states completed

✓ Error handling completed

✓ No breaking changes

Stop after Phase 4.

Do NOT implement Inventory until Phase 5.
