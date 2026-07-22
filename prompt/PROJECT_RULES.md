# PROJECT_RULES.md

# BongoBiponi ERP
## AI Development Rules & Project Guidelines

Version: 1.0
Last Updated: July 2026

## Golden Principle

Think first. Analyze second. Code last.

Never assume.
Never rush.
Preserve existing functionality above all else.
When uncertain, ask before acting.

## 1. Project Overview

Production-quality ERP.

Stack:
- Laravel 13 API
- PHP 8.4+
- MySQL
- JWT Authentication
- Next.js
- React
- Tailwind CSS
- Axios

Workflow:

Customer → Quotation → Order Booking → Sales Order → Delivery Challan → Invoice → Payment

## 2. Analyze Before Coding

Always analyze:
- Project structure
- Routes
- Controllers
- Services
- Models
- Migrations
- Middleware
- Validation
- Coding style

If anything is unclear, stop and ask.

## 3. Internet Usage

The AI may use the internet whenever necessary to verify:
- Official Laravel documentation
- Official PHP documentation
- Official Next.js documentation
- Official React documentation
- Official Tailwind CSS documentation
- Official package documentation
- GitHub repositories
- Security best practices

Prefer official documentation over blogs.

## 4. Coding Standards

- Follow SOLID
- Follow DRY
- Follow KISS
- Keep controllers thin
- Use Services where appropriate
- Use Form Requests
- Use API Resources
- Reuse existing code
- Maintain coding style

## 5. Never Do

- Never rewrite unrelated code.
- Never rename unrelated files.
- Never change API responses without approval.
- Never redesign architecture.
- Never break backward compatibility.

## 6. Database Rules

Always inspect relationships first.

Never:
- Drop tables
- Drop columns
- Rename columns

without explicit permission.

Create new migrations when needed.

## 7. Development Pace

Quality is more important than speed.

Never rush.

Implement one feature at a time.

Think before every change.

## 8. Destructive Operations

Never perform these without explicit permission:
- php artisan migrate
- php artisan migrate:fresh
- php artisan migrate:refresh
- php artisan db:seed
- php artisan migrate --seed
- Truncate tables
- Delete production/development data

## 9. Command Policy

Never automatically execute:
- composer install/update/remove
- npm install/update
- git reset/clean/revert/push/pull
- cache clear commands
- optimize clear

Explain why they are needed first.

## 10. File Modification Policy

Modify only required files.

Return only changed files unless requested otherwise.

## 11. Security

Validate inputs.
Protect secrets.
Prevent SQL injection and XSS.
Validate uploads.

## 12. Performance

Avoid N+1 queries.
Use eager loading.
Paginate results.
Optimize queries.

## 13. PDF Rules

Preserve company branding and template.
Only dynamic data should change.

## 14. Final Checklist

Before finishing verify:
- Existing features still work.
- No syntax errors.
- No unnecessary refactoring.
- No destructive commands executed.
- No migrations or seeders run without permission.
