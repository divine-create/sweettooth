# 03. Posting Engine And Observer Implementation

This is the core engineering fix: one posting engine, correct triggers, idempotent behavior.

## Step 1: Pick One Posting Service

Current conflict:

1. `app/Services/GlPostingService.php`
2. `app/Services/AccountingService.php`

Action:

1. Keep one service as the source of truth.
2. Move needed features from the other service.
3. Remove duplicate account-number logic.

Recommendation:

1. Keep `GlPostingService` as core engine.
2. Move department fallback helpers from `AccountingService` into `GlPostingService`.

## Step 2: Fix Observer Trigger Timing

## Problem A: Sales in POS

Flow in `app/Livewire/BranchDashboard/SalesDashboard/Pos/Index.php`:

1. sale created as `completed`
2. payment created after

Observer issue:

1. `SaleObserver` posts only if fully paid at create/update status change.
2. payment is not yet present during sale create.

Fix:

1. Add `created()` handling in `PaymentObserver`.
2. On `PaymentObserver::created`, if payment is completed and sale is completed, trigger sale posting if pending.

## Problem B: Purchases created directly approved

Flow in `app/Livewire/BranchDashboard/Inventory/Purchases.php`:

1. purchase may be created directly with `status = approved`

Observer issue:

1. `PurchaseObserver` only has `updated()`.

Fix:

1. Add `created()` to `PurchaseObserver`.
2. Trigger posting when created as approved and pending.

## Problem C: Stock adjustment trigger mismatch

Observer checks `damage/shrinkage` but table uses different values.

Fix:

1. align on `type` + `adjustment_reason` logic.
2. update `StockMovementObserver` and `GlPostingService::postInventoryAdjustment()`.

## Step 3: Fix Field Name Mismatches

In `GlPostingService`:

1. replace `payment_date` with `payment_time`
2. replace `movement_type` with `type` (or new reason column if added)
3. replace COGS from `average_cost` to new `unit_cost/line_cost` fields

## Step 4: Idempotency Guard

For each post method:

1. check `gl_posting_status === pending`
2. lock transaction row in DB transaction
3. post entries
4. update status to posted atomically

Optional hard guard:

1. unique key on (`reference_type`, `reference_id`, `entry_type`, `gl_account_id`, `debit`, `credit`)

## Step 5: Retry Must Actually Re-Post

Current `PostingStatusMonitor` only sets status back to pending.

Fix in `app/Livewire/BranchDashboard/Accounting/PostingStatusMonitor.php`:

1. call dedicated retry service method after resetting pending
2. surface exact error message and retry result

## Step 6: Command Hygiene

`app/Console/Commands/BackfillGlEntries.php` references `accounting:create-period` which does not exist.

Fix:

1. point users to existing period creation command, or create it.
2. align adjustment query with real stock movement values.

## Step 7: Seeding Fix

`app/Console/Commands/SeedGlAccounts.php` references missing `GlAccountSeeder`.

Fix:

1. create `database/seeders/GlAccountSeeder.php`
2. register in `database/seeders/DatabaseSeeder.php`
3. keep account numbering consistent with posting engine.

