# STEP 13 — REQUIREMENT GAP RESOLUTION & NEXT WORKFLOW VALIDATION

## Project

**Bongobiponi — Book Store Management System / ERP**

---

# 1. OBJECTIVE

STEP 12 confirmed that the customer requirement does **not** specify:

```text
Customer Return
Physical Return
Returned Goods Receipt
Inventory Restoration
Refund
Credit Note
Exchange
Replacement
```

STEP 12 therefore correctly implemented **no return functionality** and stopped
at requirement-gap documentation.

STEP 13 must now perform a **Requirement Gap Resolution and Workflow
Validation** phase.

The primary objective is:

```text
Review the complete customer requirement
        ↓
Review all completed implementation phases
        ↓
Identify remaining customer-required workflows
        ↓
Identify unresolved business rules
        ↓
Separate REQUIRED features from OPTIONAL/UNDEFINED features
        ↓
Define the correct next implementation phase
```

**Do not implement a Return module in STEP 13 unless new customer requirements
explicitly authorize it.**

---

# 2. MANDATORY BASELINE

Before doing anything, inspect these documents/reports:

```text
ERM requirement.docx

STEP 01 implementation report
STEP 02 implementation report
STEP 03 implementation report
STEP 04 implementation report
STEP 05 implementation report
STEP 06 implementation report
STEP 07 implementation report
STEP 08 implementation report
STEP 09 implementation report
STEP 10 implementation report
STEP 11 implementation report
STEP 12 implementation report
```

Also inspect the actual current codebase.

Do not rely only on previous reports.

---

# 3. STEP 12 BASELINE

STEP 12 established:

```text
Customer Return = NOT SPECIFIED
Physical RTO Return = NOT SPECIFIED
Inventory Restoration = NOT SPECIFIED
Refund = NOT SPECIFIED
Credit Note = NOT SPECIFIED
Exchange = NOT SPECIFIED
Replacement = NOT SPECIFIED
```

Therefore:

```text
No Return entity
No ReturnItem entity
No Return API
No Return frontend
No Return migration
No return status
No inventory restoration
No financial reversal
```

must be introduced merely because these features are common in ERP systems.

---

# 4. CRITICAL RULE

The customer requirement is the source of truth.

Use this classification:

```text
CUSTOMER REQUIRED
CUSTOMER IMPLIED
ARCHITECTURALLY NECESSARY
OPTIONAL
NOT SPECIFIED
```

Do not convert:

```text
NOT SPECIFIED
```

into:

```text
REQUIRED
```

without evidence.

---

# 5. CUSTOMER REQUIREMENT AUDIT

Read the complete `ERM requirement.docx`.

Create a complete feature matrix.

Use this structure:

| Requirement | Customer Evidence           | Current Status              | Phase | Action         |
| ----------- | --------------------------- | --------------------------- | ----- | -------------- |
| Feature     | Exact requirement/reference | Implemented/Partial/Missing | Phase | Required/Defer |

Do not create requirements from general ERP assumptions.

---

# 6. WORKFLOW EXTRACTION

Extract every explicit workflow from the customer requirement.

At minimum inspect whether the customer defines:

```text
Customer
Book/Product
Supplier
Publisher
Purchase
Receive Order
Order
Sales
Inventory
Delivery
Invoice
Payment
Reports
User Management
```

Only include workflows that are actually supported by the customer requirement.

---

# 7. ORDER LIFECYCLE

The confirmed Order lifecycle currently includes:

```text
INTAKE
   ↓
TO_PROCURE / TO_PACK
   ↓
PACKED
   ↓
DISPATCHED
   ↓
DELIVERED
        OR
RTO
```

Do not add:

```text
RETURNED
RETURN_REQUESTED
RETURN_APPROVED
RETURN_RECEIVED
```

because STEP 12 established that customer returns are not specified.

---

# 8. RTO RULE

Preserve:

```text
DISPATCHED → RTO
```

as the STEP 11 status-only workflow.

Do not change RTO behavior in STEP 13.

Current RTO behavior remains:

