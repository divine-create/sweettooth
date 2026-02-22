# 02. MySQL Schema Alignment Plan

This document lists schema mismatches currently blocking reliable accounting posting.

## Confirmed Mismatches

1. Stock movement type mismatch:
   1. observer checks `damage/shrinkage` in `app/Observers/StockMovementObserver.php`
   2. table enum is `in/out/adjustment/transfer/damaged/return` in `database/migrations/2025_10_09_012617_create_stock_movements_table.php`
2. Payment datetime mismatch:
   1. service uses `payment_date` in `app/Services/GlPostingService.php`
   2. table/model use `payment_time` in `database/migrations/2025_10_20_063429_create_payments_table.php` and `app/Models/Payment.php`
3. Movement field mismatch:
   1. service uses `movement_type` in `app/Services/GlPostingService.php`
   2. table column is `type` in `stock_movements`
4. COGS source mismatch:
   1. service uses `sale_items.average_cost`
   2. `sale_items` table has no `average_cost` column
5. Department GL mapping mismatch:
   1. model expects `revenue_account_id`, `tax_account_id`, `receivable_account_id`, `cash_account_id` in `app/Models/Department.php`
   2. no matching columns in department migrations

## Required New Migrations

Create migrations that are safe for MySQL and existing data.

## Migration A: Add Department GL Mapping Columns

Filename suggestion:
`database/migrations/2026_02_14_100000_add_gl_account_columns_to_departments.php`

Columns:

1. `revenue_account_id` unsignedBigInteger nullable FK -> `gl_accounts.id`
2. `tax_account_id` unsignedBigInteger nullable FK -> `gl_accounts.id`
3. `receivable_account_id` unsignedBigInteger nullable FK -> `gl_accounts.id`
4. `cash_account_id` unsignedBigInteger nullable FK -> `gl_accounts.id`

Indexes:

1. index each FK column

## Migration B: Normalize Stock Movement Type Handling

Filename suggestion:
`database/migrations/2026_02_14_100100_normalize_stock_movement_types.php`

Choose one convention and apply everywhere:

1. preferred values: `damaged` and `adjustment` with explicit reason code
2. avoid introducing `damage/shrinkage` if enum already live in production

Implementation approach:

1. keep `type` as broad category
2. add `adjustment_reason` enum/string nullable (`damage`, `shrinkage`, `write_off`, `count_variance`)

## Migration C: Add COGS Unit Cost On Sale Items

Filename suggestion:
`database/migrations/2026_02_14_100200_add_cost_fields_to_sale_items.php`

Columns:

1. `unit_cost` decimal(10,2) nullable
2. `line_cost` decimal(10,2) nullable

Rationale:

1. avoids dependency on missing `average_cost`
2. freezes cost at sale time for audit and reporting

## Migration D: Ensure Purchase Payment Linking

If supplier payments are still mixed with customer payments, add separate table:

1. `supplier_payments` with `purchase_id` or `supplier_id`
2. `payment_time`, `bank_account_id`, `amount`, `status`, `gl_posting_status`

This prevents AP logic from colliding with customer receipt logic.

## Data Backfill Rules

1. Backfill `departments.*_account_id` with default GL account IDs.
2. Backfill `sale_items.unit_cost/line_cost` from stock cost snapshots where possible.
3. Backfill stock adjustment reason from historical movement notes where detectable.

## Do Not Do

1. Do not switch to sqlite assumptions.
2. Do not rewrite old migrations in place.
3. Do not drop columns before code is fully switched.

