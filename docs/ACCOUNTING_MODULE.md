# SweetTooth Accounting Module Documentation

## Overview

The SweetTooth Accounting Module is a comprehensive double-entry bookkeeping system built on Laravel 12 with Livewire. It supports multi-branch accounting with full GL (General Ledger), bank reconciliation, financial reporting, and automated posting from business transactions.

---

## 1. Data Models

### 1.1 Core Accounting Models

#### GlAccount (`app/Models/GlAccount.php`)
The fundamental account entity in the Chart of Accounts.

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `account_number` | string | Unique account code (e.g., 1000, 1010) |
| `account_name` | string | Account display name |
| `account_type` | string | `asset`, `liability`, `equity`, `revenue`, `cost_of_goods_sold`, `expense`, `tax` |
| `account_category` | string | Optional categorization |
| `description` | string | Account description |
| `debit_balance` | decimal | Cumulative debits |
| `credit_balance` | decimal | Cumulative credits |
| `normal_balance` | string | `debit` or `credit` |
| `is_header` | boolean | Header account (grouping only) |
| `parent_account_id` | int | Parent account for hierarchy |
| `is_active` | boolean | Active status |
| `allow_manual_entry` | boolean | Allow manual journal entries |

**Relationships:**
- `parent()` - BelongsTo self (parent account)
- `children()` - HasMany child accounts
- `entries()` - HasMany GlEntry

**Key Methods:**
- `getBalance()` - Calculates balance based on account type
- `updateBalance(float $debit, float $credit)` - Updates running balances

---

#### GlEntry (`app/Models/GlEntry.php`)
Individual General Ledger debit/credit line items.

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `gl_account_id` | int | FK to GlAccount |
| `accounting_period_id` | int | FK to AccountingPeriod |
| `entry_type` | string | `manual`, `sale`, `purchase`, `payment`, etc. |
| `reference_type` | string | Polymorphic type |
| `reference_id` | int | Polymorphic ID |
| `reference_number` | string | External reference |
| `description` | string | Entry description |
| `debit` | decimal | Debit amount |
| `credit` | decimal | Credit amount |
| `entry_date` | datetime | Transaction date |
| `status` | string | `draft`, `posted`, `reversed` |
| `branch_id` | int | FK to Branch |
| `entered_by_id` | int | User who created |
| `posted_by_id` | int | User who posted |
| `reversed_by_id` | int | User who reversed |

**Key Methods:**
- `post(string|int $userId)` - Posts entry and updates account balance
- `reverse(string|int $userId)` - Creates reversing entry

---

#### AccountingEntry (`app/Models/AccountingEntry.php`)
Higher-level accounting transactions with dual entries (debit + credit).

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `branch_id` | int | FK to Branch |
| `accounting_period_id` | int | FK to AccountingPeriod |
| `entry_type` | string | `expense`, `income`, `transfer`, `adjustment`, `journal` |
| `entry_date` | date | Transaction date |
| `description` | string | Description |
| `reference` | string | External reference |
| `amount` | decimal | Transaction amount |
| `debit_gl_account_id` | int | Debit GL account |
| `credit_gl_account_id` | int | Credit GL account |
| `bank_account_id` | int | Optional FK to BankAccount |
| `source` | string | Source system |
| `notes` | string | Additional notes |

---

### 1.2 Period Management

#### AccountingPeriod (`app/Models/AccountingPeriod.php`)
Fiscal period for accounting transactions.

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `year` | int | Fiscal year |
| `month` | int | Fiscal month (1-12) |
| `period_start` | date | Period start date |
| `period_end` | date | Period end date |
| `status` | string | `open`, `closed`, `locked` |
| `closed_by_id` | int | User who closed |
| `closed_at` | datetime | Closure timestamp |
| `closing_notes` | string | Closure notes |

**Status Flow:**
```
open -> closed -> locked
```

**Key Methods:**
- `close(int $userId, ?string $notes)` - Close period
- `lock()` - Lock closed period
- `reopen()` - Reopen closed period (only if not locked)

---

### 1.3 Banking Models

#### BankAccount (`app/Models/BankAccount.php`)
Bank accounts linked to GL accounts.

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `branch_id` | int | FK to Branch |
| `bank_name` | string | Bank name |
| `bank_code` | string | Bank code |
| `account_number` | string | Account number |
| `account_type` | string | Type (checking, savings) |
| `gl_account_id` | int | Linked GL account |
| `opening_balance` | decimal | Opening balance |
| `interest_rate` | decimal | Interest rate |
| `is_active` | boolean | Active status |