```text
Order status changes
OrderStatusHistory created
No inventory mutation
No reservation mutation
No invoice mutation
No payment mutation
No Delivery Challan mutation
```

---

# 9. NO RETURN IMPLEMENTATION

Do NOT create:

```text
returns
return_items
ReturnController
ReturnService
ReturnPolicy
ReturnStatus
Return routes
Return frontend pages
```

unless the customer has supplied a new requirement.

---

# 10. INVENTORY AUDIT

Inspect the current inventory architecture.

Determine:

```text
Purchase → Inventory
Sales → Inventory
Delivery → Inventory
Reservation → Inventory
Allocation → Inventory
```

and identify whether every customer-required inventory event is already
implemented.

Do not modify inventory merely for STEP 13 analysis.

---

# 11. INVENTORY TRANSACTION AUDIT

Inspect:

```text
InventoryTransactionType
InventoryService
Stock
InventoryTransaction
OrderStockReservation
OrderStockReservationService
```

Document all currently supported transaction types.

Classify each as:

```text
USED
AVAILABLE BUT UNUSED
NOT REQUIRED
```

For example:

```text
SALE_RETURN
```

must remain:

```text
AVAILABLE BUT UNUSED
```

unless the customer requirement changes.

---

# 12. RESERVATION AUDIT

Inspect the existing reservation workflow from STEP 09.

Document:

```text
Reservation creation
Reservation release
Reservation consumption
FIFO allocation
Stock availability
Concurrency protection
```

Confirm that the STEP 09 allocator remains the single allocation mechanism.

Do not create another allocator.

---

# 13. PURCHASE WORKFLOW AUDIT

Inspect the current Purchase / Procurement implementation.

Determine whether the customer-required workflow is:

```text
Purchase
    ↓
Receive
    ↓
Inventory
```

or whether there are missing intermediate steps.

Do not infer additional procurement stages without customer evidence.

---

# 14. RECEIVE ORDER AUDIT

Because the customer requirement mentions receiving/order-related operations,
inspect:

```text
Purchase Order
Receive Order
Purchase Receipt
Stock Receipt
```

Determine exactly which of these are implemented.

If terminology differs between the customer document and current architecture:

document the mapping.

Do not silently rename business concepts.

---

# 15. BOOK MANAGEMENT AUDIT

Inspect:

```text
Books
Categories
Publishers
Authors
ISBN
Price
Cost
Stock
```

Determine which fields are customer-required and which are application
additions.

Do not add new book metadata without requirement evidence.

---

# 16. SALES WORKFLOW AUDIT

Inspect the complete sales process.

Determine whether the current architecture supports the customer-required
sequence:

```text
Customer
   ↓
Order
   ↓
Packing
   ↓
Dispatch
   ↓
Delivery
```

and determine where:

```text
Invoice
Payment
```

fit into the customer's requirement.

Do not change the existing workflow without evidence.

---

# 17. DELIVERY AUDIT

Inspect:

```text
Delivery Challan
Delivery Challan Item
Order
Order Item
Invoice
Invoice Item
```

Determine whether the customer requires:

```text
Delivery Challan
Delivery confirmation
Partial delivery
Multiple deliveries
```

If not specified, document the gap.

Do not invent partial-delivery rules.

---

# 18. INVOICE AUDIT

Inspect the current Invoice implementation.

Determine:

```text
Invoice creation
Invoice numbering
Invoice items
Order relation
Delivery Challan relation
Customer relation
Tax
Totals
PDF
Invoice status
```

Compare each against the customer requirement.

Do not add credit notes or refund functionality.

---

# 19. PAYMENT AUDIT

Inspect:

```text
Payment
Payment status
Payment amount
Payment methods
Invoice relation
```

Determine what the customer actually requires.

Do not add:

```text
Refund
Partial Refund
Payment Reversal
Credit Note
```

unless explicitly required.

---

# 20. CUSTOMER MANAGEMENT AUDIT

Inspect:

```text
Customer CRUD
Customer fields
Customer-order relation
Customer-invoice relation
Customer search
Customer status
```

Compare against customer requirements.

Identify missing required fields only.

---

# 21. SUPPLIER AUDIT

