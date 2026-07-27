# TASK: Debug and Fix Password Recovery System

IMPORTANT

Read PROJECT_RULES.md completely before doing anything.

Follow every rule in PROJECT_RULES.md.

This is a debugging task.

DO NOT redesign the authentication system.

DO NOT rewrite the password recovery feature from scratch unless absolutely
necessary.

The objective is to identify the root cause and apply the smallest possible fix.

---

PROJECT ANALYSIS

Before writing any code, analyze the existing project.

Understand the complete Password Recovery flow.

Inspect:

- Routes
- Controllers
- Services
- Models
- Middleware
- Validation
- Mail configuration
- Queue configuration (if used)
- OTP generation
- OTP verification
- Password reset logic
- Frontend pages
- API calls
- Environment variables related to mail
- Existing logs

Do not assume anything.

---

OBJECTIVE

The Password Recovery system is currently not working.

Your job is to find the exact reason.

Do NOT implement a new password recovery system.

Debug the existing implementation.

---

DEBUG CHECKLIST

Verify every step.

STEP 1

Can the frontend call the correct API endpoint?

Check:

- URL
- HTTP Method
- Request Payload
- Headers
- Authentication requirements

---

STEP 2

Does the API route exist?

Verify

- Route registration
- Route middleware
- Route prefix

---

STEP 3

Does the request reach the controller?

Check

- Validation
- Exceptions
- Error handling

---

STEP 4

OTP Generation

Verify

- OTP is generated
- OTP length
- OTP expiration
- Secure random generation

---

STEP 5

Database

Verify

- OTP stored correctly
- Email exists
- Expiry stored
- Existing tokens updated properly

---

STEP 6

Mail

Verify

- Mail configuration
- SMTP settings
- Mail driver
- Mail queue
- Mail template
- Mail exceptions

Check whether the email is actually being sent.

---

STEP 7

OTP Verification

Verify

- OTP comparison
- Expiration validation
- Multiple attempts
- Invalid OTP handling

---

STEP 8

Password Reset

Verify

- Password validation
- Password hashing
- Database update
- User login after reset

---

STEP 9

Frontend

Verify

- API requests
- Error handling
- Success handling
- Loading state
- Validation
- OTP page
- Reset password page

---

STEP 10

Logs

Inspect

- Laravel logs

Check for

- Exceptions
- Mail errors
- Database errors
- Validation errors
- Stack traces

---

ROOT CAUSE ANALYSIS

Do NOT immediately fix the code.

First determine:

What is the exact root cause?

Examples

- Wrong API route
- Validation failure
- Mail configuration
- SMTP authentication
- Queue not running
- Incorrect environment variable
- Expired OTP logic
- Database issue
- Frontend request issue
- JWT/Auth conflict
- Controller bug
- Service bug

Identify the exact cause before changing anything.

---

IMPLEMENTATION RULES

Apply the smallest possible fix.

Modify only the files that require changes.

Do not refactor unrelated code.

Do not redesign authentication.

Do not change API responses unless necessary.

Preserve backward compatibility.

---

DO NOT

Do NOT run:

- php artisan migrate
- php artisan migrate:fresh
- php artisan migrate:refresh
- php artisan db:seed

Do NOT modify database schema.

Do NOT install new packages.

Do NOT modify unrelated files.

Do NOT change JWT authentication.

Do NOT redesign login or registration.

---

OUTPUT FORMAT

Before making changes provide:

1. Password recovery architecture overview

2. Complete request flow

3. Files involved

4. Root cause analysis

5. Exact file(s) causing the issue

6. Proposed minimal fix

Only after completing the analysis should you implement the fix.

---

AFTER IMPLEMENTATION

Provide:

- Files modified
- Why each file changed
- Explanation of the fix
- Manual testing procedure

---

FINAL VERIFICATION

Confirm that:

✓ Forgot Password API works

✓ OTP generation works

✓ OTP is stored correctly

✓ OTP email is sent successfully

✓ OTP verification works

✓ Expired OTP is rejected

✓ Invalid OTP is rejected

✓ Password reset works

✓ New password can be used for login

✓ Existing authentication remains unchanged

✓ No unrelated files were modified

✓ No destructive commands were executed

✓ PROJECT_RULES.md was followed throughout the task
