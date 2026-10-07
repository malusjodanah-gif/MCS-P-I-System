# Guided v4 retest — 7 October 2026

The following outcomes were reported by the user during guided local retesting, rather than independently observed browser execution by the assistant.

- Purchasing Officer: STOCK/NON-STOCK receipts, weighted cost, overreceipt rejection, receipt edits/cancellation and manual GE statuses passed.
- Purchasing: Pending editing, approval protection, Backorder completion, multi-line header consistency, whole-GE changes, custom/default PO assignment, supplier ownership and rejection/cancellation/deletion passed.
- Purchasing Officer and Inventory Officer: issue/adjustment creation, editing, cancellation and insufficient-stock rejection passed; IO purchasing write URLs returned 403.
- EndUser: viewing, report tables, forbidden write/admin URLs and logout passed.
- Administrator: users and role changes, branches, suppliers, customers, items/ItemBranch and assets CRUD/duplicate checks passed. Default Branch 201 had no Delete button; direct server deletion was not manually attempted.
- Inventory: reorder equality, deletion guards, branch isolation, used-receipt reversal blocking, metadata editing, referenced-customer deletion, used-IN cancellation blocking and issue changes between items passed.
- Narrow-screen navigation, report scrolling, stock value and logout passed.

Confirmed final Alpha Test Pens balance: 16 units at Unit Cost 5.6250, Stock Value 90.00.

## Remaining form retest on fix/purchasing-orders

1. Log in as Purchasing Officer; open New GE Order.
2. STOCK: item selector is visible and required. Select Alpha Test Pens; description and UOM populate and cannot be manually changed.
3. NON-STOCK: item selector and UOM are hidden; description is editable and required.
4. Enter a service description, switch to STOCK and back: service text should be retained.
5. Save a disposable NON-STOCK GE; verify Item ID is blank and inventory unchanged.
6. Edit that Pending NON-STOCK GE and verify the same field behaviour.
7. Save and edit a disposable STOCK GE; verify Item ID, catalogue description/UOM and totals.
8. Delete the disposable Pending orders.

Future acceptance still includes repeated clean Lighthouse measurements and concurrent multi-user execution. Passing these guided checks is not a claim that every possible system scenario has been tested.
