# SweetTooth ERP — Roles & Responsibilities Guide

This document describes every role in the system, what each role can do, and which parts of the application they can access. Share this with department heads so staff know what to expect when they log in.

---

## Role Hierarchy Overview

The system has five access levels. A higher level can always do everything a lower level can do within their department.

```
Level 5 — Super Admin / Managing Director    (full system access)
Level 4 — Admin / Accounting Manager         (branch-wide administrative access)
Level 3 — Department Managers               (full control of their department)
Level 2 — Supervisors                        (day-to-day oversight, approve transactions)
Level 1 — Staff                              (perform daily operations only)
```

---

## Level 5 Roles

### Super Admin
The technical system owner. Typically the developer or IT person managing the software.

**Can do:**
- Everything in the system across all branches
- Create, edit, and delete branches
- Manage roles and permissions for all users
- Access the User Activity Monitor (see what every user does in the system)
- View all financial reports across all branches
- Access system settings
- Cannot be managed or restricted by any other role

**Screens accessible:** All screens in the system.

---

### MD (Managing Director)
The business owner or executive director. Has the same access level as Super Admin but is a business user rather than a technical one.

**Can do:**
- View all operational reports (sales, production, inventory, HR, accounting)
- Manage all departments, employees, branches, and settings
- Approve and manage roles and permissions
- View full accounting including financial statements (balance sheet, income statement, cash flow)
- Manage payroll
- Manage bank accounts and reconciliation
- Access MD Reports dashboard (consolidated reports sent up from all departments)
- Export any report
- Create and manage suppliers
- Manage discounts and refunds

**Screens accessible:** All screens. The MD Reports section is specifically designed as an executive summary view.

---

## Level 4 Roles

### Accounting Manager
The head of the accounts department. Full control over financial records.

**Can do:**
- View and post all accounting entries
- Manage the Chart of Accounts (add/edit GL accounts)
- Manage accounting periods (open and close periods)
- Manage bank accounts
- Perform bank reconciliation
- View all financial reports (balance sheet, income statement, trial balance, general ledger, cash flow)
- View POS remittances, expense entries, expense imports, purchase payments, tax payments
- Send reports to the MD
- View analytics

**Cannot do:**
- Manage employees or HR functions
- Manage inventory or production
- Create new branches or change system settings

---

### Accounting Staff
A junior member of the accounts team who handles daily postings.

**Can do:**
- View and post accounting entries
- Manage accounting periods
- Manage bank accounts
- Perform bank reconciliation
- View accounting module

**Cannot do:**
- View financial reports (balance sheet, income statement, etc.)
- Send reports to the MD
- View POS remittances, purchase payments, tax payments, or expense imports

> **Note:** Accounting Staff has the same system access level as Accounting Manager but fewer report-viewing permissions. Use Accounting Manager for the head of accounts and Accounting Staff for bookkeepers or data entry personnel.

---

## Level 3 Roles

### HR
The Human Resources manager. Full control over people management.

**Can do:**
- Manage employees (create, edit, view employee profiles and employment details)
- Manage departments (create and edit departments and categories)
- Manage leave (approve leave requests, set leave types, manage allocations)
- Manage payroll (run payrolls, create payslips)
- Manage staff schedules (assign shifts, configure shift times)
- Manage role assignments (assign roles to employees within the branch)
- View HR reports (workforce overview, leave utilisation)
- View analytics
- Send reports to MD

**Cannot do:**
- Create or delete roles themselves (only assign existing roles)
- Access accounting, inventory, or production modules
- Change system-level settings

---

### Inventory Manager
Manages the store/inventory department. Full control over stock and purchasing.

**Can do:**
- Manage inventory items and categories (add, edit, deactivate items)
- Manage purchases (create purchase orders, receive goods, record stock batches)
- Manage stock takes (initiate and complete stock counts)
- Manage suppliers (add and edit supplier profiles)
- View inventory reports (stock levels, stock movement, valuation, item usage)
- View analytics
- Send reports to MD