**Key Methods:**
- `getCurrentBalance()` - Get balance from latest position
- `getBalanceAsOf($date)` - Balance on specific date

---

#### DailyBankPosition (`app/Models/DailyBankPosition.php`)
Daily bank position tracking.

**Attributes:**
| Field | Type | Description |
|-------|------|-------------|
| `bank_account_id` | int | FK to BankAccount |
| `position_date` | date | Position date |
| `opening_balance` | decimal | Opening balance |
| `inflows_total` | decimal | Total inflows |
| `outflows_total` | decimal | Total outflows |
| `unavailable_balance` | decimal | Unavailable funds |
| `available_balance` | decimal | Available balance |
| `variance_amount` | decimal | Variance from GL |
| `reconciled` | boolean | Reconciliation status |

---

#### DailyBankTransaction (`app/Models/DailyBankTransaction.php`)
Individual bank transactions.

**Constants:**
```php
public const INFLOW = 'inflow';
public const OUTFLOW = 'outflow';

public const POS_SALES = 'pos_sales';
public const TRANSFER_SALES = 'transfer_sales';
public const REVERSED_TRANSFER = 'reversed_transfer';
public const OTHER_INCOME = 'other_income';
public const CASH_DEPOSIT = 'cash_deposit';
public const TRANSFER_EXPENSE = 'transfer_expense';
public const CASH_WITHDRAWAL = 'cash_withdrawal';
public const CHARGEBACK = 'chargeback';
public const BANK_CHARGE = 'bank_charge';
```

---

### 1.4 Supporting Models

#### Budget (`app/Models/Budget.php`)
Budget planning per account/period.

#### AccountTransfer (`app/Models/AccountTransfer.php`)
Inter-bank transfers with GL posting.

#### ExpenseEntry (`app/Models/ExpenseEntry.php`)
Expense claims and entries.

#### Payroll (`app/Models/Payroll.php`)
Employee payroll processing with GL posting.

---

## 2. Services

### 2.1 Core Services

#### AccountingService (`app/Services/AccountingService.php`)
Main accounting operations, posting, and validation.

#### GeneralLedgerService (`app/Services/GeneralLedgerService.php`)
- `getEntries()` - Get GL entries with filters
- `getAccountBalance()` - Get balance per account
- `getEntriesByAccount()` - Entries for specific account
- `getAccountDetails()` - Account with all entries

---

### 2.2 Reporting Services

#### TrialBalanceService (`app/Services/TrialBalanceService.php`)
- `getTrialBalance(?int $periodId)` - Trial balance data
- `getComparativeTrialBalance()` - Compare two periods
- `clearCache()` - Clear cached data
- `getBalancingReport()` - GL balancing status

#### BalanceSheetService (`app/Services/BalanceSheetService.php`)
- `getBalanceSheet(?int $periodId)` - Balance sheet data
- `getComparativeBalanceSheet()` - Compare two periods
- `getFinancialRatios()` - Calculate ratios (current ratio, debt-to-equity, etc.)

#### IncomeStatementService (`app/Services/IncomeStatementService.php`)
- `getIncomeStatement(?int $periodId)` - P&L data
- `getComparativeIncomeStatement()` - Compare two periods
- Account ranges:
  - Revenue: 4000-4099
  - COGS: 5000-5099
  - Operating Expenses: 6000-6999
  - Administrative: 7000-7999
  - Finance Costs: 8000-8999
  - Taxes: 9000-9999

---

## 3. Livewire Components

### 3.1 Dashboard & Overview

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Index` | `/accounting` | Main dashboard |
| `BranchDashboard\Accounting\Simple\Home` | `/accounting/overview` | Overview/home |

### 3.2 Chart of Accounts

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Simple\ChartOfAccounts` | `/accounting/gl-accounts` | GL account list & create |
| `BranchDashboard\Accounting\GlAccountList` | `/accounting/gl-accounts/list` | GL account list |

### 3.3 Journal Entries

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Simple\AccountingEntries` | `/accounting/journal-entries` | Accounting entries |
| `BranchDashboard\Accounting\ManualJournalEntry` | `/accounting/journal-entry/create` | Manual JE form |
| `BranchDashboard\Accounting\Simple\Journals` | `/accounting/journals` | Journal list |

### 3.4 Bank & Cash

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Simple\CashBank` | `/accounting/bank-accounts` | Bank account management |
| `BranchDashboard\Accounting\BankAccounts` | `/accounting/bank-accounts/list` | Bank accounts (advanced) |
| `BranchDashboard\Accounting\BankStatementImport` | `/accounting/bank-statement-import` | Import bank statement (CSV) |
| `BranchDashboard\Accounting\BankReconciliation` | `/accounting/bank-reconciliation` | Reconciliation tool |
| `BranchDashboard\Accounting\CashPosition` | `/accounting/cash-position` | Cash position |

