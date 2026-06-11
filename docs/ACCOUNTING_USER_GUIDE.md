# SweetTooth Accounting Module - User Guide

## Introduction

The Accounting Module is your organization's system for tracking all financial transactions. It follows standard accounting practices where every transaction records both where money comes from and where it goes (debits and credits).

---

## Getting Started

### Logging In
1. Visit your organization's SweetTooth login page
2. Enter your username and password
3. If you have access, you'll see "Accounting" in your navigation menu

### Finding Your Branch
If your organization has multiple branches/ locations, make sure the correct branch is selected in the top navigation before recording transactions.

---

## Main Menu

From the Accounting menu, you can access:

| Menu Item | What It's For |
|----------|---------------|
| Dashboard | Overview of accounting activities |
| GL Accounts | View and manage chart of accounts |
| Bank Accounts | Add, edit, and manage your organization's bank accounts |
| Journal Entries | Record manual transactions |
| Periods | Manage fiscal periods |
| Reports | Financial reports |
| Inventory Valuation | Inventory accounting |
| Expenses | Record expenses |
| Payroll | Employee payroll |
| Tax Payments | Tax remittances |
| Budgets | Budget planning |

---

## Chart of Accounts

The Chart of Accounts is the list of all your organization's accounts. Think of it like a filing cabinet where every financial transaction gets sorted.

### Account Numbers

Accounts are organized by number range:

| Range | What It Contains | Examples |
|-------|-----------------|----------|
| 1000-1999 | Assets (what you own) | Cash, Bank, Inventory, Receivables |
| 2000-2999 | Liabilities (what you owe) | Payables, Tax Owed |
| 4000-4999 | Revenue (income) | Sales Revenue |
| 5000-5999 | Cost of Goods Sold | Cost of items sold |
| 6000-6999 | Expenses | Salaries, Rent, Utilities |

### Finding an Account
In any field that asks for an account, you can search by:
- Account number (e.g., "1010" for Cash)
- Account name (e.g., "Cash")

---

## Recording Transactions

### Manual Journal Entry

Use this when you need to record a transaction that's not captured by other modules (like sales or purchases).

**Step-by-Step:**

