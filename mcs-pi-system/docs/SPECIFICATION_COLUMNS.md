# Specification v4 column alignment

The visible table/form headings now use the specification's terms. Storage uses Laravel snake_case equivalents; renaming valid existing columns to names containing spaces would break stored data and model queries without changing meaning.

| Specification attribute | Stored attribute | Display |
| --- | --- | --- |
| GE Number / PO Number | ge_number / po_number | Explicit columns on GE Orders |
| Inventory Flag | inventory_flag | Inventory Flag |
| ItemID / BranchID / SupplierID / UserID | item_id / branch_id / supplier_id / user_id | Item ID / Branch ID / Supplier ID / User ID |
| Date (ORDERS / ISSUE / ADJUSTMENT / ASSET) | order_date / issue_date / adjustment_date / purchase_date | Date |
| Date Received | date_received | Date Received |
| Quantity Ordered / Quantity Received | quantity_ordered / quantity_received | Quantity Ordered / Quantity Received |
| User / Supplier / Customer / Item / Asset / Branch | name / supplier / customer / item / asset / branch | User / Supplier / Customer / Item / Asset / Branch |
| UnitCost / Unit Cost | unit_cost | Unit Cost |
| HoS | hos | HoS (Head of School) on entry; HoS in the list |

ITEM and ITEMBRANCH are separate specification entities. The Items screen contains only Item ID, Item, UOM, Category, Sub-Category and Supplier ID. Current Stock, Unit Cost, Location, Reorder Level and Reorder Quantity are branch-specific ITEMBRANCH attributes and are maintained on Item Branch, not on Items. New Item records therefore do not create stock/location rows implicitly.

Asset registration requires an ASSET Item ID that has an Item Branch row for the selected branch. The assets table keeps item_id nullable for compatibility with legacy records, but the application create/edit rules require it.

GE Orders show Item ID explicitly; NON-STOCK lines legitimately have no Item ID. Additional order_line_id and receipt_id keys support multiple items and partial receipts, which the business rules require. `stock_revision` is not in Specification v4 and is not part of the schema. Receipt valuation snapshots (`stock_before`, `cost_before`, `purchase_unit_cost`, `stock_after`, and `cost_after`) are internal safeguards, not user-maintained fields.

The report evidence confirms a missing internal column, rather than a need to rename the stock quantity field. Deploy the schema branch and run the preflight described in ALPHA_RETEST.md.