> **Note:** Bank Reconciliation is live. The workflow is: import a statement via `BankStatementImport` (CSV), then match GL entries against the imported bank transactions in `BankReconciliation`.

### 3.5 Period Management

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Simple\PeriodControl` | `/accounting/periods` | Period management |
| `BranchDashboard\Accounting\PeriodManagement` | `/accounting/periods/manage` | Period CRUD |

### 3.6 Reports

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Report\Index` | `/accounting/reports` | Reports index |
| `BranchDashboard\Accounting\Report\TrialBalanceReport` | `/accounting/reports/trial-balance` | Trial Balance |
| `BranchDashboard\Accounting\Report\BalanceSheetReport` | `/accounting/reports/balance-sheet` | Balance Sheet |
| `BranchDashboard\Accounting\Report\IncomeStatementReport` | `/accounting/reports/income-statement` | Income Statement |
| `BranchDashboard\Accounting\Report\GeneralLedgerReport` | `/accounting/reports/general-ledger` | General Ledger |
| `BranchDashboard\Accounting\Report\CashFlowStatementReport` | `/accounting/reports/cash-flow` | Cash Flow |

### 3.7 Specialized Modules

| Component | Route | Description |
|-----------|------|-------------|
| `BranchDashboard\Accounting\Simple\ExpenseEntries` | `/accounting/expenses` | Expense management |
| `BranchDashboard\Accounting\Simple\QuickExpense` | `/accounting/quick-expense` | Quick expense entry |
| `BranchDashboard\Accounting\Simple\Payrolls` | `/accounting/payrolls` | Payroll module |
| `BranchDashboard\Accounting\Simple\TaxPayments` | `/accounting/tax-payments` | Tax payments |
| `BranchDashboard\Accounting\Simple\PurchasePayments` | `/accounting/purchase-payments` | Purchase payments |
| `BranchDashboard\Accounting\Simple\InventoryValuation` | `/accounting/inventory-valuation` | Inventory valuation |
| `BranchDashboard\Accounting\Simple\FixedAssets` | `/accounting/fixed-assets` | Fixed assets |
| `BranchDashboard\Accounting\Simple\Budgets` | `/accounting/budgets` | Budget management |

---

## 4. Routes

All routes are in `routes/accounting.php`:

```php
Route::middleware(['auth', 'accounting'])->prefix('accounting')->group(function () {
    // Dashboard
    Route::get('/', ...)->name('accounting.dashboard');
    
    // GL Accounts
    Route::get('/gl-accounts', ...)->name('accounting.gl-accounts.index');
    
    // Bank Accounts
    Route::get('/bank-accounts', ...)->name('accounting.bank-accounts.index');
    
    // Journal Entries
    Route::get('/journal-entries', ...)->name('accounting.journal-entries.index');
    
    // Periods
    Route::get('/periods', ...)->name('accounting.periods.index');
    
    // Reports
    Route::get('/reports', ...)->name('accounting.reports.index');
    Route::get('/reports/balance-sheet', ...);
    Route::get('/reports/income-statement', ...);
    Route::get('/reports/trial-balance', ...);
    Route::get('/reports/general-ledger', ...);
    Route::get('/reports/cash-flow', ...);
    
    // Bank Statement Import + Reconciliation
    Route::get('/bank-statement-import', ...);
    Route::get('/bank-reconciliation', ...);
    Route::get('/bank-reconciliation/manage', ...);
    
    // Inventory Valuation
    Route::get('/inventory-valuation', ...);
    Route::get('/inventory-valuation/manage', ...);
});
```

---

## 5. Middleware

### AccountingMiddleware (`app/Http/Middleware/AccountingMiddleware.php`)
- Requires `view-accounting` or `manage-accounting` permission
- Or user role: `Accountant`, `Accounting Manager`, `Admin`, `Super Admin`

---

## 6. Chart of Accounts Structure

The default seed (in `GlAccountSeeder`) creates:

