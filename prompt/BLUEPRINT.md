# Book Shop Management System

## Project Blueprint

Version: 1.0 Status: Planning Framework: Laravel API Frontend: (To be
implemented separately) Database: MySQL Architecture: Modular Monolith AI
Assistant: Opencode

---

# Project Vision

Develop a scalable, maintainable, and production-ready Book Shop Management
System.

The system must prioritize:

- Clean Architecture
- SOLID Principles
- Reusable Components
- High Readability
- Low Coupling
- High Cohesion
- Future Scalability

This project is expected to grow over time. Every implementation must consider
future modules before writing code.

Never sacrifice architecture for short-term convenience.

---

# Primary Business Workflow

Supplier │ ▼ Receive Order │ ▼ Purchase │ ▼ Inventory │ ▼ Sales │ ▼ Reports

Inventory is the core module.

Every stock movement must pass through the Inventory Transaction system.

---

# Core Modules

Authentication

User Management

Role & Permission

Supplier

Publisher

Author

Category

Book Master

Receive Order

Purchase

Inventory

Sales

Reports

Settings

---

# Module Dependency

Authentication │ ▼ User │ ▼ Master Data │ ▼ Receive Order │ ▼ Purchase │ ▼
Inventory │ ▼ Sales │ ▼ Reports

---

# Master Data

Supplier

Publisher

Author

Category

Book

Master data should remain independent.

Deleting master records should never break transactional records.

Use proper foreign keys.

Use soft delete where appropriate.

---

# Inventory Philosophy

Inventory is the source of truth.

Purchase does not own stock.

Sales does not own stock.

Inventory owns stock.

Every stock-changing operation must create an Inventory Transaction.

Examples:

Purchase

Purchase Return

Sale

Sale Return

Damage

Adjustment

Opening Stock

Future Warehouse Transfer

Stock should never be manually modified.

---

# Receive Order

Purpose:

Track supplier commitments before physical goods are received.

Receive Order does NOT affect inventory.

Receive Order may produce one or multiple Purchase records in the future.

---

# Purchase

Purchase confirms actual received goods.

Only Confirmed Purchase updates Inventory.

Draft Purchase does not change stock.

Cancelled Purchase never changes stock.

---

# Sales

Sales module already exists.

Reuse existing architecture whenever possible.

Avoid redesign unless absolutely necessary.

Inventory deduction must occur only after successful sales confirmation.

---

# Reporting

Reports must never store calculated values.

Reports should be generated from transactional data.

Examples

Purchase Report

Sales Report

Inventory Ledger

Supplier Ledger

Book Ledger

Profit Analysis

Daily Sales

Monthly Sales

Yearly Sales

---

# Architecture Principles

Follow layered architecture.

Controller

↓

Request Validation

↓

Service

↓

Repository

↓

Model

↓

Database

Business logic belongs inside Services.

Repositories should only communicate with the database.

Controllers should remain thin.

Avoid fat controllers.

Avoid business logic inside Models.

---

# Code Standards

Use Laravel best practices.

Use Form Request Validation.

Use API Resources.

Use Repository Pattern.

Use Service Layer.

Use Database Transactions where necessary.

Use Eloquent Relationships correctly.

Use Dependency Injection.

Never duplicate business logic.

Write expressive method names.

Prefer readability over clever code.

---

# Naming Convention

Tables

plural_snake_case

Models

Singular PascalCase

Controllers

ResourceController

Services

BookService

Repositories

BookRepository

Requests

StoreBookRequest

UpdateBookRequest

Resources

BookResource

Enums

PurchaseStatus

InventoryTransactionType

---

# API Standard

RESTful APIs.

Consistent response format.

HTTP Status Codes must be correct.

Validation errors must follow Laravel standards.

Pagination should be consistent across endpoints.

---

# Error Handling

Never suppress exceptions.

Log unexpected errors.

Return meaningful API responses.

Use custom exceptions where appropriate.

---

# Database Rules

Never modify production-safe schema carelessly.

Prefer additive migrations.

Avoid destructive migrations.

Never delete columns unless explicitly approved.

Never drop tables without permission.

---

# Future Ready Features

Purchase Return

Sales Return

Multi Warehouse

Barcode

QR Code

Book Reservation

Online Orders

POS

Supplier Payment

Customer Credit

Expense

Accounting

GST/VAT

Dashboard

Notification

Audit Log

Activity Timeline

Exports

Imports

---

# Performance Guidelines

Use eager loading.

Avoid N+1 queries.

Index searchable columns.

Use pagination.

Optimize joins.

Cache only when necessary.

---

# Security

Always validate input.

Never trust client data.

Protect mass assignment.

Use authorization policies.

Respect authentication.

Never expose sensitive information.

---

# AI Development Rules (Opencode)

Before writing code:

1. Analyze existing architecture.
2. Reuse existing code whenever possible.
3. Never rewrite working modules.
4. Preserve coding style.
5. Keep backward compatibility.

Before modifying any file:

Explain:

- why
- impact
- affected modules

Only then proceed.

Never generate unnecessary files.

Never rename files without approval.

Never change APIs unless required.

Never introduce breaking changes without warning.

---

# AI Restrictions

Do NOT:

Generate placeholder code.

Generate fake implementations.

Ignore existing architecture.

Duplicate logic.

Modify unrelated files.

Run database seeders.

Reset database.

Delete migrations.

Rename tables.

Change API contracts.

Remove existing functionality.

Assume requirements.

Rush implementation.

---

# Git Workflow

main

↓

develop

↓

feature/module-name

Every feature should be developed in its own branch.

Merge only after:

Review

Testing

Approval

---

# Development Workflow

Requirement

↓

Analysis

↓

Planning

↓

Database Design

↓

API Design

↓

Implementation

↓

Review

↓

Testing

↓

Documentation

↓

Merge

Never skip steps.

---

# Definition of Done

A module is complete only if:

✓ Database is stable

✓ APIs are complete

✓ Validation implemented

✓ Business rules implemented

✓ Error handling complete

✓ API Resources used

✓ Services implemented

✓ Repository implemented

✓ Inventory integrity preserved

✓ Documentation updated

✓ Existing features still work

Only then is the module considered complete.