Inspect:

```text
Supplier
Supplier fields
Supplier-Purchase relation
```

Determine whether supplier management is complete.

Document any missing customer-required functionality.

---

# 22. PUBLISHER AUDIT

Inspect:

```text
Publisher
Book-Publisher relation
Publisher management
```

Determine whether the customer's Publisher requirement is fully implemented.

---

# 23. USER / ROLE AUDIT

Inspect current authorization.

Document:

```text
Admin
Manager
Regular User
```

and determine what the customer requirement actually requires each role to do.

Do not create new roles.

Do not expand permissions without evidence.

---

# 24. REPORTING AUDIT

Inspect existing reports/dashboard.

Determine which reports are explicitly required by the customer.

Possible categories may include:

```text
Sales
Purchase
Inventory
Customer
Supplier
Payment
Order
```

Only mark them required if supported by the customer requirement.

---

# 25. SEARCH / FILTER / PAGINATION AUDIT

Review all major modules for customer-required usability features.

Check:

```text
Search
Filter
Pagination
Sorting
Date filtering
Status filtering
```

Do not add these everywhere automatically.

Only identify missing functionality that materially affects a customer
requirement.

---

# 26. PDF / DOCUMENT AUDIT

Inspect all required document generation.

Determine whether the customer requires:

```text
Purchase document
Order document
Delivery Challan
Invoice
Reports
```

Verify:

```text
Serial
Customer
Items
Quantity
Price
Totals
Dates
Company information
```

Do not redesign documents unless the requirement requires it.

---

# 27. DASHBOARD AUDIT

Inspect the current dashboard.

Compare displayed metrics against customer requirements.

Classify:

```text
Required
Useful but optional
Not specified
```

Do not add arbitrary KPIs.

---

# 28. DATA INTEGRITY AUDIT

Review the completed architecture for:

```text
Foreign keys
Cascade rules
Delete restrictions
Duplicate prevention
Quantity validation
Authorization
User ownership
Status transitions
```

Do not perform a broad security rewrite.

Only document issues relevant to the current customer workflow.

---

# 29. STATUS TRANSITION AUDIT

Inspect:

```text
OrderStatusTransitionService
OrderPolicy
OrderStatusHistory
```

Verify that all currently implemented status transitions are:

```text
valid
authorized
auditable
```

Do not add new statuses.

---

# 30. STATUS MATRIX

Produce:

| Current Status | Allowed Next Status | Authorization | Side Effects |
| -------------- | ------------------- | ------------- | ------------ |
| INTAKE         | ...                 | ...           | ...          |
| TO_PROCURE     | ...                 | ...           | ...          |
| TO_PACK        | ...                 | ...           | ...          |
| PACKED         | ...                 | ...           | ...          |
| DISPATCHED     | ...                 | ...           | ...          |
| DELIVERED      | ...                 | ...           | ...          |
| RTO            | ...                 | ...           | ...          |
| CANCELLED      | ...                 | ...           | ...          |

Use actual implementation.

Do not invent transitions.

---

# 31. COMPLETED FEATURES MATRIX

Create:

| Module    | Customer Required |    Implemented | Remaining |
| --------- | ----------------: | -------------: | --------- |
| Customer  |            Yes/No | Yes/Partial/No | ...       |
| Supplier  |            Yes/No | Yes/Partial/No | ...       |
| Publisher |            Yes/No | Yes/Partial/No | ...       |
| Book      |            Yes/No | Yes/Partial/No | ...       |
| Purchase  |            Yes/No | Yes/Partial/No | ...       |
| Order     |            Yes/No | Yes/Partial/No | ...       |
| Inventory |            Yes/No | Yes/Partial/No | ...       |
| Delivery  |            Yes/No | Yes/Partial/No | ...       |
| Invoice   |            Yes/No | Yes/Partial/No | ...       |
| Payment   |            Yes/No | Yes/Partial/No | ...       |
| Reports   |            Yes/No | Yes/Partial/No | ...       |

---

# 32. REQUIREMENT GAP CLASSIFICATION

Every gap must be classified as:

