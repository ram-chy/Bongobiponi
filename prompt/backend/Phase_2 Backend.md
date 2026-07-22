# PHASE_02_MASTER_DATA.md

# Phase 2 - Master Data Implementation

## Objective

Implement the complete **Master Data** module for the Book Shop Management
System.

This phase establishes all reference data required before implementing
transactional modules such as Receive Order, Purchase, Inventory and Sales.

---

# IMPORTANT

Read the following documents before doing anything.

1. BLUEPRINT.md
2. PROJECT_RULES.md (if available)

Analyze the existing project completely before making changes.

Do not assume anything.

---

# Existing Project Status

The following modules already exist and are production-ready.

- Authentication
- Login
- JWT Authentication
- User Management
- Role & Permission
- API Response Structure
- Exception Handling
- Repository Pattern
- Service Layer
- API Resources
- Base Architecture

These modules MUST NOT be redesigned.

Reuse existing architecture.

---

# Goal

Implement the following modules only.

- Supplier
- Publisher
- Author
- Category
- Book Master

Nothing else.

---

# Development Rules

Before writing code:

1. Analyze the existing architecture.
2. Explain your implementation plan.
3. List every file that will be created.
4. List every file that will be modified.
5. Explain database design.
6. Explain relationships.
7. Wait for approval.

Do not write code until the plan is approved.

---

# Architecture Rules

Follow the project's existing architecture.

Controller

↓

Form Request

↓

Service

↓

Repository

↓

Model

↓

Database

Business logic belongs only in Services.

Repositories should only access the database.

Controllers must remain thin.

Never place business logic inside controllers.

Never place business logic inside models.

---

# Module 1

## Supplier

Create complete CRUD.

Fields

- id
- name
- company_name
- phone
- email
- gst_number (nullable)
- address
- remarks (nullable)
- status
- created_by
- updated_by
- timestamps
- soft deletes

Validation

- Name required
- Company Name required
- Phone unique
- Email nullable
- Status boolean

---

# Module 2

## Publisher

CRUD

Fields

- id
- name
- phone (nullable)
- email (nullable)
- address
- remarks
- status
- created_by
- updated_by
- timestamps
- soft deletes

---

# Module 3

## Author

CRUD

Fields

- id
- name
- biography (nullable)
- country (nullable)
- remarks
- status
- created_by
- updated_by
- timestamps
- soft deletes

---

# Module 4

## Category

CRUD

Fields

- id
- name
- description
- status
- created_by
- updated_by
- timestamps
- soft deletes

Support unlimited parent-child categories using a self-referencing relationship.

---

# Module 5

## Book Master

Create complete CRUD.

Book represents a unique title.

Do NOT manage inventory here.

Inventory belongs to a separate module.

Fields

- id
- isbn
- barcode
- title
- subtitle
- publisher_id
- category_id
- edition
- language
- purchase_price
- selling_price
- minimum_stock
- description
- cover_image
- status
- created_by
- updated_by
- timestamps
- soft deletes

---

# Book Author Relationship

A book may have multiple authors.

An author may write multiple books.

Implement many-to-many relationship.

Use a pivot table.

book_author

- id
- book_id
- author_id

---

# API Requirements

Implement REST APIs.

Each module should support

GET List

GET Details

POST Create

PUT Update

DELETE Soft Delete

Restore (optional)

Pagination

Search

Filtering

Sorting

Validation

API Resources

Consistent JSON response

---

# Validation

Use Form Request validation.

Never validate inside controllers.

Use custom validation messages where appropriate.

---

# Repository Layer

Every module should have

Repository Interface

Repository Implementation

No business logic inside repositories.

---

# Service Layer

Every module should have

Create

Update

Delete

List

Details

Business logic belongs here.

---

# Resources

Every API response should use API Resources.

Never return raw models.

---

# Database Rules

Use foreign keys.

Use indexes where appropriate.

Use soft delete.

Never remove existing tables.

Never rename existing columns.

Never modify authentication tables.

---

# Coding Standards

Follow PSR-12.

Use Dependency Injection.

Use Laravel naming conventions.

Use expressive method names.

Avoid duplicated code.

---

# Performance

Use eager loading.

Avoid N+1 queries.

Paginate large datasets.

---

# Error Handling

Return proper HTTP status codes.

Log unexpected exceptions.

Do not suppress errors.

---

# Before Every Step

Explain

- what will change
- why it is required
- impact on existing modules

Wait for approval.

---

# Restrictions

Do NOT

- Rewrite working code
- Modify Authentication
- Modify Role & Permission
- Change API response structure
- Rename existing files
- Remove existing functionality
- Create placeholder code
- Generate fake implementations
- Run seeders
- Run migrations automatically
- Drop tables
- Modify unrelated files

---

# Completion Criteria

This phase is complete only if

- Supplier CRUD complete
- Publisher CRUD complete
- Author CRUD complete
- Category CRUD complete
- Book CRUD complete
- Book-Author relationship complete
- Repository layer complete
- Service layer complete
- Form Requests complete
- API Resources complete
- Validation complete
- Pagination implemented
- Search implemented
- Filtering implemented
- Soft Delete implemented
- Documentation updated

Only then proceed to Phase 3.

Stop after completing Phase 2. Wait for further instructions.
