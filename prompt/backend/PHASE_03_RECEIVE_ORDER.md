# PHASE_03_RECEIVE_ORDER.md

# Phase 3 - Receive Order Management

Version: 1.0

Module: Receive Order

Architecture: Laravel API + Next.js

Status: Backend + Frontend

---

# Read Before Starting

Read the following documents completely before making any changes.

- BACKEND_BLUEPRINT.md
- FRONTEND_BLUEPRINT.md
- PROJECT_RULES.md

Analyze the existing backend and frontend architecture before implementing
anything.

Do not assume missing functionality.

---

# Existing Project Status

Completed Modules

- Authentication
- User Management
- Role & Permission
- Supplier
- Publisher
- Author
- Category
- Book Master

These modules are production-ready.

Do NOT redesign them.

Reuse existing architecture.

---

# Objective

Implement the complete Receive Order module.

This module records the supplier's expected delivery before books are physically
received.

Receive Order DOES NOT update inventory.

Inventory updates only after Purchase Confirmation.

---

# Business Workflow

Supplier

↓

Receive Order

↓

Pending Delivery

↓

Purchase

↓

Inventory

---

# Business Rules

A Receive Order is a commitment from a supplier.

It is NOT proof of receiving books.

It does NOT affect inventory.

A Receive Order may be:

- Fully received
- Partially received
- Not received
- Cancelled

One Receive Order may generate multiple Purchase records.

Every Purchase must reference its originating Receive Order.

---

# Receive Order Status

Draft

Approved

Partially Received

Completed

Cancelled

Status transitions must be validated.

Invalid transitions must be rejected.

---

# Database Design

## receive_orders

Fields

- id
- order_no (auto-generated, unique)
- supplier_id
- expected_delivery_date
- reference_no (nullable)
- notes (nullable)
- status
- created_by
- updated_by
- timestamps
- soft deletes

---

## receive_order_items

Fields

- id
- receive_order_id
- book_id
- ordered_quantity
- received_quantity (default 0)
- purchase_price
- remarks (nullable)
- timestamps

---

# Relationships

Supplier

1

↓

Many

Receive Orders

Receive Order

1

↓

Many

Receive Order Items

Book

1

↓

Many

Receive Order Items

---

# Order Number Format

Generate automatically.

Example

RO-2026-000001

Requirements

- Unique
- Sequential
- Readable

Do not allow manual editing.

---

# Backend Requirements

Implement

Migration

Model

Relationships

Repository Interface

Repository Implementation

Service

Controller

Form Requests

API Resources

Routes

Validation

Business Rules

Search

Filtering

Pagination

Sorting

Soft Delete

---

# Frontend Requirements

Create pages

Receive Order List

Create Receive Order

Edit Receive Order

View Receive Order

---

# UI Features

Data Table

Server-side Pagination

Server-side Search

Sorting

Filters

Status Badge

Breadcrumb

Loading Skeleton

Empty State

Error State

---

# Receive Order Form

Header Information

Supplier

Expected Delivery Date

Reference Number

Notes

Status

---

# Item Entry

Support dynamic rows.

Each row contains

Book

Quantity

Purchase Price

Remarks

Add Row

Remove Row

---

# Item Rules

At least one item is required.

Book cannot be duplicated within the same Receive Order.

Quantity must be greater than zero.

Purchase Price cannot be negative.

---

# API Integration

Consume Laravel API only.

Do not hardcode responses.

Use existing API response structure.

Handle

200

201

401

403

404

422

500

correctly.

---

# Validation

Backend

Use Form Requests.

Frontend

Use React Hook Form + Zod.

Display backend validation errors.

---

# Search

Server-side search.

Support searching by

Order Number

Supplier

Reference Number

Status

---

# Filters

Supplier

Status

Date Range

Expected Delivery Date

---

# Permissions

Respect existing authentication.

Hide unauthorized actions.

Never rely only on frontend authorization.

---

# Status Actions

Draft

↓

Approve

↓

Partially Received

↓

Completed

Cancelled can occur before completion.

Completed records cannot be edited.

Cancelled records cannot generate Purchases.

---

# Audit

Track

Created By

Updated By

Created At

Updated At

Display these details on the View page.

---

# UI Components

Reuse existing components.

Only create new reusable components if necessary.

Required

Data Table

Status Badge

Dynamic Item Table

Search Box

Pagination

Modal

Delete Confirmation

Date Picker

Loading Skeleton

Breadcrumb

Action Dropdown

---

# Performance

Use eager loading.

Avoid N+1 queries.

Use server-side pagination.

Lazy load heavy frontend components.

---

# Error Handling

Return meaningful API responses.

Display friendly frontend messages.

Never expose stack traces.

---

# Opencode Workflow

Before writing any code

Explain

1. Implementation plan
2. Database changes
3. Backend files to create
4. Backend files to modify
5. Frontend files to create
6. Frontend files to modify
7. API endpoints
8. Risks
9. Dependencies

Wait for approval.

Implement one logical step at a time.

After every completed step

Summarize

- Created files
- Modified files
- APIs implemented
- Components created
- Remaining work

Then stop and wait.

---

# Restrictions

Do NOT

Rewrite working modules

Modify Authentication

Modify Book module

Modify Supplier module

Modify API response format

Modify unrelated files

Run migrations automatically

Run seeders

Drop tables

Rename existing tables

Generate placeholder code

Assume missing requirements

---

# Definition of Done

The Receive Order module is complete only if

✓ Database designed

✓ CRUD implemented

✓ Dynamic item management

✓ Validation completed

✓ Repository implemented

✓ Service implemented

✓ API Resources implemented

✓ Search implemented

✓ Filtering implemented

✓ Pagination implemented

✓ Status workflow implemented

✓ Audit information available

✓ Frontend integrated

✓ Responsive UI

✓ Error handling complete

✓ Loading states complete

✓ No console errors

✓ No ESLint errors

✓ No breaking changes

Stop after completing Phase 3.

Do NOT start Purchase or Inventory without approval.
