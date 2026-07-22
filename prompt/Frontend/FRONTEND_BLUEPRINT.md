# Book Shop Management System

# FRONTEND_BLUEPRINT.md

Version: 1.0

Frontend Framework: Next.js (App Router)

Language: TypeScript

UI: Tailwind CSS

State Management: React Context + TanStack Query

Form Library: React Hook Form

Validation: Zod

HTTP Client: Axios

Notification: React Toastify

Icons: Lucide React

Table: TanStack Table

Authentication: JWT (Laravel API)

AI Assistant: Opencode

---

# Project Vision

Develop a modern, fast, responsive and maintainable frontend for the Book Shop
Management System.

The frontend is responsible only for:

- User Interface
- User Experience
- Client-side Validation
- API Communication
- Authentication State
- Navigation
- Data Presentation

Business logic belongs to the Laravel API.

Never duplicate backend business logic in the frontend.

---

# Frontend Philosophy

Frontend is a consumer of APIs.

Backend is the source of truth.

Never calculate business rules in React.

Never duplicate inventory logic.

Never duplicate pricing logic.

Never duplicate stock calculations.

---

# Technology Stack

Framework

- Next.js (App Router)

Language

- TypeScript

Styling

- Tailwind CSS

State

- React Context
- TanStack Query

Forms

- React Hook Form
- Zod

HTTP

- Axios

Notifications

- React Toastify

Icons

- Lucide React

Tables

- TanStack Table

Loading

- Skeleton Components

---

# Folder Structure

src/

app/

components/

features/

hooks/

contexts/

services/

lib/

types/

schemas/

constants/

utils/

layouts/

providers/

styles/

---

# Feature Structure

features/

supplier/

publisher/

author/

category/

book/

purchase/

inventory/

sales/

reports/

Each feature should be self-contained.

---

# Component Structure

Each feature should contain:

components/

pages/

hooks/

services/

types/

schemas/

constants/

---

# Routing

Use App Router.

Example

/login

/dashboard

/suppliers

/publishers

/authors

/categories

/books

/purchases

/inventory

/sales

/reports

/settings

---

# Layout

Application Layout

Navbar

Sidebar

Header

Breadcrumb

Content Area

Footer

All pages should use the same layout.

---

# Design Principles

Clean

Modern

Responsive

Accessible

Minimal

Consistent

Avoid unnecessary animations.

---

# Theme

Light Theme

Dark Theme

System Theme

Support all three.

---

# Responsive Design

Desktop

Tablet

Mobile

Every page must be responsive.

Never create desktop-only layouts.

---

# Authentication

Use JWT.

Store token securely.

Automatically attach Authorization header.

Handle token expiration.

Redirect unauthenticated users to Login.

Never expose protected pages.

---

# API Communication

All API calls must go through services.

Never call axios directly inside components.

Correct

Component

↓

Service

↓

Axios

↓

Laravel API

Incorrect

Component

↓

Axios

---

# State Management

Server State

TanStack Query

Client State

React Context

Avoid unnecessary global state.

---

# Form Standards

Use React Hook Form.

Use Zod validation.

Display validation errors.

Disable submit while processing.

Prevent duplicate submissions.

---

# Table Standards

Every CRUD page should support:

Pagination

Search

Sorting

Filtering

Column Visibility

Row Selection (future)

Export Ready

---

# CRUD Page Layout

Header

Create Button

Search

Filters

Data Table

Pagination

Action Buttons

---

# UI Components

Create reusable components.

Button

Input

Textarea

Select

Checkbox

Switch

Badge

Modal

Drawer

Alert Dialog

Card

Table

Pagination

Breadcrumb

Loader

Skeleton

Empty State

Error State

Avatar

Dropdown

Tooltip

Popover

Date Picker

File Upload

Image Preview

---

# Notifications

Success

Error

Warning

Information

Use React Toastify.

Never use browser alerts.

---

# Loading States

Skeleton

Button Loading

Page Loading

Table Loading

Image Loading

---

# Error Handling

Show friendly error messages.

Display API validation messages.

Handle network failures.

Handle unauthorized requests.

Never expose stack traces.

---

# Search

Debounce search input.

Server-side searching.

Never load unnecessary records.

---

# Pagination

Use server-side pagination.

Frontend should consume pagination metadata from API.

---

# Filtering

Server-side filtering.

Do not filter large datasets on the client.

---

# File Upload

Image Preview

Progress Indicator

File Validation

Remove Image

Replace Image

---

# Reusable Hooks

useAuth

usePagination

useDebounce

useSearch

useModal

useConfirm

usePermissions

---

# API Response Handling

Always use the backend response structure.

Never assume response formats.

Handle:

Success

Validation Error

Unauthorized

Forbidden

Server Error

Network Error

---

# Permissions

Hide unauthorized actions.

Disable buttons when necessary.

Never rely only on frontend permissions.

Backend authorization is mandatory.

---

# Accessibility

Keyboard Navigation

Focus Management

ARIA Labels

Proper Contrast

Semantic HTML

---

# Performance

Lazy Loading

Dynamic Imports

Memoization where appropriate

Image Optimization

Avoid unnecessary re-renders.

---

# Coding Standards

TypeScript Strict Mode

Functional Components

Custom Hooks

Reusable Components

No duplicated code

Meaningful names

Small components

Readable code

---

# Opencode Development Rules

Before writing code:

1. Read BACKEND_BLUEPRINT.md.
2. Read FRONTEND_BLUEPRINT.md.
3. Analyze the existing frontend architecture.
4. Reuse existing components.
5. Never rewrite working pages.
6. Follow existing folder structure.
7. Explain implementation plan.
8. List files to create.
9. List files to modify.
10. Wait for approval.

---

# AI Restrictions

Do NOT:

Rewrite working components.

Change routing without approval.

Modify authentication flow.

Duplicate backend business logic.

Hardcode API URLs.

Create unnecessary components.

Ignore existing coding style.

Generate placeholder code.

Modify unrelated files.

---

# Development Workflow

Requirement

↓

Analysis

↓

UI Planning

↓

Component Design

↓

API Integration

↓

Testing

↓

Review

↓

Approval

Never skip analysis.

---

# Definition of Done

A frontend module is complete only if:

✓ Responsive

✓ API Integrated

✓ Validation Implemented

✓ Loading States

✓ Error Handling

✓ Toast Notifications

✓ Pagination

✓ Search

✓ Filtering

✓ Reusable Components

✓ TypeScript Types

✓ Accessibility

✓ Clean Code

✓ No Console Errors

✓ No ESLint Errors

✓ Ready for Production