```text
P0 — Customer-required blocker
P1 — Customer-required important
P2 — Customer-required enhancement
P3 — Optional
P4 — Not specified
```

Do not classify an unspecified feature as P0/P1.

---

# 33. NEXT PHASE SELECTION

After the audit, determine the next implementation phase.

Possible result:

```text
NEXT PHASE = <specific customer-required feature>
```

or:

```text
NEXT PHASE = CUSTOMER CLARIFICATION REQUIRED
```

or:

```text
NEXT PHASE = BUG FIX / COMPLETION OF EXISTING REQUIRED FEATURE
```

Do not automatically select a Return phase.

---

# 34. HARD STOP

If all currently known customer requirements are implemented and no new
requirement is available:

```text
STOP.
```

Do not invent STEP 14 functionality.

Report:

```text
Customer requirement implementation is complete
for the currently specified scope.
```

---

# 35. CUSTOMER CLARIFICATION

Create a clarification table only for genuine missing business rules.

Use:

| Topic            | Existing Evidence | Missing Decision  | Blocking? |
| ---------------- | ----------------- | ----------------- | --------- |
| RTO inventory    | Not specified     | Customer decision | Yes       |
| Customer Return  | Not specified     | Customer decision | No        |
| Refund           | Not specified     | Customer decision | No        |
| Exchange         | Not specified     | Customer decision | No        |
| Partial Delivery | ...               | ...               | ...       |
| Payment Rules    | ...               | ...               | ...       |

Do not ask the customer about features that are completely irrelevant to the
existing requirement.

---

# 36. NO ASSUMPTION POLICY

Never implement a feature because:

```text
"ERP systems normally have it"
```

or:

```text
"this would be useful"
```

or:

```text
"the database already supports it"
```

Only implement when:

```text
customer requirement
OR
explicit architectural necessity
```

supports it.

---

# 37. REGRESSION REQUIREMENT

STEP 13 must preserve all completed functionality.

Run:

```text
php vendor/bin/phpunit
```

Expected result:

```text
0 failures
```

The current STEP 12 baseline is:

```text
207 passed
628 assertions
```

If the test count changes because STEP 13 adds tests, document the new total.

---

# 38. FRONTEND VALIDATION

Run:

```text
npm run lint
npm run build
```

The STEP 12 report recorded:

```text
Lint:
43 problems
5 errors
38 warnings
```

These were pre-existing.

STEP 13 must determine whether any of these are affected by STEP 13 work.

Do not claim pre-existing issues are fixed unless they actually are.

---

# 39. MIGRATION VALIDATION

Run:

```text
php artisan migrate:status
```

Confirm:

```text
No unexpected pending migrations
```

If STEP 13 requires no schema changes:

```text
No migration created.
```

If a migration is required by a customer-required feature:

document exactly why.

---

# 40. NO DATABASE REDESIGN

Do not redesign the database during STEP 13.

Only introduce schema changes when:

```text
customer-required feature
+
existing architecture cannot support it
```

both are true.

---

# 41. CODE QUALITY

If STEP 13 requires implementation:

follow existing architecture.

Prefer:

```text
Controller
    ↓
Policy / Authorization
    ↓
Service
    ↓
Repository / Model
```

according to the existing project architecture.

Do not introduce a competing architectural pattern.

---

# 42. FRONTEND ARCHITECTURE

Reuse existing:

```text
components
hooks
API client
validation
dialogs
notifications
tables
forms
status components
```

Do not duplicate existing functionality.

---

# 43. API CONSISTENCY

If an existing API already provides the required operation:

reuse it.

Do not create duplicate endpoints.

---

# 44. AUDIT TRAIL

Any new customer-required state-changing operation must preserve the existing
audit/history approach.

Do not silently modify data without history where the existing architecture
requires history.

---

# 45. AUTHORIZATION

Every new state-changing operation must verify:

```text
authenticated user
role
ownership / CreatedByScope
policy
```

according to the existing project architecture.

Do not bypass `OrderPolicy` or existing authorization services.

---

# 46. TEST REQUIREMENT

If STEP 13 implements anything:

tests must be added for:

