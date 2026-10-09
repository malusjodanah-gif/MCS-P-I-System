# Additional user requirements — 9 October 2026

These changes build on the tested fix/purchasing-orders branch. No database schema change is needed.

| Modification branch | Retest |
| --- | --- |
| feature/vertical-financial-forms | Open each creation page as an allowed writer; fields appear one below another. Verify readable financial figures and narrow-screen layouts. |
| feature/multi-item-ge-orders | One shared branch/supplier/date header, 10 optional lines, +10 expansion. Enter 2 × 6 and 3 × 4: live total and saved total are 24. Blank lines are ignored; any incomplete entered line rejects the entire submission. Try STOCK and NON-STOCK and Pending editing. |
| feature/receipt-quantity-guidance | Select a 6-unit order with 1 received: note shows ordered 6, received 1, outstanding 5. Type 2: after receipt shows 3. Type 6: overreceipt feedback prevents submission. Switch lines and confirm the note changes. |
| feature/inventory-search-and-role-views | As each of the four roles, search ItemBranch by item name/ID/location; combine branch/category/stock filters and reset. Browse other lists/reports with text search and column filters. EndUser sees no Action columns; writers retain allowed controls. |

The GE screen uses one HTML form and optional expandable line sections. It supports up to 100 submitted lines to stay within normal PHP request input limits; add in blocks of ten. Currency labels use the selected supplier's currency. Amounts shown live are estimates until server validation recalculates the persisted line totals. Inventory is not changed by creating GE requests.

ItemBranch filters run on the server. Other existing full-list tables filter their loaded rows in the browser. Matching GE lines display the complete GE group and its original total to preserve context. Existing role enforcement continues to control access.

Merge/review order follows the table from top to bottom, after the preceding receipt/order fixes. Actual browser acceptance of these new requirements remains to be recorded by the user.
