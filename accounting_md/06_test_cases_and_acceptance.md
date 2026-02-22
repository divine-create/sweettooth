# 06. Test Cases And Acceptance Checklist

Use this checklist before declaring the accounting integration stable.

## Automated Test Cases

## Sales And Receipts

1. `completed` sale with full payment posts revenue and cash/bank once.
2. `completed` sale with partial payment posts AR + revenue, then receipt posts AR reduction.
3. sale created first and payment created second still posts correctly.
4. retry on failed sale posting succeeds without duplicate GL entries.

## Purchases And Supplier Payments

1. purchase created as `approved` posts inventory/expense + AP.
2. purchase approved by status update posts once.
3. supplier payment posts AP reduction + cash/bank reduction.

## Inventory

1. goods receipt does not create GL by default.
2. delivery note does not create GL by default.
3. write-off/damage adjustment creates correct inventory loss entry.
4. adjustment retry remains idempotent.

## Posting Integrity

1. every posting batch is balanced: total debit = total credit.
2. each source transaction has traceable GL references.
3. duplicate observer firing does not duplicate entries.

## Manual UAT Script

Run this flow end-to-end in staging:

1. Create quote -> convert to order -> create invoice.
2. Collect two-part receipt.
3. Create PO -> receive goods -> approve bill -> pay supplier.
4. Record one damaged stock adjustment.
5. Open trial balance and confirm all expected accounts moved.

Detailed checklist: `accounting_md/07_uat_script.md`.

## Acceptance Criteria

1. Sales, purchases, and inventory are linked to accounting with no manual journal hacks.
2. Non-accounting users can complete daily workflows without choosing GL accounts.
3. Failed postings can be retried safely from UI.
4. Month-end reconciliation can be completed from system reports.

## Suggested Metrics After Go-Live

1. failed posting rate < 0.5%
2. duplicate posting incidents = 0
3. manual journal corrections reduced by at least 80%
4. average transaction completion time reduced for cashier and purchase clerk roles
