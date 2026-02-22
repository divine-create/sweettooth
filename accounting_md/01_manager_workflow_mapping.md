# 01. Manager Workflow Mapping To Your System

This maps Manager-style accounting behavior to your existing tables.

## Core Principle

Split operational events from accounting settlement events:

1. Invoice events create AR/AP or revenue/expense.
2. Settlement events move cash/bank and clear AR/AP.
3. Logistics events track stock movement and fulfillment, not cash.

## Sales Side

### Workflow

1. Quote: `sales_quotes` + `sales_quote_items`
2. Order: `sales_orders` + `sales_order_items`
3. Invoice (financial): `sales` + `sale_items`
4. Receipt (financial settlement): `payments`
5. Delivery note (logistics): `delivery_notes` + `delivery_note_items`
6. Credit note (financial reversal): `credit_notes` + `credit_note_items`

### Posting Rules

1. On invoice issue (`sales.status = completed` with invoice meaning):
   1. Debit AR (or Cash for immediate cash sale)
   2. Credit Revenue
2. On receipt (`payments.status = completed`):
   1. Debit Cash/Bank
   2. Credit AR
3. On credit note issue:
   1. Debit Sales Returns / Contra Revenue
   2. Credit AR or Cash (based on application mode)

## Purchase Side

### Workflow

1. RFQ: `purchase_quotes` + `purchase_quote_items`
2. PO: `purchase_orders` + `purchase_order_items`
3. Bill/Invoice (financial): `purchases` + `purchase_items`
4. Supplier payment (financial settlement): payment record for purchase liabilities
5. Goods receipt (logistics): `goods_receipts` + `goods_receipt_items`
6. Debit note (financial reversal): `debit_notes` + `debit_note_items`

### Posting Rules

1. On purchase bill approval:
   1. Debit Inventory or Expense
   2. Credit AP
2. On supplier payment:
   1. Debit AP
   2. Credit Cash/Bank
3. On debit note issue:
   1. Debit AP
   2. Credit Inventory/Expense reversal

## Inventory Side

### Non-Posting Events

1. `delivery_notes` (dispatch proof)
2. `goods_receipts` (receiving proof)
3. regular transfers unless value adjustment is required

### Posting Events

1. write-off/damage/shrinkage adjustments
2. production completion and component consumption with value movement

## Manager-Style Simplicity For Users

Use two top-level action sets:

1. Sales:
   1. `Cash Sale` (invoice + receipt in one flow)
   2. `Invoice Sale` (invoice now, receipt later)
2. Purchases:
   1. `Cash Purchase` (bill + payment in one flow)
   2. `Bill Purchase` (bill now, payment later)

This keeps accounting correct while reducing user confusion.