```text
Happy path
Authorization
Validation
Invalid state
Duplicate operation
Data integrity
Regression
```

Only create tests relevant to the implemented feature.

---

# 47. IMPLEMENTATION REPORT

Create:

```text
prompt/STEP_13_REQUIREMENT_GAP_RESOLUTION_IMPLEMENTATION_REPORT.md
```

The report must contain:

```text
1. Objective

2. Customer Requirement Audit

3. Completed Workflow Audit

4. Missing Customer Requirements

5. Requirement Classification

6. Module-by-Module Status

7. Order Lifecycle Status

8. Inventory Status

9. Purchase Status

10. Sales Status

11. Delivery Status

12. Invoice Status

13. Payment Status

14. Customer Status

15. Supplier Status

16. Publisher Status

17. Book Status

18. User/Role Status

19. Reporting Status

20. API Status

21. Frontend Status

22. Database Status

23. Security/Authorization Status

24. Test Results

25. Lint Result

26. Build Result

27. Migration Result

28. Customer Clarifications

29. Remaining Required Work

30. Recommended Next Phase

31. Known Limitations
```

---

# 48. FINAL DECISION TABLE

The report must end with:

| Area                      | Decision                                                  |
| ------------------------- | --------------------------------------------------------- |
| Customer Return           | Not specified                                             |
| Physical RTO Return       | Not specified                                             |
| Refund                    | Not specified                                             |
| Exchange                  | Not specified                                             |
| Replacement               | Not specified                                             |
| Current Order Lifecycle   | Keep unchanged                                            |
| RTO                       | Keep status-only                                          |
| Inventory                 | Keep unchanged unless customer requirement says otherwise |
| Invoice                   | Keep unchanged unless required                            |
| Payment                   | Keep unchanged unless required                            |
| Next Implementation Phase | Determined from customer requirement audit                |

---

# 49. STEP 13 ACCEPTANCE CRITERIA

STEP 13 is complete when:

- [ ] Complete customer requirement has been audited.
- [ ] STEP 01–STEP 12 reports have been reviewed.
- [ ] Current codebase has been inspected.
- [ ] Every customer-required module has been classified.
- [ ] Missing required features are identified.
- [ ] Optional features are separated.
- [ ] Unspecified features are not implemented.
- [ ] RTO remains status-only.
- [ ] No Return module was invented.
- [ ] Inventory architecture remains intact.
- [ ] Reservation architecture remains intact.
- [ ] FIFO allocator remains unchanged.
- [ ] Invoice architecture remains intact.
- [ ] Payment architecture remains intact.
- [ ] Existing authorization remains intact.
- [ ] Backend tests pass.
- [ ] Frontend lint result is documented.
- [ ] Frontend build succeeds.
- [ ] Migration status is verified.
- [ ] Customer clarification requirements are documented.
- [ ] Remaining required work is prioritized.
- [ ] A specific next phase is recommended.
- [ ] STEP 13 implementation report is created.

---

# 50. FINAL INSTRUCTION TO OPENCODE

Do NOT begin by writing code.

First perform the complete requirement audit.

The order must be:

```text
1. Read customer requirement
        ↓
2. Read STEP 01–STEP 12 reports
        ↓
3. Inspect current codebase
        ↓
4. Build requirement matrix
        ↓
5. Build module completion matrix
        ↓
6. Identify missing customer-required functionality
        ↓
7. Identify unresolved business rules
        ↓
8. Determine whether implementation is actually required
        ↓
9. Implement ONLY confirmed required functionality
        ↓
10. Run regression tests
        ↓
11. Run frontend lint/build
        ↓
12. Verify migrations
        ↓
13. Create STEP 13 report
        ↓
14. STOP
```

### MOST IMPORTANT RULE

Do not implement functionality merely because it is common in a bookstore ERP.

The customer requirement is the source of truth.

If the audit determines that a requirement is missing or ambiguous:

```text
DOCUMENT IT.
DO NOT GUESS.
```

If all currently specified requirements are complete:

```text
STOP.
REPORT COMPLETION.
```

Do not automatically create STEP 14.
