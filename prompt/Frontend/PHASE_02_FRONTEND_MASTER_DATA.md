# PHASE_02_FRONTEND_MASTER_DATA.md

# Phase 2 - Frontend Master Data

Version: 1.0

Frontend: Next.js (App Router)

Language: TypeScript

Purpose:
Implement the complete frontend for the Master Data modules using the existing Laravel API.

---

# Read Before Starting

Read these files completely before making any changes.

- FRONTEND_BLUEPRINT.md
- BACKEND_BLUEPRINT.md
- PROJECT_RULES.md

Analyze the existing frontend architecture first.

Do NOT assume anything.

---

# Existing Project Status

Already Completed

- Authentication
- Login
- JWT Authentication
- Protected Routes
- Dashboard Layout
- Sidebar
- Navbar
- Axios Configuration
- API Client
- Theme
- Toast Notifications
- Global Error Handling

Reuse all existing architecture.

Do NOT redesign working code.

---

# Objective

Implement the frontend for the following modules only.

- Supplier
- Publisher
- Author
- Category
- Book

Nothing else.

---

# Before Writing Code

Analyze the project.

Then explain:

1. Implementation plan
2. Folder structure
3. Components to create
4. Pages to create
5. Hooks to create
6. Services to create
7. Types to create
8. API integration strategy
9. Files to modify

Wait for approval before writing code.

---

# General Rules

Every module must follow exactly the same architecture.

Feature

↓

Page

↓

Components

↓

Hooks

↓

Service

↓

Laravel API

Never call Axios directly inside React components.

Always use Service classes/functions.

---

# Folder Structure

Each module should have:

features/

supplier/

components/

hooks/

services/

types/

schemas/

Repeat the same structure for:

publisher

author

category

book

---

# Pages

Create pages for:

Supplier List

Create Supplier

Edit Supplier

Publisher List

Create Publisher

Edit Publisher

Author List

Create Author

Edit Author

Category List

Create Category

Edit Category

Book List

Create Book

Edit Book

---

# CRUD Features

Each module must support

- List
- Details
- Create
- Update
- Soft Delete

Do not implement permanent delete.

---

# Data Table Features

Every listing page must include:

Server-side Pagination

Server-side Search

Server-side Sorting

Column Filters

Responsive Table

Loading Skeleton

Empty State

Error State

Action Menu

Refresh Button

---

# Forms

Use

React Hook Form

+

Zod

Requirements

- Client-side validation
- Display backend validation errors
- Disable submit while saving
- Loading button
- Prevent duplicate submission

---

# Supplier Module

Fields

- Name
- Company Name
- Phone
- Email
- GST Number
- Address
- Remarks
- Status

---

# Publisher Module

Fields

- Name
- Phone
- Email
- Address
- Remarks
- Status

---

# Author Module

Fields

- Name
- Biography
- Country
- Remarks
- Status

---

# Category Module

Fields

- Parent Category
- Name
- Description
- Status

Display category hierarchy.

---

# Book Module

Fields

- ISBN
- Barcode
- Title
- Subtitle
- Publisher
- Category
- Authors
- Edition
- Language
- Purchase Price
- Selling Price
- Minimum Stock
- Description
- Cover Image
- Status

Support multiple author selection.

Support image upload with preview.

---

# UI Components

Reuse existing components whenever possible.

Create reusable components only if necessary.

Required components

Data Table

Search Box

Pagination

Modal

Delete Confirmation

Status Badge

Image Upload

Image Preview

Loading Skeleton

Empty State

Error State

Breadcrumb

Form Card

Action Dropdown

---

# API Integration

Consume existing Laravel API.

Never change backend endpoints.

Never modify backend response format.

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

# Loading States

Implement

Page Loading

Table Loading

Form Loading

Button Loading

Image Loading

Skeleton Loading

---

# Notifications

Use React Toastify.

Success

Error

Warning

Information

Do not use browser alert().

---

# Error Handling

Display backend validation messages.

Handle network failures.

Handle unauthorized requests.

Handle server errors.

Never expose raw error objects.

---

# Search

Server-side search only.

Debounce search input.

300ms debounce.

---

# Pagination

Use backend pagination metadata.

Do not implement client-side pagination.

---

# Filtering

Server-side filtering only.

---

# Permissions

Hide unauthorized buttons.

Disable unauthorized actions.

Never rely only on frontend authorization.

---

# Responsive Design

Desktop

Tablet

Mobile

All pages must be responsive.

---

# Accessibility

Keyboard navigation

Proper labels

Focus states

Semantic HTML

---

# Performance

Lazy load heavy components.

Avoid unnecessary re-renders.

Use memoization only where beneficial.

Optimize image rendering.

---

# Code Standards

TypeScript Strict Mode

Functional Components

Reusable Hooks

Small Components

Readable Code

No duplicate logic

No inline API calls

Meaningful variable names

---

# Opencode Rules

Before every implementation

Explain

- Why
- What
- Impact

Wait for approval.

Implement one module at a time.

After each module

Summarize

Created files

Modified files

API endpoints used

Components created

Potential improvements

Then stop and wait.

---

# Restrictions

Do NOT

Rewrite working pages

Modify authentication

Modify dashboard

Modify routing without approval

Duplicate backend logic

Hardcode API URLs

Ignore existing coding style

Create placeholder code

Modify unrelated files

Generate mock data

Use local storage except authentication

---

# Definition of Done

A module is complete only if

✓ CRUD completed

✓ API integrated

✓ Pagination working

✓ Search working

✓ Filtering working

✓ Validation working

✓ Loading states implemented

✓ Error handling implemented

✓ Toast notifications implemented

✓ Responsive design completed

✓ Accessibility checked

✓ TypeScript clean

✓ ESLint clean

✓ Production ready

Stop after completing Phase 2.

Do not start Phase 3 without approval.