### Assets (1000-1999)
| Account | Name | Type |
|---------|------|------|
| 1000 | Assets | Header |
| 1010 | Cash | Asset |
| 1050 | Bank | Asset |
| 1060 | POS Clearing | Asset |
| 1100 | Accounts Receivable | Asset |
| 1200 | Inventory | Asset |
| 1220 | Inventory - Sales | Asset |
| 1500 | Fixed Assets | Asset |
| 1510 | Accumulated Depreciation | Asset |

### Liabilities (2000-2999)
| Account | Name | Type |
|---------|------|------|
| 2000 | Liabilities | Header |
| 2010 | Accounts Payable | Liability |
| 2110 | Payroll Payable | Liability |
| 2120 | Payroll Tax Payable | Liability |
| 2130 | Other Deductions Payable | Liability |
| 2020 | Sales Tax Payable | Tax |
| 2100 | Tax Payable | Tax |

### Revenue (4000-4099)
| Account | Name | Type |
|---------|------|------|
| 4000 | Revenue | Header |
| 4010 | Sales Revenue | Revenue |

### COGS (5000-5099)
| Account | Name | Type |
|---------|------|------|
| 5000 | Cost of Goods Sold | Header |
| 5010 | COGS | COGS |
| 5020 | Damage Loss | Expense |
| 5030 | Shrinkage Loss | Expense |
| 5040 | Write-off Loss | Expense |
| 5050 | Inventory Adjustment | Expense |

### Expenses (6000-6999)
| Account | Name | Type |
|---------|------|------|
| 6100 | Salaries & Wages | Expense |
| 6200 | Depreciation Expense | Expense |
| 6300 | Travel Expenses | Expense |
| 6310 | Meals & Entertainment | Expense |
| 6320 | Office Supplies | Expense |
| 6330 | Communication | Expense |
| 6340 | Accommodation | Expense |
| 6350 | Professional Services | Expense |
| 6900 | Other Expenses | Expense |

---

## 7. Key Flows

### 7.1 Creating a Journal Entry
1. Create `AccountingEntry` with debit/credit accounts
2. Create two `GlEntry` records (debit & credit)
3. Call `->post(userId)` on each entry
4. This updates the `GlAccount.debit_balance` / `credit_balance`

### 7.2 Posting Flow (Business Transaction)
1. Business event occurs (sale, purchase, payment)
2. Observer/Service creates `GlEntry` records
3. Entries linked via `reference_type`/`reference_id`
4. Automatic posting or queue-based posting
5. Status tracked in `*_gl_posting_status` fields

### 7.3 Period Closing
1. Run Trial Balance for period
2. Verify debits = credits
3. Call `AccountingPeriod->close(userId)`
4. Optionally lock period
5. Generate closing entries

---

## 8. Database Schema (Main Tables)

| Table | Description |
|-------|-------------|
| `gl_accounts` | Chart of Accounts |
| `gl_entries` | GL line items |
| `accounting_entries` | Accounting transactions |
| `accounting_periods` | Fiscal periods |
| `bank_accounts` | Bank accounts |
| `daily_bank_positions` | Daily bank positions |
| `daily_bank_transactions` | Bank transactions |
| `budgets` | Budget planning |
| `account_transfers` | Inter-bank transfers |
| `expense_entries` | Expense entries |
| `payrolls` | Payroll records |

---

## 9. Observers

### AccountTransferObserver
Monitors inter-bank transfers and creates GL entries automatically.

---

## 10. Command Line Tools

| Command | Description |
|---------|-------------|
| `php artisan seed:gl-accounts` | Seed Chart of Accounts |
| `php artisan seed:accounting-data` | Seed accounting data |
| `php artisan verify:gl-accounts` | Verify GL account structure |

---

## 11. Permissions

| Permission | Description |
|------------|-------------|
| `view-accounting` | View accounting reports |
| `manage-accounting` | Full accounting access |
| `create-journal-entry` | Create manual journal entries |
| `post-gl-entries` | Post GL entries |
| `close-period` | Close accounting periods |
| `view-trial-balance` | View trial balance |

---

## 12. Key Architectural Patterns

### Double-Entry Bookkeeping
- Every transaction has equal debits and credits
- `GlEntry` records are debit/credit line items
- `AccountingEntry` is a higher-level convenience model

### Branch Scoping
- All entries scoped by `branch_id`
- Uses `current_branch_id()` helper
- Middleware ensures branch context

### Multi-Period Accounting
- Periods can be open/closed/locked
- Historical data preserved
- Support for comparative reporting

### Reference Tracking
- Polymorphic references (`reference_type`, `reference_id`)
- Trace GL entries back to source documents
- Audit trail capability

---

*Documentation generated from codebase analysis.*