**Cannot do:**
- Access sales, production, or accounting modules
- Manage employees or HR functions
- Process sales transactions

---

### Production Manager
Manages production departments (e.g. Hot Kitchen, Pastry, Gelato). Full control over production operations.

**Can do:**
- Manage production (create production records, update production status, manage production pipeline)
- Manage recipes (create and edit recipes for finished goods and WIP)
- Manage quality (mark quality checks, approve or reject production batches)
- Manage staff schedule (assign production staff to shifts)
- View inventory (read-only access to see available materials)
- View production reports (efficiency, quality, waste, cost, ingredient utilisation)
- View analytics
- Send reports to MD

**Cannot do:**
- Access sales, accounting, or HR modules
- Edit inventory items or purchase stock

---

### Sales Manager
Manages the sales floor (e.g. Till Sales, Concession, Corner Store). Full control over the POS and sales operations.

**Can do:**
- Manage sales (oversee all sales transactions in the department)
- Process sales (operate the POS)
- Manage discounts (apply or approve discounts on sales)
- Manage refunds (process refunds and returns)
- Manage staff schedule (assign sales staff to shifts)
- View inventory (read-only access to see stock levels)
- View sales reports (daily sales, revenue, performance)
- View analytics
- Send reports to MD

**Cannot do:**
- Access production, accounting, or HR modules
- Edit inventory items

---

### Chef
A specialist production role for head chefs or kitchen leads.

**Can do:**
- Manage production (create and update production records)
- View production (view all production data)

**Note:** The Chef role is a streamlined version of Production Manager focused purely on production tasks without report or schedule management. Assign this to a head chef who runs the kitchen but does not handle admin.

---

## Level 2 Roles (Supervisors)

Supervisors can oversee daily work, approve transactions, and handle exceptions. They cannot change master data (items, recipes, accounts) but can resolve issues within their shift.

### Till Supervisor / Cornerstore Supervisor / Concession Supervisor
Supervises the sales floor for their specific area.

**Can do:**
- All sales staff functions (process sales, view sales)
- Approve variance resolutions (resolve discrepancies between dispatched and recorded stock)
- Oversee dispatches and callbacks for their area
- Access shift closing for their department

---

### Hot Kitchen Chef / Pastry Chef
Supervises production for their specific kitchen section.

**Can do:**
- All production staff functions (view production)
- Oversee production records and quality for their kitchen

---

### Assistant Shop Floor Manager
Deputy to the Sales Manager; can step in to handle floor management tasks.

**Can do:**
- Sales floor oversight including dispatch approval
- Variance resolution
- Shift operations

---

### Inventory Team Lead / Procurement Officer
Supports the Inventory Manager in day-to-day stock management.

**Can do:**
- View and manage inventory operations
- Support purchasing and stock take activities

---

### HR Officer
Supports the HR Manager with administrative people management.

**Can do:**
- Employee record maintenance
- Leave administration
- Clock-in monitoring

---

### Accountant / Cost Accountant
Supports the Accounting Manager with bookkeeping tasks.

**Can do:**
- Post accounting entries
- View accounting module

---

## Level 1 Roles (Staff)

Staff members perform daily operations only. They cannot approve, amend, or delete records — only create or view within their function.

### Sales Staff
A general sales floor worker (cashier, server, attendant).

**Can do:**
- Process sales on the POS
- View sales transactions they have processed

**Daily workflow:**
1. Clock in → select shift
2. Complete stock opening (count and record opening stock for their station)
3. Operate the POS to record customer sales
4. Request production items when stock is running low (Production Request)
5. Complete shift closing at end of shift

---

### Waiter
Same as Sales Staff but specifically for table service.

**Can do:**
- Process sales (take orders and record them on the POS)
- View sales

---

### Production Staff
A general production kitchen worker.

