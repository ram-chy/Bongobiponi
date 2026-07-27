# TASK: Build a Reusable Safe Delete Engine for Master Data

IMPORTANT

Read PROJECT_RULES.md completely before doing anything.

Follow every rule inside PROJECT_RULES.md.

Analyze the entire project before writing code.

Understand:

- Project architecture
- Models
- Relationships
- Controllers
- Services
- API responses
- Coding style

Do NOT start coding until the analysis is complete.

Do NOT modify unrelated files.

Do NOT redesign the existing architecture.

Do NOT execute any command.

Do NOT run:

- php artisan migrate
- php artisan migrate:fresh
- php artisan migrate:refresh
- php artisan db:seed
- composer install
- composer update
- npm install
- npm update

---

OBJECTIVE

Build a reusable Safe Delete Engine.

This engine will protect ALL Master Data modules.

Current modules

- Suppliers
- Publishers
- Authors
- Categories
- Books

The solution must be reusable for future modules.

Never duplicate deletion logic.

---

DELETE POLICY

Deletion is allowed ONLY when ALL validation rules pass.

============================================================ RULE 1 Relationship
Protection (Highest Priority)
============================================================

Before deleting any record

Analyze every relationship in the project.

Find where this record is being used.

Examples

Publisher ↓

Books

Author ↓

Books

Category ↓

Books

Supplier ↓

Purchases Purchase Items Inventory Goods Receive Stock Invoices Future modules

Book ↓

Quotation Items Order Items Sales Order Items Delivery Challan Items Invoice
Items Stock Inventory Purchase Items Future modules

These are examples only.

Inspect the existing project.

Automatically determine where the record is referenced.

Do NOT hardcode assumptions that may become outdated.

---

If the record is referenced anywhere

DO NOT DELETE.

Return a detailed response.

Example

{ "success": false, "message": "Unable to delete Publisher.",

    "reason": "This publisher is referenced by existing books.",

    "reference_module": "Books",

    "reference_count": 18,

    "sample_records": [
        "Book A",
        "Book B",
        "Book C"
    ]

}

If sample records cannot be determined safely, omit that field.

Never return a generic message.

The response must clearly explain WHY deletion is blocked.

============================================================ RULE 2 Latest
Record Protection ============================================================

If no dependencies exist

Only the newest record may be deleted.

Example

IDs

1 2 3 4 5

Allowed

Delete 5

Not Allowed

Delete 1 Delete 2 Delete 3 Delete 4

After deleting 5

Delete 4

Continue forever.

---

If blocked

Return

{ "success": false,

    "message": "Unable to delete Category.",

    "reason": "Newer records exist. Delete the most recently created record first.",

    "latest_record_id": 15

}

============================================================ RULE 3 Delete
Success ============================================================

Delete only when

✓ No relationships exist

AND

✓ Record is the latest record

---

Return

{ "success": true, "message": "Category deleted successfully." }

============================================================ ENGINE REQUIREMENTS
============================================================

Create ONE reusable Safe Delete Engine.

Do NOT duplicate logic.

The engine should

✓ Accept Model

✓ Accept Record ID

✓ Inspect relationships

✓ Count references

✓ Determine latest record

✓ Return structured validation result

Every Master Data controller must use this engine.

Future modules should require minimal configuration.

============================================================ API RESPONSE

Preserve the existing API response format.

Only extend it with additional fields when deletion fails.

Possible fields

message

reason

reference_module

reference_count

sample_records

latest_record_id

success

Do not remove existing response fields.

============================================================ FRONTEND SUPPORT

The frontend should be able to display

Title

Reason

Reference Module

Reference Count

Sample Records

without any additional API calls.

============================================================ DO NOT

Do NOT modify routes.

Do NOT redesign controllers.

Do NOT change authentication.

Do NOT change the database schema.

Do NOT create migrations.

Do NOT create seeders.

Do NOT modify unrelated files.

Do NOT remove existing functionality.

============================================================ OUTPUT

Before coding

Explain

1. Architecture analysis

2. Files to modify

3. Files to create

4. Why the chosen design is the best

Wait until the analysis is complete.

Then implement.

============================================================ FINAL VERIFICATION

Confirm

✓ Existing CRUD still works

✓ Relationship protection works

✓ Latest-record protection works

✓ Detailed error messages work

✓ API responses remain compatible

✓ No unrelated files changed

✓ Safe Delete Engine is reusable

✓ No destructive commands were executed

✓ PROJECT_RULES.md was followed throughout the implementation
