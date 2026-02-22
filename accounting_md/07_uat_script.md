# 07. UAT Script (Staging, MySQL)

Purpose: validate end-to-end accounting integration using real workflows and the simplified accounting pages.

## Pre-Checks

1. Confirm environment uses MySQL and latest migrations are applied.
2. Seed GL accounts:
   - `php artisan seed:gl-accounts`
3. Ensure an open accounting period exists for the current month.
4. Confirm one Sales department is mapped to GL accounts:
   - Revenue -> `4010`
   - Receivable -> `1100`
   - Cash/Bank -> `1010` or `1050`
   - Tax -> `2020` (if used)

## Flow A: Invoice Sale -> Payment

1. Create a sale (invoice) with status `pending`.
2. Add at least one sale item with unit price and unit cost.
3. Mark sale as `completed`.
4. Expected GL:
   - AR debit = sale total
   - Revenue credit = sale subtotal
   - COGS debit = sum of sale item cost
   - Inventory credit = sum of sale item cost
5. Create a payment for the sale (status `completed`).
6. Expected GL:
   - Cash/Bank debit = payment amount
   - AR credit = payment amount

## Flow B: Cash Sale

1. Create a sale with status `completed` and a payment created immediately.
2. Verify sale and payment both post once (no duplicates).

## Flow C: Purchase -> Approval

1. Create a purchase in `draft`.
2. Update status to `approved`.
3. Expected GL:
   - Inventory debit = landing cost
   - AP credit = landing cost

## Flow D: Stock Adjustment (Damage)

1. Create a stock movement with:
   - `type = damaged`
   - `adjustment_reason = damage`
   - `unit_cost` and `quantity`
2. Expected GL:
   - Damage Loss debit
   - Inventory credit

## Flow E: Retry Posting

1. Manually set one transaction `gl_posting_status = failed`.
2. Use the simple `Transactions` page to retry.
3. Expected: entries post once and status becomes `posted`.

## Validation Checks

1. For each source transaction, confirm:
   - GL entries exist with matching `reference_type` + `reference_id`.
2. Trial Balance totals:
   - Total debit equals total credit.
3. Verify no duplicate GL entries for any source transaction.

## Exit Criteria

1. All flows posted without errors.
2. No duplicate entries found.
3. Trial Balance is balanced.
4. Posting failure queue is empty or understood.
