# CODE_REVIEW_AND_BUG_AUDIT.md

# Full Project Code Review & Bug Audit

Role: You are acting as a Senior Laravel, Next.js, TypeScript, MySQL, and
Software Architecture Engineer.

Your responsibility is to review the entire project.

Do NOT implement new features.

Do NOT refactor working code unless it fixes a real issue.

Do NOT rewrite code for style only.

---

# Objective

Perform a complete audit of the Book Shop Management System.

Focus on

- Bugs
- Hidden bugs
- Logic errors
- Runtime errors
- Security
- Performance
- API consistency
- Database consistency
- Architecture
- Code quality
- Future maintainability

---

# Read Before Starting

Read completely

- BACKEND_BLUEPRINT.md
- FRONTEND_BLUEPRINT.md
- PROJECT_RULES.md

Then analyze the whole project.

---

# Review Strategy

Review module by module.

Do not skip modules.

---

# Backend Review

Review

Authentication

Authorization

User Management

Supplier

Publisher

Author

Category

Book

Receive Order

Purchase

Inventory

Repositories

Services

Controllers

Requests

Resources

Routes

Models

Policies

Middleware

Enums

Traits

Exceptions

Events (if any)

Observers (if any)

Migrations

Seeders

Factories

Configuration

---

# Frontend Review

Review

Authentication

Protected Routes

Dashboard

Supplier

Publisher

Author

Category

Book

Receive Order

Purchase

Inventory

Layouts

Components

Hooks

Contexts

Services

Types

Schemas

Routing

Axios

Toast Notifications

Loading States

Error Handling

---

# Database Review

Check

Foreign Keys

Indexes

Constraints

Nullable fields

Cascade Rules

Soft Deletes

Relationships

Naming

Data consistency

Migration order

Future scalability

---

# API Review

Check

REST conventions

HTTP status codes

Validation

Pagination

Filtering

Sorting

Search

API Resources

Error responses

Authentication

Authorization

Consistency

---

# Inventory Review

Verify

InventoryService is the only stock updater.

No module updates stock directly.

Current stock is correct.

Inventory transactions remain the source of truth.

Negative stock validation works.

Transaction rollback is safe.

---

# Receive Order Review

Check

Status flow

Quantity calculations

Partial receive

Completed receive

Cancelled receive

Purchase relationship

---

# Purchase Review

Check

Purchase creation

Manual purchase

Receive Order purchase

Status

Quantity

Totals

Validation

Purchase integration

---

# Security Review

Check

Authentication

Authorization

Mass Assignment

SQL Injection

XSS

CSRF (where applicable)

Validation

Sensitive information exposure

File Upload Security

Access Control

---

# Performance Review

Check

N+1 queries

Eager Loading

Pagination

Indexes

Repeated queries

Duplicate API calls

Frontend re-renders

Large component rendering

Unused imports

Dead code

---

# Frontend UI Review

Check

Responsive design

Accessibility

Keyboard navigation

Loading states

Error states

Empty states

Toast messages

Form validation

Duplicate requests

Broken navigation

Broken links

Unused components

---

# Code Quality

Review

SOLID

DRY

KISS

Repository Pattern

Service Layer

Dependency Injection

Naming

Folder structure

Code duplication

Magic numbers

Magic strings

Comments

Readability

Maintainability

---

# Testing Simulation

Think through

Create

Update

Delete

Search

Filtering

Pagination

Receive Order

Purchase

Inventory

Error handling

Unauthorized requests

Network failures

Invalid input

Concurrent requests

Race conditions

---

# Output Format

Produce a report.

Use the following sections.

## Critical Issues

Issues that may corrupt data, break business logic, or create security risks.

## High Priority

Serious issues that should be fixed before production.

## Medium Priority

Issues affecting maintainability or user experience.

## Low Priority

Minor improvements.

## Architecture Suggestions

Long-term improvements.

## Performance Suggestions

Optimization opportunities.

## Security Suggestions

Potential vulnerabilities.

## Code Smells

Areas where code quality can be improved.

## Positive Findings

List well-designed parts of the project.

---

# Important Rules

Do NOT modify code automatically.

Do NOT create pull requests.

Do NOT refactor code.

Do NOT rename files.

Do NOT change APIs.

Do NOT change database schema.

Only identify issues and recommend fixes.

For every issue include:

- Severity
- File(s)
- Line(s) if available
- Description
- Root cause
- Recommended fix
- Risk if ignored

---

# Final Summary

Provide

- Overall project score (0–10)
- Backend score
- Frontend score
- Database score
- Security score
- Performance score
- Maintainability score
- Production readiness percentage

Finally answer:

"Would you deploy this project to production today?"

If the answer is "No", explain exactly why and list the minimum fixes required
before deployment.
