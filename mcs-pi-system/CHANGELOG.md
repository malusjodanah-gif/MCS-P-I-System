# Change history

## 2026-10-04 — Specification review and regression fixes

- Repair fresh user/branch migration ordering and add missing string-ID database sessions.
- Scope ItemBranch Eloquent writes and reads by both primary-key columns.
- Align Purchasing Officer inventory permissions and Receipt action visibility with authorization.
- Preserve manual GE statuses; validate approval, completion, cancellation and multi-line headers.
- Reject zero/over-receipts under an order-line lock and return missing-location validation errors.
- Reverse Issue/Adjustment cancellations exactly under locks, rejecting invalid reversals.
- Guard Item/ItemBranch deletion and roll back failed deletion without erasing stock.
- Enforce asset category and matching ItemBranch, derive UOM and protect system-managed stock.
- Add specification regression tests and SQLite/PostgreSQL GitHub Actions checks.
- Update both READMEs and add `docs/SPECIFICATION_REVIEW.md` with remaining requirements.

Changed source areas: `app/Models/{ItemBranch,User}.php`, purchasing/inventory controllers,
`routes/web.php`, Item/ItemBranch/Receipt views, baseline users migration, forward sessions migration,
`tests/Feature/`, repository workflow and documentation. No live database reset is required.
