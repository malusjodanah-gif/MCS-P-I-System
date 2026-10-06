# MCS Purchasing and Inventory Tracking System

Laravel 13 (locked version 13.30.1), PHP 8.3+, PostgreSQL, server-rendered Blade.
Theme: Golden Green Glimmer (forest green, gold and slate). Application root: `mcs-pi-system/`.

## Current verification status

See [the specification review](docs/SPECIFICATION_REVIEW.md) for verified source findings,
changes, regression coverage and unfinished requirements. Implemented features are not a
claim of complete acceptance testing. CI runs PHP 8.5 against SQLite and PostgreSQL 16.

## Setup

From the repository root:

```sh
cd mcs-pi-system
composer install
cp .env.example .env
php artisan key:generate
```

Create an empty PostgreSQL database `mcs_pi`, then configure `DB_HOST`, `DB_PORT`,
`DB_DATABASE`, `DB_USERNAME` and `DB_PASSWORD` in `.env`.

```sh
php artisan migrate
# For a NEW demonstration database only:
php artisan db:seed
php artisan serve
```

Open http://127.0.0.1:8000. Existing installations should use `php artisan migrate`,
without resetting or reseeding existing records. `migrate:fresh` destroys database data.

Demo-only accounts:

| User ID | Password | Role |
| --- | --- | --- |
| admin | admin123 | Administrator |
| po1 | po123 | Purchasing Officer |
| io1 | io123 | Inventory Officer |
| eu1 | eu123 | EndUser |

Create individual accounts and change demonstration passwords before operational use.

## Roles and stock behavior

Administrator has full access. Purchasing Officer can write purchasing and inventory.
Inventory Officer can write inventory. EndUser can view operational information.
User and branch administration remain Administrator functions as documented in the reports.
HoS is an informational branch field, never an authorization role.

GE status is changed manually by Purchasing Officer or Administrator:
Pending → Approved/Rejected/Cancelled; Approved → Ordered; Ordered → Received;
Received → Backorder/Complete; Backorder → Received/Complete.
Order cancellation is allowed only without receipts. Complete may be manually returned to Received for a receipt correction. Receiving stock updates quantity and weighted
average cost; it does not change GE status. Complete requires full receipt, while Backorder
requires a partial receipt. A PO number is allocated when Approved progresses to Ordered.

Stock starts at zero in new Item Branch records. Use Receipts and Adjustments to record
stock. Seeded opening balances are demonstration fixtures. Current Stock and UOM cannot
be overwritten through Item Branch forms. Unit Cost remains manually maintainable.

## Tests

```sh
php artisan test
php artisan route:list --except-vendor
php artisan view:cache
```

Tests default to an isolated in-memory SQLite database. To test PostgreSQL, point the
following variables at an EMPTY dedicated test database (never your operational database):

```sh
DB_CONNECTION=pgsql DB_DATABASE=mcs_pi_test DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=postgres DB_PASSWORD=testing php artisan test
```

CI repeats the full suite on both database engines. Receipts can be edited and cancelled. STOCK quantity corrections require unchanged inventory
and a valuation snapshot; legacy or subsequently used stock is protected. Metadata corrections
remain available. Issue/Adjustment edits reconcile old and new stock in one transaction.

Regression tests cover composite stock
keys, migrations/seeding, real login with database sessions, roles, receipt quantities and
costing, manual statuses, cancellation, multi-line GE consistency and operational references.

See [CHANGELOG.md](CHANGELOG.md) for corrections and [docs/SPECIFICATION_REVIEW.md](docs/SPECIFICATION_REVIEW.md)
for remaining work and the demonstration checklist.

## Alpha follow-up deployment

Use the grouped pull requests in order (#1 → #2 → #3 → #4 → #5). After deploying the complete code, back up the database, run `php artisan migrate --force`, `php artisan optimize:clear`, and `php artisan mcs:check-schema`. Do not reset or reseed an existing alpha database.

See [alpha retest instructions](docs/ALPHA_RETEST.md) and [specification column mapping](docs/SPECIFICATION_COLUMNS.md). Custom PO Number is entered when setting an approved line to Ordered; leaving it blank retains the existing PO or defaults to GE Number. Date Received can be entered during receipt creation.
