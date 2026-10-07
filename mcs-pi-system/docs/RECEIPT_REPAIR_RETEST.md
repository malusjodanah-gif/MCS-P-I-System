# Purchasing Officer receipt repair

A PostgreSQL 42703 error for receipts.stock_before means code and database schema differ. This forward migration adds any missing valuation snapshot fields without resetting stock or receipts. Existing snapshots are preserved; absent historical snapshots remain null.

Back up the database. From the application directory run:

```sh
php artisan migrate
php artisan optimize:clear
php artisan mcs:check-schema
```

Do not use migrate:fresh or reseed the existing database. If an earlier migration fails, stop and capture its error rather than marking it as run manually.

Retest the reported transaction: baseline stock 10 at PGK 5, receipt of 1 at PGK 6. Expected stock 11 and weighted Unit Cost 5.0909. Inspect stock and receipt history before retrying; receipt and stock changes are wrapped in one database transaction, and a regression test checks rollback on the missing-column failure. GE status remains manual.

Record the actual outcome before continuing to partial/full receipt and cancellation tests. This branch is based on Polish and should not be mixed with older receipt implementations.
