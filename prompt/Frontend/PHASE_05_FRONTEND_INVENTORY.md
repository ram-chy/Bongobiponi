# PHASE_05_FRONTEND_INVENTORY.md

# Phase 5 - Inventory Engine (Frontend)

Version: 1.0

Framework: Next.js (App Router)

Language: TypeScript

Status: Frontend Only

---

# Read Before Starting

Read the following documents completely before making any changes.

- FRONTEND_BLUEPRINT.md
- BACKEND_BLUEPRINT.md
- PROJECT_RULES.md

Analyze the existing frontend architecture before implementing anything.

Reuse existing layouts and components.

Do not redesign existing pages.

---

# Existing Modules

Completed

- Authentication
- Dashboard
- Supplier
- Publisher
- Author
- Category
- Book
- Receive Order
- Purchase

Reuse all existing UI components whenever possible.

---

# Objective

Implement the complete Inventory frontend.

The frontend is responsible for

- Displaying inventory
- Searching inventory
- Filtering inventory
- Viewing inventory history
- Viewing stock ledger
- Creating Opening Stock
- Creating Stock Adjustment
- Creating Damage Entry

The frontend must NEVER calculate stock.

Current stock always comes from the backend.

---

# Navigation

Add Inventory menu

Inventory

├── Dashboard

├── Current Stock

├── Inventory Ledger

├── Stock Adjustment

├── Damage Entry

├── Opening Stock

---

# Pages

Create

Inventory Dashboard

Current Stock List

Book Inventory Details

Inventory Ledger

Opening Stock

Stock Adjustment

Damage Entry

---

# Dashboard

Cards

Total Books

Total Stock Quantity

Low Stock

Out of Stock

Recent Transactions

Latest Adjustments

Latest Damages

Charts (Future Ready)

---

# Current Stock Page

Display

Book Cover

ISBN

Book Title

Category

Publisher

Current Quantity

Minimum Stock

Stock Status

Actions

---

# Stock Status

Display badges

In Stock

Low Stock

Out of Stock

Status comes from backend.

---

# Book Details

Display

Book Information

Current Quantity

Transaction Summary

Recent Transactions

Opening Stock

Purchase History

Adjustment History

Damage History

Future Sales History

---

# Inventory Ledger

Display

Transaction Number

Date

Book

Transaction Type

Reference Type

Reference Number

Quantity In

Quantity Out

Running Quantity

Remarks

Created By

---

# Opening Stock

Create page

Fields

Book

Quantity

Unit Cost

Transaction Date

Remarks

Validation

Book required

Quantity > 0

Unit Cost >= 0

---

# Stock Adjustment

Fields

Book

Adjustment Type

Increase

Decrease

Quantity

Reason

Transaction Date

Remarks

---

# Damage Entry

Fields

Book

Quantity

Reason

Date

Remarks

---

# Search

Server-side search

Support

Book

ISBN

Transaction Number

Reference Number

---

# Filters

Book

Category

Publisher

Transaction Type

Date Range

Stock Status

---

# Sorting

Book

Date

Current Quantity

Created Date

---

# Pagination

Use backend pagination.

Never implement client-side pagination.

---

# UI Components

Reuse existing components.

Required

Inventory Table

Ledger Table

Search Box

Filter Panel

Date Picker

Book Selector

Status Badge

Statistic Cards

Breadcrumb

Loading Skeleton

Empty State

Error State

Action Dropdown

Confirmation Dialog

---

# Forms

Use

React Hook Form

-

Zod

Requirements

Display validation errors.

Disable submit while saving.

Prevent duplicate submission.

---

# API Integration

Consume Laravel API only.

Never calculate inventory.

Never modify stock directly.

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

# Notifications

Use React Toastify.

Success

Error

Warning

Information

Never use browser alert().

---

# Loading States

Page Loading

Table Loading

Button Loading

Form Loading

Skeleton Loading

---

# Error Handling

Display backend validation messages.

Handle

Network Error

Unauthorized

Forbidden

Validation Error

Server Error

Friendly messages only.

---

# Permissions

Respect existing permissions.

Hide unauthorized buttons.

Disable unauthorized actions.

Backend authorization remains the source of truth.

---

# Responsive Design

Desktop

Tablet

Mobile

Every page must be responsive.

---

# Accessibility

Keyboard navigation

ARIA labels

Semantic HTML

Focus management

---

# Performance

Lazy loading

Dynamic imports

Memoization where appropriate

Avoid unnecessary re-renders

Server-side filtering

Server-side searching

---

# Coding Standards

TypeScript Strict Mode

Functional Components

Reusable Hooks

Small Components

No duplicated code

Meaningful names

No inline API calls

---

# Hooks

Create reusable hooks where needed

useInventory()

useInventoryLedger()

useOpeningStock()

useStockAdjustment()

useDamageEntry()

---

# Services

InventoryService

OpeningStockService

StockAdjustmentService

DamageService

LedgerService

All API calls must go through services.

---

# Opencode Workflow

Before implementation

Explain

1. Pages to create

2. Components to create

3. Services to create

4. Hooks to create

5. Types to create

6. API endpoints used

7. Files to modify

Wait for approval.

Implement one page at a time.

After every completed page

Summarize

Created files

Modified files

Components completed

API integration

Remaining work

Stop and wait.

---

# Restrictions

Do NOT

Modify Authentication

Modify Dashboard Layout

Modify Purchase

Modify Receive Order

Modify Book module

Duplicate inventory logic

Calculate stock on frontend

Hardcode API URLs

Modify unrelated files

Generate placeholder code

Use mock data

---

# Definition of Done

Inventory Frontend is complete only if

✓ Dashboard completed

✓ Current Stock page completed

✓ Inventory Ledger completed

✓ Opening Stock page completed

✓ Stock Adjustment page completed

✓ Damage Entry page completed

✓ Search implemented

✓ Filtering implemented

✓ Pagination implemented

✓ API integration completed

✓ Loading states completed

✓ Error handling completed

✓ Responsive design completed

✓ TypeScript clean

✓ ESLint clean

✓ Production ready

Stop after Phase 5.

Do NOT start Sales Integration without approval.