**Can do:**
- View production records and reports
- Log production output and progress

**Daily workflow:**
1. Clock in → select shift
2. Receive material requests from inventory
3. Log production quantities (quick produce or production order)
4. Record any material returns to inventory
5. Close shift with production totals

---

### Inventory Staff
A general store worker.

**Can do:**
- View inventory (stock levels, items, categories)

**Note:** Inventory Staff have view-only access. They can see stock but cannot edit items, create purchases, or approve requests. Use this for store assistants who need to look up stock information but should not make changes.

---

## How Roles Work in Practice

### Shift requirement
Almost all operational screens (inventory, production, sales) require an active shift to be open. Staff must clock in and select a shift before they can access any work screen. Shift configuration (times, types) is managed by HR or the Super Admin.

### Department scope
Production and Sales screens are department-specific. A user only sees data for the department they belong to. A Pastry Chef sees Pastry data; a Till Supervisor sees Till Sales data. This is controlled by their department assignment in their employee profile.

### Role assignment
Only the HR role (and above) can assign roles to users within the branch. The Super Admin or MD can assign any role. HR can assign roles up to Level 3 (Manager). Only Level 5 can assign the MD or Super Admin role.

### Permissions vs Roles
The system supports adding individual permissions directly to a user on top of their role. For example, if a Sales Staff member also needs to view inventory reports, an admin can grant them the `view-inventory-reports` permission directly without changing their role. This is done under `Roles & Permissions → Role Assignments`.

---

## Quick Reference: Who Can Do What

| Task | Staff | Supervisor | Manager | Accounting Manager | HR | MD/Super Admin |
|------|-------|-----------|---------|-------------------|-----|----------------|
| Process a sale (POS) | Sales Staff ✓ | ✓ | ✓ | — | — | ✓ |
| Approve a discount | — | — | Sales Manager ✓ | — | — | ✓ |
| Log production output | Production Staff ✓ | ✓ | ✓ | — | — | ✓ |
| Create a purchase order | — | — | Inventory Manager ✓ | — | — | ✓ |
| Approve a leave request | — | — | — | — | HR ✓ | ✓ |
| Run payroll | — | — | — | — | HR ✓ | ✓ |
| Post a journal entry | — | — | — | ✓ | — | ✓ |
| View balance sheet | — | — | — | ✓ | — | ✓ |
| Reconcile bank account | — | — | — | ✓ | — | ✓ |
| Add a new employee | — | — | — | — | HR ✓ | ✓ |
| Assign roles to users | — | — | — | — | HR ✓ | ✓ |
| Create a new branch | — | — | — | — | — | ✓ |
| Change system settings | — | — | — | — | — | ✓ |
| View user activity logs | — | — | — | — | — | ✓ |

---

## Recommended Role Assignments by Job Title

| Job Title | Assign This Role |
|-----------|-----------------|
| Business Owner / CEO | MD |
| General Manager | MD or Admin |
| Branch Manager | Admin |
| Head of Accounts | Accounting Manager |
| Accountant / Bookkeeper | Accounting Staff |
| HR Manager | HR |
| HR Assistant | HR Officer (Level 2) |
| Head of Inventory / Storekeeper Supervisor | Inventory Manager |
| Store Assistant | Inventory Staff |
| Head of Production (overall) | Production Manager |
| Head Chef / Kitchen Lead | Chef or Production Manager |
| Pastry Chef | Pastry Chef (Level 2) |
| Hot Kitchen Chef | Hot Kitchen Chef (Level 2) |
| Production Kitchen Worker | Production Staff |
| Sales Floor Manager | Sales Manager |
| Floor Supervisor / Till Supervisor | Till Supervisor or Assistant Shop Floor Manager |
| Cashier | Sales Staff |
| Waiter / Server | Waiter |
| Concession Staff | Sales Staff |
| Corner Store Staff | Sales Staff |
