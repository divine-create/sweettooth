# Accounting Integration Implementation Docs

This folder explains how to make the accounting system usable and fully linked with sales, purchases, and inventory using your current MySQL + Laravel setup.

## Document Order

1. `01_manager_workflow_mapping.md`
2. `02_mysql_schema_alignment.md`
3. `03_posting_engine_and_observers.md`
4. `04_ui_ux_and_usability.md`
5. `05_rollout_plan.md`
6. `06_test_cases_and_acceptance.md`

## Goal

Build a Manager-style accounting flow:

1. Sales and purchases create accounting obligations (AR/AP).
2. Receipts and payments settle those obligations.
3. Inventory logistics documents stay non-financial unless they represent value adjustment.
4. All postings are automatic, idempotent, and visible to users.

## Current Critical Files

1. `app/Livewire/BranchDashboard/SalesDashboard/Pos/Index.php`
2. `app/Livewire/BranchDashboard/Inventory/Purchases.php`
3. `app/Observers/SaleObserver.php`
4. `app/Observers/PaymentObserver.php`
5. `app/Observers/PurchaseObserver.php`
6. `app/Observers/StockMovementObserver.php`
7. `app/Services/GlPostingService.php`
8. `app/Services/AccountingService.php`
9. `database/migrations/2025_12_29_000001_create_manager_io_accounting_tables.php`

What Is Blocking Linkage Now

  1. Sales from POS are created as completed before payments are inserted (app/Livewire/BranchDashboard/SalesDashboard/Pos/Index.php:476, app/Livewire/BranchDashboard/
     SalesDashboard/Pos/Index.php:536), while SaleObserver only posts when sale is already fully paid at create/update time (app/Observers/SaleObserver.php:22, app/
     Observers/SaleObserver.php:34).
  2. PaymentObserver only handles updated, not created (app/Observers/PaymentObserver.php:22), so completed payments created directly do not auto-post.
  3. PurchaseObserver only handles updated (app/Observers/PurchaseObserver.php:22), but super-admin purchases are created already approved (app/Livewire/
     BranchDashboard/Inventory/Purchases.php:231).
  4. Inventory posting logic uses wrong field/value names: code checks damage/shrinkage and movement_type (app/Observers/StockMovementObserver.php:25, app/Services/
     GlPostingService.php:331), but migration defines type with damaged (database/migrations/2025_10_09_012617_create_stock_movements_table.php:18).
  5. GlPostingService uses payment_date (app/Services/GlPostingService.php:285) but your payment schema/model use payment_time (database/
     migrations/2025_10_20_063429_create_payments_table.php:21, app/Models/Payment.php:17).
  6. COGS uses sale_items.average_cost (app/Services/GlPostingService.php:111), but sale_items migration has no such column (database/
     migrations/2025_10_20_063409_create_sale_items_table.php:20).
  7. Department GL mapping exists in model (app/Models/Department.php:23) but not in department migrations (database/
     migrations/2025_10_04_053818_create_departments_table.php:14, database/migrations/2026_01_08_000002_extend_departments_table.php:14).
  8. Retry in posting monitor only flips status to pending (app/Livewire/BranchDashboard/Accounting/PostingStatusMonitor.php:157) but does not call posting service, so
     retries won’t run.
  9. GL seeding command references missing seeder (app/Console/Commands/SeedGlAccounts.php:5), so chart setup is incomplete.

  How To Make Sales, Purchases, Inventory Properly Link To Accounting

  1. Create migration fixes:
      - add missing department GL columns (revenue_account_id, receivable_account_id, cash_account_id, tax_account_id);
      - align stock movement type values (damage vs damaged) and fields (type vs movement_type);
      - add cost basis for COGS (either sale_items.average_cost or compute from stock snapshots).
  2. Keep one posting engine only (prefer GlPostingService) and remove duplicated accounting logic conflicts (4010 vs 4110, AP vs AR usage differences in app/Services/
     GlPostingService.php:71 and app/Services/AccountingService.php:328).
  3. Fix trigger points:
      - PaymentObserver: handle both created and updated when status is completed;
      - PurchaseObserver: handle created when status is approved;
      - SaleObserver: post when payment completion makes sale fully paid, not only on sale status change.
  4. Fix journal logic in service:
      - use sale_number/purchase_number, payment_time, type;
      - ensure each transaction posts balanced entries.
  5. Make retry/backfill real:
      - retry should call posting service/job directly, not just status update;
      - fix BackfillGlEntries type filters and run it after fixes.
  6. Add tests for real flow:
      - POS completeSale() end-to-end;
      - super-admin immediate purchase creation;
      - inventory adjustment/damage posting.

  If you want, I can implement this in phases, starting with the minimum working linkage path (sales + purchases + inventory adjustments posting reliably), then
  backfill old records.



