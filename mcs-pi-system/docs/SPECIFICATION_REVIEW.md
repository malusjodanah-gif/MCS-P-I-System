# Specification and implementation review — 4 October 2026

Baseline: GitHub commit `ae73bb80a3709ed5971e81dd85e8001f5086255e`.
The repository now contains the application; the earlier README-only observation is obsolete.

Sources read: repository `Updated Specification.pdf` (v4, revised 18 August 2026);
Capturing Requirements Report (21 September), System Implementation and Progress Report
(20 September), and Design and Software Architecture Report (22 September).
The repository PDF retains the same governing business rules as the v4 report copy.

## Corrections in this review

| Area | Baseline inconsistency | Correction and evidence to run |
| --- | --- | --- |
| Fresh installation | Initial users migration referenced branches before their creation | Restore a baseline users migration; custom users are created after branches. Fresh migrations and seeding are exercised by regression tests. |
| Login | Database sessions selected, but no migration created sessions | Add an idempotent forward migration with string user IDs; add real login/logout test. |
| Item Branch | Composite PK had no Eloquent write scoping | Scope save, fresh/refresh and delete by both ItemID and BranchID. Test other items and branches remain untouched. |
| Roles | PO denied issue/adjustment writes and inventory buttons | Align middleware and helper with v4 full PO inventory access; test PO and EndUser writes. |
| Receipt navigation | Inventory Officer shown an Add Receipt button leading to 403 | Display purchasing write actions only to purchasing roles. |
| Manual status | Receipts automatically changed status | Receipt processing updates stock and costs only; status remains manual. |
| Receipt quantity | Zero and unlimited accumulated quantities accepted | Require positive quantities, serialize per-line receipts and reject quantities exceeding outstanding units. |
| Missing stock location | Receipt could fail with an uncaught runtime error | Report a validation error and roll back the receipt. |
| Approval | Any allowed status could jump directly from Pending to Ordered/Complete | Validate manual transitions and receipt-dependent statuses atomically; assign new PO number only when Ordered. |
| Multi-line GE | Same GE could combine suppliers/flags/branches or append Pending lines after approval | Require consistent headers and Pending lines; PostgreSQL advisory lock serializes additions to the same pre-allocated GE. |
| Adjustment cancellation | Cancelling IN used `max(0, balance - quantity)`, silently losing the reversal | Reject insufficient balance without deleting the adjustment; reverse the exact quantity otherwise. |
| Repeated cancellation | Route-bound stale records could restore stock twice under concurrent deletion | Re-fetch and lock Issue/Adjustment inside the transaction; fail when already removed. |
| Item deletion | Item Branch balances were deleted before an item deletion could fail | Guard operational references and stock, and wrap deletion in a transaction. |
| Item Branch deletion | Stock-holding or referenced locations could be removed | Reject deletion when stock or related operational records exist. |
| Asset validation | Assets accepted no item, consumables, or items absent from the chosen branch | Require an ASSET item with the matching Item Branch. |
| Stock metadata | UOM and initial stock could be forged through forms | Copy UOM from Item; start new Item Branch records at zero and use movements for stock. |
| Documentation | README said Laravel 11/12 and PHP 8.2 | Match composer.json and composer.lock: Laravel 13 and PHP 8.3+. |

## What remains incomplete

These are source-confirmed gaps, not completed features:

1. GE line editing is absent: there is creation, status update and deletion, but no edit/update form.
2. Receipt editing, cancellation and deletion are absent. Reversing receipts must handle both
   quantity and weighted-average valuation, including later movements; a simple subtraction
   is insufficient. Legacy receipts have no stored valuation snapshot or inventory ledger.
3. Issue and Adjustment editing is absent; cancellation currently deletes the transaction.
   A durable cancellation/audit history would improve traceability but is an additional design choice.
4. Forms for Issues and Adjustments do not select a branch; normal UI transactions default to 201.
   The backend validates a supplied branch, but broader branch operation needs UI work.
5. Asset registration validates stock membership but does not implement an explicit stock-to-asset
   reclassification movement. Agree and test that accounting behavior before calling it complete.
6. GE header/line and receipt ID schemas use surrogate IDs instead of the specification's stated
   GE/PO primary keys. This supports many lines and partial receipts, but must be explained in
   the ERD and reports. A normalized header/line model remains a useful follow-up.
7. ID allocation uses `max + 1`. Concurrent new transactions can collide on IDs; database uniqueness
   protects records, but friendly retry/sequence handling has not been implemented.
8. Branch/user administration is Administrator-only as the reports describe. The v4 prose gives
   broad staff access to tables, so the reports should explicitly retain this narrower policy.
9. Existing records with mixed GE headers, STOCK orders with null items, or legacy asset links need
   data review. This change does not rewrite operational records or reseed existing databases.

## Report corrections to make before submission

Treat the reports as dated development records. Do not change their original submission dates or
replace historical progress with unverified current claims. Add a dated verification addendum:

- Distinguish Create/View/Cancel/Delete from full CRUD where Edit is absent.
- State that GE status is manual even after a receipt; Purchasing manually chooses Received,
  Backorder or Complete when the receipt totals permit it.
- Document PO inventory access as full access, and restrict user/branch administration explicitly.
- Explain composite Item Branch writes and atomic transaction boundaries, rather than assuming
  that declaring a database composite key is enough for Eloquent.
- Show reports as application outputs returned to the browser, not a downstream database server.
  The architecture report's client/server and TPS explanation remains appropriate; its table of
  contents also needs regeneration because the References page number exceeds its actual pages.
- Include the remaining receipt and asset workflow gaps, updated ERD, test counts and actual test
  results. The existing reports correctly say further testing is required.

## Verification status

Local source review completed; JSON and workflow YAML parse checks and `git diff --check` passed.
PHP/Composer are not available in this execution environment, and the PHP installer host was
unreachable. Local Laravel/PHPUnit execution has therefore not occurred.
GitHub Actions configuration runs syntax checks, routes, Blade compilation and the full suite on
SQLite and PostgreSQL 16 with PHP 8.5. Check the PR's actual runs before claiming tests passed.
Passing these regression tests will not replace user acceptance checks for the open requirements.

## Demonstration and acceptance checklist

Use an isolated demonstration/test database, never reset the operational database.

1. Migrate and seed a new database. Confirm markers (1005/201) = 28 and detergent (1006/201) = 15.
2. Log in as each role, then log out; confirm EndUser cannot submit changes.
3. As PO create two Pending GE lines with the same supplier/flag/branch/account code.
   Try changing the supplier under that GE and verify rejection.
4. Try Pending → Ordered; verify rejection. Manually set Approved, then Ordered.
5. Record a partial STOCK receipt; confirm stock increase and weighted cost, and unchanged status.
   Set Received then Backorder manually. Receive the remaining quantity and set Complete manually.
6. Try receiving beyond the outstanding quantity; confirm no receipt or stock change.
7. Receive a NON-STOCK service; confirm inventory quantities and costs are unchanged.
8. Issue units; cancel the issue once and confirm stock restoration. Try an oversized issue.
9. Record IN and OUT adjustments; reverse them when possible. Try cancelling IN after its units
   have been consumed and confirm both balance and adjustment are preserved.
10. Edit Item Branch location/reorder/cost; confirm stock and other branches remain unchanged.
11. Register an ASSET item at a valid Item Branch; reject a consumable or missing branch relation.
12. Check stock, purchases, issues and reorder reports against database quantities, including stock
    exactly equal to the reorder level.
13. Complete and separately test the missing edit, receipt reversal and asset reclassification
    requirements before final handover.
