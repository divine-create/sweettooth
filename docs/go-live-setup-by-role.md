# SweetTooth ERP — Go-Live Setup Guide (By Role)

Each role has specific setup tasks to complete before the system can go live. Work through these in order — some roles depend on others finishing first.

---

## Recommended Setup Order

```
1. Super Admin / Developer     (system configuration)
2. MD / Managing Director      (branch & executive setup)
3. HR                          (employees, roles, shifts)
4. Accounting Manager          (chart of accounts, opening balances)
5. Inventory Manager           (opening stock)
6. Production Manager          (production store, recipes)
7. Sales Manager               (verify POS readiness)
```

---

## 1. Super Admin / Developer

Do this before handing the system to any other user.

**System configuration:**
- [ ] Set `APP_URL` in `.env` to the real domain (e.g. `https://sweettooth.com`)
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in `.env`
- [ ] Configure SMTP email in `.env` (`MAIL_MAILER`, `MAIL_HOST`, `MAIL_USERNAME`, `MAIL_PASSWORD`) so notifications actually send
- [ ] Set up a cron job to run `php artisan schedule:run` every minute (handles auto clock-out, batch expiry checks, shift reminders)
- [ ] Set up a queue worker (`php artisan queue:work`) so background jobs (exports, GL retry, database backups) process
- [ ] Run `php artisan storage:link` to enable file uploads

**Clear demo/test data:**
- [ ] Run the data wipe SQL provided in the go-live setup document to clear all demo transactions (sales, shifts, purchases, production records, stock levels, GL entries, audit logs, notifications)

**Super Admin account:**
- [ ] Log in with `newuser@sweettooth.local` / `password`
- [ ] Go to **Profile** → change email to the real owner/MD email address
- [ ] Change the password to something strong immediately
- [ ] Go to **Employee Management** → link this user to the actual MD employee record

---

## 2. MD / Managing Director

**Branch details:**
- [ ] Go to **Branch Management** → edit "SweetTooth Port Harcourt"
- [ ] Confirm the branch name, address, phone number, and email are correct
- [ ] Confirm the branch code (PHC-002) is correct — this appears on documents and reports

**System settings:**
- [ ] Go to **System Settings**
- [ ] Set the correct currency and locale for the business
- [ ] Set the correct tax rate (VAT %) if applicable
- [ ] Review POS configuration (receipt footer message, table management on/off)

**MD Reports:**
- [ ] Verify that the department managers know to send compiled reports to MD through the system (`Reporting → Send to MD`) once operations begin

---

## 3. HR

Complete these before any staff member tries to log in.

**Step 1 — Review employees:**
- [ ] Go to **Employee Management** → review all 72 imported employee records
- [ ] For each employee, confirm: name, employee number, department assignment, and employment status are correct
- [ ] Flag any employees who have left and set them to **Inactive**

**Step 2 — Assign roles to every employee:**
- [ ] Go to **Roles & Permissions → Role Assignments**
- [ ] Assign the correct system role to every active employee (refer to `docs/roles-and-responsibilities.md` for the role-to-job-title mapping)
- [ ] Ensure at least one person per department has a Manager role so they can approve transactions

**Step 3 — Reset all staff passwords:**
- [ ] For each employee, set an initial password (e.g. their employee number or a standard format like `Sweetooth@2026`)
- [ ] Communicate the initial password to each staff member and instruct them to change it on first login
- [ ] Ensure the Accounting Manager, Inventory Manager, Production Manager, and Sales Manager have their passwords set and confirmed before their own setup steps begin

**Step 4 — Departments:**
- [ ] Go to **Departments** → review all 13 departments
- [ ] Confirm each department has the correct category (Production, Sales, or Support)
- [ ] Add any missing departments; deactivate any that no longer exist

**Step 5 — Shift configuration:**
- [ ] Go to **Shift Management → Configuration**
- [ ] The system already has 4 shifts (Morning 06:00, Afternoon 14:00, Evening 16:00, Full-time 06:00) — adjust the times if they do not match actual shift hours
- [ ] Go to **Shift Management → Assignment** → assign each active employee to their default shift type

