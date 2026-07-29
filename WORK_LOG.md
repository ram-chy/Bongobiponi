# Work Log

## Date: 2026-07-28

### Completed Modules (DO NOT TOUCH)
- **Auth** — OTP email fix (MAIL_MAILER=log → smtp)
- **Registration** — Customer module refactored (fields removed)
- **Master Data** — Categories, Books, Publishers, Authors, Suppliers all refactored

### Changes Made

#### Auth
- `backend/.env` — `MAIL_MAILER=log` → `smtp` for OTP emails

#### Customer (Registration > Customers)
Removed: company_name, status, gst_number, pan_number, credit_limit, opening_balance
- Backend: Model $fillable, Store/Update requests, Service (search/filter/sort), Factory, Seeder, Test
- Frontend: Form (zod schema + UI), list page columns, edit page defaultValues

#### Category (Master Data)
Removed: parent_id dropdown, status
- Backend: Model $fillable, Store/Update requests, Service, Factory, Seeder
- Frontend: Form (zod schema + UI), list page columns, edit/view pages

#### Book (Master Data)
Removed: status field entirely
- Backend: Model, Store/Update requests, Resource, Service, Factory, Seeder
- Frontend: Types, Form, list page, edit page, view page

Added cover image upload feature:
- `POST /api/books/upload-cover` route + controller
- GD-based compression (iterative quality reduction to ≤500KB)
- Temp upload → rename on book creation
- Preview in form (file input + blob URL)
- Thumbnail column in book list

#### Infrastructure
- `php.ini` — Set `upload_tmp_dir` to `backend/storage/php-upload-tmp/` (fixes UPLOAD_ERR_NO_TMP_DIR)
- `frontend/next.config.ts` — Added `http` protocol to `remotePatterns`
- `frontend/src/lib/axios.ts` — Request interceptor strips `Content-Type` for FormData

#### Sidebar
- Fixed accordion toggle: sections can now be collapsed even when active
- Uses `userCollapsed` state decoupled from `isChildActive`

### Changes Made (2026-07-29)

#### Auth
- `PasswordResetController.php` — Added user existence check in `forgotPassword()` and `verifyOtp()` so OTP is never sent/verified for non-existent accounts. Returns 404 "No Account Found for this email" early instead of failing with "Invalid token or email" only at the reset step.
- `app/layout.tsx` — Moved `<Toaster>` from `dashboard-layout.tsx` to root layout so toast notifications work on auth pages (forgot-password, login, etc.). Removed duplicate from dashboard layout.

### Next Time
- Skip Auth, Registration, and Master Data modules entirely
- Neither frontend nor backend changes to those three modules