• Use Manager’s workflow model as your backbone, then wire it to your existing MySQL tables.
  You already have most tables needed in database/migrations/2025_12_29_000001_create_manager_io_accounting_tables.php:25.

  1. Adopt this exact transaction logic (Manager-style):
  2. Sales Invoice creates AR + Revenue (not cash movement).
  3. Receipt settles AR and moves cash/bank.
  4. Purchase Invoice creates Expense/Inventory + AP (not cash movement).
  5. Payment settles AP and moves cash/bank.
  6. Delivery Notes and Goods Receipts are logistics only (no GL posting).
  7. Inventory Write-off and Production Order affect inventory value and GL.
  8. Map that to your current schema:
  9. Use sales as invoice header and payments as separate settlement records.
  10. Use purchases as purchase invoice header; add/confirm supplier payment flow (separate from invoice).
  11. Use delivery_notes/goods_receipts for physical movement only.
  12. Keep GL entries generated only from accounting events, not from every stock/logistics event.
  13. Fix your current linkage blockers first (critical):
  14. POS creates sale first, then payment, so sale auto-post can be missed: app/Livewire/BranchDashboard/SalesDashboard/Pos/Index.php:476, app/Livewire/
     BranchDashboard/SalesDashboard/Pos/Index.php:536, app/Observers/SaleObserver.php:22.
  15. Payments only post on updated, not created: app/Observers/PaymentObserver.php:22.
  16. Purchases also post only on updated, but some are created directly as approved: app/Livewire/BranchDashboard/Inventory/Purchases.php:231, app/Observers/
     PurchaseObserver.php:22.
  17. Field/type mismatches break posting logic: app/Services/GlPostingService.php:285 (payment_date vs payment_time), app/Services/GlPostingService.php:331
     (movement_type vs type), database/migrations/2025_10_09_012617_create_stock_movements_table.php:18 (damaged enum).
  18. Department GL mapping fields are in model but missing in department migrations: app/Models/Department.php:23, database/
     migrations/2025_10_04_053818_create_departments_table.php:14, database/migrations/2026_01_08_000002_extend_departments_table.php:14.
  19. You have two posting engines with conflicting account maps; unify to one: app/Services/GlPostingService.php:21, app/Services/AccountingService.php:14.
  20. Make it easy for users (this is the usability win):
  21. Two buttons only on sales: Cash Sale (single step) and Invoice Sale (invoice then receipt).
  22. Two buttons on purchases: Cash Purchase and Bill Purchase.
  23. Add Copy to flow between Quote -> Order -> Invoice -> Delivery and PO -> Bill -> Goods Receipt.
  24. Hide GL accounts from daily users; use department/account defaults.
  25. Add a simple status board: To Deliver, To Receive, Unpaid, Partially Paid, Failed Posting.
  26. Implementation order (fastest path):
  27. Repair observer/service mismatches and posting triggers.
  28. Standardize one posting service + one chart-of-accounts mapping.
  29. Enforce workflow states (invoice/payment/logistics separated).
  30. Build simplified forms and status dashboards.

  If you want, I can implement Phase 1 now (observer fixes + posting-service field/type fixes) directly in your codebase.

  Sources:

  - https://www2.manager.io/guides
  - https://www2.manager.io/guides/7178
  - https://www2.manager.io/guides/7189
  - https://www2.manager.io/guides/7820
  - https://www2.manager.io/guides/7821
  - https://www2.manager.io/guides/7321
  - https://www2.manager.io/guides/7325
  - https://www2.manager.io/guides/10676
  - https://www2.manager.io/guides/10704
  - https://www2.manager.io/guides/10709
  - https://www2.manager.io/guides/7767
  - https://www2.manager.io/guides/7226
  - https://www2.manager.io/guides/9309
  - https://www2.manager.io/guides/7262
  - https://www2.manager.io/faq/46013
