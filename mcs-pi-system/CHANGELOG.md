# Alpha follow-up — 6 October 2026

- BUG01: forward stock_revision repair and read-only inventory schema check; existing database upgrade instructions without resetting data.
- BUG02: readable stock, reorder, purchase and issue tables with specification headings and correct purchase grouping.
- BUG03: persistent sign out at every viewport width; narrow form layout, keyboard table access and defined theme colours.
- Explicit specification identifiers and missing transaction/asset columns; logical/storage column mapping in docs/SPECIFICATION_COLUMNS.md.
- Supported Adjustment IN → issue → cancellation retest covering the alpha zero-stock precondition.
- Enter Date Received when recording a receipt; select only lines with outstanding quantity.
- Assign pre-allocated PO Number when setting Ordered; retain default GE Number, enforce one supplier per PO and protect already receipted PO identifiers.

Merge order: PR #1 (original specification fixes), #2 (schema), #3 (reports), #4 (interface/columns), #5 (purchasing completion). Retarget each dependent PR to main once its predecessor is merged. The final branch includes the complete stack.

Existing database and actual Edge/Opera/Lighthouse acceptance still require the steps in docs/ALPHA_RETEST.md. CI results are recorded in each PR.

# Change history

## 2026-10-07 — Remove non-specification revision field

- Remove `stock_revision` and its receipt reference with a forward migration; preserve receipt reversal safeguards using valuation snapshots and later-movement checks.

## 2026-10-04 — Specification review and regression fixes

- Repair fresh user/branch migration ordering and add missing string-ID database sessions.
- Scope ItemBranch Eloquent writes and reads by both primary-key columns.
- Align Purchasing Officer inventory permissions and Receipt action visibility with authorization.
- Preserve manual GE statuses; validate approval, completion, cancellation and multi-line headers.
- Reject zero/over-receipts under an order-line lock and return missing-location validation errors.
- Reverse Issue/Adjustment cancellations exactly under locks, rejecting invalid reversals.
- Guard Item/ItemBranch deletion and roll back failed deletion without erasing stock.
- Enforce asset category and matching ItemBranch, derive UOM and protect system-managed stock.
- Add receipt editing and guarded cancellation with valuation snapshots and stock revision tracking.
- Add protected Pending GE editing, stock-safe Issue/Adjustment editing and branch selectors.
- Serialize PostgreSQL ID allocation through insertion and recheck requested numbers under locks.
- Return clear validation for referenced-user deletion and align Item Branch navigation labels.
- Add specification regression tests and SQLite/PostgreSQL GitHub Actions checks.
- Update both READMEs and add `docs/SPECIFICATION_REVIEW.md` with remaining requirements.

Changed source areas: `app/Models/{ItemBranch,User}.php`, purchasing/inventory controllers,
`routes/web.php`, Item/ItemBranch/Receipt views, baseline users migration, forward sessions migration,
`tests/Feature/`, repository workflow and documentation. No live database reset is required.