1. Go to **Journal Entries > Create**
2. Select the **Accounting Period** (usually the current month)
3. Enter the **Transaction Date**
4. Enter a **Reference Number** (or leave blank for auto-generation)
5. Enter a **Description** (what's this for?)
6. Add your entry lines:
   - Line 1: Select Debit account, enter amount in Debit column
   - Line 2: Select Credit account, enter amount in Credit column
   - **Important:** Debits must equal Credits
7. Select **Save as Draft** (to review before posting) or **Save and Post** (to immediately record)
8. Click Submit

**Example - Recording a Cash Expense:**
```
Debit:  Office Supplies (6320)    ₦5,000
Credit: Cash (1010)             ₦5,000
```

---

### Accounting Entries (Simpler Form)

For basic transactions, use the simpler form:

1. Go to **Journal Entries**
2. Click **New Entry**
3. Select **Entry Type**:
   - **Expense** - Money going out
   - **Income** - Money coming in
   - **Transfer** - Between accounts
   - **Adjustment** - Corrections
4. Enter **Date**, **Description**, **Amount**
5. Select **Debit Account** and **Credit Account**
6. Add any notes if needed
7. Click Save

---

## Bank Accounts

### Adding a Bank Account

1. Go to **Bank Accounts**
2. Click **Add New**
3. Enter:
   - Bank Name
   - Bank Code (optional)
   - Account Number
   - Account Type (Checking/Savings)
   - GL Account (the account this maps to)
   - Opening Balance
4. Set to Active
5. Save

### Bank Statement Import

Before you can reconcile, you need to bring your bank statement into the system.

1. Go to **Import Bank Statement**
2. Select the **Bank Account**
3. Upload your **CSV file** (download the sample CSV to see the expected format)
4. The system shows a **preview** of the first 20 rows
5. Click **Import** to bring all transactions into the system
6. Then go to **Bank Reconciliation** to start matching

> **Tip:** Download the sample CSV from the import page to see the exact format. Most bank statements can be exported as CSV from your internet banking portal.

### Bank Reconciliation

This matches your bank records with your GL records to ensure they agree.

**Step-by-Step:**

1. Go to **Bank Reconciliation**
2. Select the **Bank Account**
3. Enter the **Reconciliation Date** and **Bank Balance** (from your statement)
4. Click **Start Reconciliation**
5. The system shows two columns:
   - **GL Entries** — transactions from your accounting records
   - **Bank Transactions** — transactions you imported
6. Click **Auto-Match Transactions** to match obvious pairs, or match manually
7. When the difference reaches ₦0.00, click **Complete Reconciliation**

**Tip:** Reconcile at least monthly to keep your records accurate.

---

## Accounting Periods

An accounting period is typically a month (e.g., "January 2026"). All transactions are recorded within a period.

### Checking Period Status

1. Go to **Periods**
2. You'll see:
   - **Open** - You can still record transactions
   - **Closed** - Period is finalized
   - **Locked** - Period cannot be changed

### Closing a Period

When the month is over:

1. Go to **Periods**
2. Click **Close** on the completed period
3. You must run a **Trial Balance** first to verify debits = credits
4. Add any closing notes
5. Confirm closure

**Important:** Once closed, you cannot record transactions in that period.

### Reopening a Period

If you need to make a correction:

1. Go to **Periods**
2. Click **Reopen** on the closed period
3. Make your correction
4. Close the period again

---

## Financial Reports

### Trial Balance

Shows all accounts with their debit/credit totals. Used to verify that debits equal credits.

**Access:** Reports > Trial Balance

**What to check:**
- Total Debits = Total Credits
- Difference should be 0.00

### Balance Sheet

Shows what you own (Assets) versus what you owe (Liabilities) plus Equity.

**Access:** Reports > Balance Sheet

**Formula:** Assets = Liabilities + Equity

### Income Statement (Profit & Loss)

Shows whether you made money or lost money for the period.

**Access:** Reports > Income Statement

**What it shows:**
- Revenue (sales)
- Cost of Goods Sold (what you paid for items)
- Gross Profit (Revenue - COGS)
- Operating Expenses
- Net Income (bottom line)

### General Ledger

Details all transactions for a specific account.

**Access:** Reports > General Ledger

**Filter by:**
- Account
- Date range
- Status (Posted/Draft)

---

## Common Tasks

### Recording a Sale (Cash)

When you make a cash sale:

```
Debit:  Cash (1010)          ₦10,000
Credit: Sales Revenue (4010) ₦10,000
```

### Recording a Sale (Credit)

When a customer buys on credit:

```
Debit:  Accounts Receivable (1100) ₦10,000
Credit: Sales Revenue (4010)    ₦10,000
```

### Receiving Payment from Customer

```
Debit:  Cash (1010)                ₦10,000
Credit: Accounts Receivable (1100)  ₦10,000
```

### Paying a Supplier

```
Debit:  Accounts Payable (2010)  ₦15,000
Credit: Cash (1010)              ₦15,000
```

### Recording an Expense

```
Debit:  Salaries & Wages (6100)  ₦50,000
Credit: Cash (1010)              ₦50,000
```

---

## Important Rules

1. **Every transaction must balance** - Debits must equal Credits
2. **Use the correct period** - Record in the correct month
3. **Post promptly** - Save entries as Posted, not Draft
4. **Reconcile regularly** - Match bank records weekly
5. **Close periods on time** - Don't leave periods open indefinitely

---

## Troubleshooting

### "Debits don't equal Credits"

- Review each line of your entry
- Make sure amounts are in the correct columns
- Check for amounts entered on both Debit AND Credit

### "No open accounting period"

- Go to Periods
- Check if the current period is closed
- Reopen the period or create a new one

### "Account not found"

- The account may be inactive
- Contact your administrator to activate the account

### "Cannot save entry"

- Check all required fields are filled
- Make sure accounts are different (debit and credit cannot be the same account)

---

## Glossary

| Term | Meaning |
|------|---------|
| **Debit** | Money coming in (left side of entry) |
| **Credit** | Money going out (right side of entry) |
| **Chart of Accounts** | List of all accounts |
| **GL Account** | General Ledger account |
| **Journal Entry** | Recording a transaction |
| **Posting** | Actually recording in the GL |
| **Trial Balance** | Check that debits = credits |
| **Period** | An accounting month |
| **Reconciliation** | Matching bank to GL |

---

## Need Help?

If you encounter issues:
1. Check this guide first
2. Contact your system administrator
3. Note any error messages for faster assistance

---

*This guide is for day-to-day operations. For technical details, see the Internal Documentation.*