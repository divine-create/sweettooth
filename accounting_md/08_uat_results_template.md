# 08. UAT Results Template

Run date:
Environment:
DB:
App version/commit:
Tester:

## Pre-Checks

- [ ] MySQL confirmed.
- [ ] All migrations applied.
- [ ] GL accounts seeded.
- [ ] Accounting period open.
- [ ] Department GL mappings set (Revenue, AR, Cash/Bank, Tax if used).

## Flow A: Invoice Sale -> Payment

- [ ] Sale created as `pending`.
- [ ] Sale item added with unit cost.
- [ ] Sale marked `completed`.
- [ ] AR debit = total.
- [ ] Revenue credit = subtotal.
- [ ] COGS debit = sum of item cost.
- [ ] Inventory credit = sum of item cost.
- [ ] Payment created as `completed`.
- [ ] Cash/Bank debit = payment amount.
- [ ] AR credit = payment amount.

Notes:

## Flow B: Cash Sale

- [ ] Sale created as `completed`.
- [ ] Payment created immediately.
- [ ] No duplicate GL entries.

Notes:

## Flow C: Purchase -> Approval

- [ ] Purchase created as `draft`.
- [ ] Purchase updated to `approved`.
- [ ] Inventory debit = landing cost.
- [ ] AP credit = landing cost.

Notes:

## Flow D: Stock Adjustment (Damage)

- [ ] Stock movement created with `type=damaged` and `adjustment_reason=damage`.
- [ ] Damage Loss debit recorded.
- [ ] Inventory credit recorded.

Notes:

## Flow E: Retry Posting

- [ ] One transaction marked `failed`.
- [ ] Retry from Transactions page.
- [ ] Status becomes `posted`.
- [ ] No duplicate GL entries.

Notes:

## Validation Checks

- [ ] Each transaction has GL entries with correct `reference_type` + `reference_id`.
- [ ] Trial Balance is balanced (total debit = total credit).
- [ ] No duplicate entries.

## Issues Found

1.
2.
3.

## Exit Criteria

- [ ] All flows posted without errors.
- [ ] No duplicate entries found.
- [ ] Trial Balance is balanced.
- [ ] Posting failure queue is empty or understood.