**Step 6 — Leave setup:**
- [ ] Go to **Leave Management → Leave Types** → confirm leave types are set up (Annual Leave, Sick Leave, etc.)
- [ ] Go to **Leave Management → Manage Allocations** → allocate annual leave entitlements for every employee for the current year (e.g. 21 days annual leave each)

**Step 7 — Units of Measure:**
- [ ] Go to **Organization → Units & Measure**
- [ ] The system has 32 standard UOMs (grams, kg, litres, pieces, etc.) — review and add any specialist units used by the kitchen that are missing
- [ ] Review UOM conversions (e.g. 1 kg = 1000 g) and add any that are missing for ingredients used in recipes

---

## 4. Accounting Manager

Complete these before any sales, purchases, or production transactions are recorded.

**Step 1 — Review Chart of Accounts:**
- [ ] Go to **Accounting → Accounts**
- [ ] The system has 47 GL accounts pre-configured — review each one
- [ ] Rename any accounts that do not match the business's terminology
- [ ] Add any accounts that are missing (e.g. specific expense accounts for rent, utilities, cleaning)
- [ ] Ensure every department has revenue and cost accounts mapped to it (the system uses these for automatic GL posting)

**Step 2 — Set up the real bank account:**
- [ ] Go to **Accounting → Bank Accounts**
- [ ] Delete the demo "Testing Bank" account (balance of 4,444,444,444 — this is test data)
- [ ] Add each real business bank account:
  - Bank name
  - Account number
  - Account type (savings / current)
  - Opening balance as of go-live date

**Step 3 — Create accounting periods:**
- [ ] Go to **Accounting → Periods**
- [ ] Create an accounting period for the current month (e.g. June 2026), status: **Open**
- [ ] Create periods for the next 2–3 months in advance so they are ready
- [ ] Do NOT leave the old demo periods (February 2026, May 2026) open — close or delete them

**Step 4 — Enter opening GL balances:**
- [ ] Go to **Accounting → Journal Entry**
- [ ] Create a single opening balance journal entry dated as of go-live date
- [ ] Debit each asset account (Cash, Bank, Inventory) with its actual opening balance
- [ ] Credit each liability account (Accounts Payable, etc.) with its actual opening balance
- [ ] Credit Equity / Retained Earnings with the balancing figure
- [ ] This journal establishes the financial position on day one

**Step 5 — POS Remittances:**
- [ ] Go to **Accounting → POS Remittances**
- [ ] Confirm the POS clearing account is correctly mapped so daily till remittances post to the right GL account

**Step 6 — Verify GL posting is working:**
- [ ] After the first real sale is made, go to **Accounting → Posting Status**
- [ ] Confirm the sale posted to GL with no failures
- [ ] If there are failures, review the GL account mapping for the relevant department

---

## 5. Inventory Manager

Complete these after the Accounting Manager has set up the GL accounts, as stock movements trigger GL entries.

**Step 1 — Review items:**
- [ ] Go to **Inventory → Items**
- [ ] The system has 793 items — review them for accuracy
- [ ] Confirm each item has a correct: name, category, unit of measure, and unit cost
- [ ] Deactivate any items that are no longer stocked or used
- [ ] Add any missing items

**Step 2 — Review item categories:**
- [ ] Go to **Inventory → Item Categories**
- [ ] Confirm categories match the business's storage and reporting structure
- [ ] Rename or merge any duplicate categories

**Step 3 — Add suppliers:**
- [ ] Go to **Suppliers**
- [ ] The system has 2 demo suppliers — review and update with correct details
- [ ] Add all real suppliers with their full details: contact person, phone, email, bank account, payment terms

**Step 4 — Enter opening stock (most important step):**
- [ ] Go to **Inventory → Stock Takes**
- [ ] Create a new stock take for the go-live date
- [ ] Count every item physically in the store/warehouse
- [ ] Enter the actual quantity for each item
- [ ] Submit the stock take — this becomes the official opening stock the system uses for all future movements
- [ ] Alternatively, if an Excel sheet of opening quantities is available, use **Inventory → Items** to update quantities directly

**Step 5 — Set reorder levels:**
- [ ] For each item, set a minimum stock level so the system can alert when stock falls below the threshold

