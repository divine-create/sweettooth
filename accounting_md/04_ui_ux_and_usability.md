# 04. UI/UX Plan For Easy Daily Use

This document focuses on making accounting understandable for non-accountants.

## Design Rule

Users should not need accounting knowledge to do daily operations.

## Sales Screens

Provide two entry modes:

1. `Cash Sale`
   1. one form
   2. creates invoice + receipt in one action
2. `Invoice Sale`
   1. creates invoice only
   2. shows outstanding balance
   3. receipt can be added later

Required user-facing statuses:

1. Draft
2. Issued
3. Partially Paid
4. Paid
5. Cancelled

## Purchase Screens

Provide two entry modes:

1. `Cash Purchase`
   1. bill + payment in one action
2. `Bill Purchase`
   1. bill now
   2. payment later

Required statuses:

1. Draft
2. Approved
3. Partially Paid
4. Paid
5. Voided

## Inventory Screens

Separate operational and accounting actions:

1. Logistics:
   1. goods receipts
   2. delivery notes
   3. transfers
2. Financial adjustments:
   1. write-off
   2. damage
   3. shrinkage
   4. count variance

Only financial adjustments should post to GL.

## Accounting Visibility

Add one page: `Accounting Health`.

Cards:

1. Pending postings
2. Failed postings
3. Unbalanced periods
4. AR aging total
5. AP aging total

Table columns:

1. transaction type
2. reference number
3. amount
4. status
5. error
6. retry action

## Form Defaults

To reduce complexity:

1. auto-select branch default bank account
2. auto-select department GL mapping
3. hide manual GL account selector for normal roles
4. show plain-language summary before submit:
   1. "This will record revenue and receivable."
   2. "This will reduce payable and move cash."

## Permissions

Minimum role split:

1. Maker: create invoices/bills
2. Approver: approve purchases and adjustments
3. Accountant: post/reverse/manual journals
4. Auditor: read-only and export

