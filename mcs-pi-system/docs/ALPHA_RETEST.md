# Alpha fixes and retesting

Sources: v4 MCS P&I System Specification (18 August 2026) and CS321 Alpha User Testing Report (4 October 2026).

## BUG01: deployment schema

`stock_revision` is not a Specification v4 field and is removed by a forward migration. Receipt reversals remain guarded by valuation snapshots and subsequent movement checks. Migration preserves stock quantities, costs and transaction records.

After backing up the existing database, deploy the branches in their PR order and run:

```sh
composer install --no-interaction --prefer-dist
php artisan migrate --force
php artisan optimize:clear
php artisan mcs:check-schema
```

Do not use `migrate:fresh` or reseed the alpha database. Review the affected GE 1002, Item 1009/Branch 201, receipt records and balance before retrying. Automated tests verify rollback on rejected receipts; the actual alpha database must still be inspected for any historical partial records.

## Required acceptance retest

- AT01: valid login, logout, invalid credentials and direct forbidden edit URL for EndUser.
- AT02: use the actual stock/cost baseline, save Unit Cost, receive 4 units at PGK 5, verify the weighted cost and manual status, then exercise NON-STOCK receipt and confirm no stock changes.
- AT03: establish opening stock through Adjustment IN or a receipt, issue 2 units, cancel and verify exact restoration; reject an issue above availability.
- AT04: open all four reports in Edge and Opera and at 390 by 844. Confirm readable headings, horizontal table scrolling and accessible sign out.
- AT05: collect three dashboard Lighthouse runs with extensions disabled and identical settings; report medians. The earlier 72 score and 630 ms blocking time are not an uncontaminated baseline.

Automated checks do not establish the user's browser results, server capacity, tester identities or beta approval. Record the real retest evidence and review decision separately.