---

## 6. Production Manager

Complete these after the Inventory Manager has entered opening stock, since production consumes inventory.

**Step 1 — Review products:**
- [ ] Go to **Production → Products** for each production department (Hot Kitchen, Pastry, Gelato, Cornerstone)
- [ ] The system has 434 products — confirm each product belongs to the correct department
- [ ] Deactivate any products that are no longer made
- [ ] Add any new products

**Step 2 — Review and complete recipes:**
- [ ] Go to **Production → Recipes** for each department
- [ ] The system has 181 recipes — for each recipe confirm: the ingredient list, quantities, and units are accurate
- [ ] Add recipes for any products that do not have one (the system cannot auto-calculate production costs without a recipe)
- [ ] Set up WIP (Work In Progress) recipes for any semi-finished goods used across multiple products

**Step 3 — Enter production store opening stock:**
- [ ] Go to **Production → Store** for each department (Hot Kitchen, Pastry, Gelato, Cornerstone)
- [ ] Enter the actual quantity of finished goods and WIP currently held in each production store
- [ ] This is separate from inventory — it is the stock sitting in the kitchen/production area ready to be dispatched to sales

**Step 4 — Assign products to departments:**
- [ ] Go to **Production → Product Assignments**
- [ ] Confirm each product is correctly assigned to its production department so that when Sales requests an item, it goes to the right kitchen

**Step 5 — Test production workflow:**
- [ ] Create one test production record end-to-end: request materials → record production output → dispatch to production store
- [ ] Confirm the GL posts correctly after the production record is saved

---

## 7. Sales Manager

Complete these after Inventory and Production are fully set up.

**Step 1 — Review products available for sale:**
- [ ] Go to **Sales Dashboard → Dispatches** for each sales department (Till Sales, Concession, Corner Store)
- [ ] Confirm the products that appear for dispatch match what is actually sold at each station
- [ ] If products are missing from the sales list, notify the Production Manager to check product-to-department assignments

**Step 2 — Verify POS configuration:**
- [ ] Go to **System Settings** → confirm POS settings (receipt format, table management if enabled)
- [ ] If table management is enabled, go to **Branch Management** → confirm tables are set up correctly for the floor layout

**Step 3 — Train staff on the daily workflow:**

Ensure every Sales Staff member understands this exact sequence for each shift:
1. **Clock In** → select their shift
2. **Stock Opening** (`Sales Dashboard → Stock Opening`) → count and enter opening stock at their station before any sales
3. **POS** (`Sales Dashboard → POS`) → process sales throughout the shift (only available after stock opening is done)
4. **Production Requests** (`Sales Dashboard → Production Requests`) → request more stock from the kitchen when running low
5. **Shift Closing** (`Sales Dashboard → Shift Closing`) → submit end-of-shift stock count and sales summary

> The system enforces this order — staff cannot access the POS until stock opening is completed.

**Step 4 — Test a full shift cycle:**
- [ ] Have one staff member run through steps 1–5 above as a test before go-live day
- [ ] Confirm the shift closes correctly and the sales appear in the Accounting posting status with no failures

---

## Final Go-Live Checklist (All Roles Together)

Run through this on the day before going live:

- [ ] Super Admin: `.env` is production-ready, cron and queue worker are running
- [ ] Super Admin: demo data has been cleared, super admin password changed
- [ ] HR: all employees have roles assigned and passwords reset
- [ ] HR: all employees are assigned to a shift
- [ ] HR: leave allocations are set for the current year
- [ ] Accounting: real bank account is set up, demo bank deleted
- [ ] Accounting: accounting period for current month is open
- [ ] Accounting: opening GL balance journal has been posted
- [ ] Inventory: opening stock take completed and submitted
- [ ] Inventory: all real suppliers added
- [ ] Production: all recipes reviewed and complete
- [ ] Production: production store opening stock entered for all departments
- [ ] Sales Manager: tested a complete shift end-to-end (clock in → stock open → sale → shift close)
- [ ] Accounting: confirmed GL posting status shows no failures after the test sale
- [ ] All managers: email notifications are working (receive a test notification)
