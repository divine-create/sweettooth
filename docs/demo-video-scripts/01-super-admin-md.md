# Demo Scripts — Super Admin / Managing Director

**Role:** Level 5. Full system access across all branches. The Super Admin is the
technical owner; the MD is the business owner. They share the same access, so these
videos serve both — the MD-focused sections are marked.

**Total videos in this role:** 7

---

### Section 1 — Logging in and finding your way around
**Estimated length:** ~2:00
**Screen / on-screen actions:**
1. Show the login page at the root URL. Type the email and password, click **Sign in**.
2. Land on the role-based dashboard. Slowly pan across the top navigation and the
   left/side menu.
3. Hover over each major area: HR, Inventory, Production, Sales, Accounting,
   Reporting, Settings.
4. Click the branch switcher at the top and show the list of branches.

**Narration:**
> Welcome to SweetTooth ERP training. In this video, we'll log in as a Super Admin
> and take a tour of the system.
>
> Start at the login page. Enter your email address and password, then click
> "Sign in". If two-factor authentication is turned on for your account, you'll be
> asked for your code here.
>
> Once you're in, you land on your dashboard. As a Super Admin, you can see
> everything: Human Resources, Inventory, Production, Sales, Accounting, Reporting,
> and System Settings. Each area is a module, and you move between them from this
> main menu.
>
> Because SweetTooth supports more than one branch, look at the top of the screen.
> This is your branch switcher. Whatever branch you choose here sets the context for
> every screen you open — the data you see, the reports you run, everything is scoped
> to the selected branch. The Super Admin and Managing Director are the only roles
> that can move freely between all branches.
>
> Let's start with the things only a Super Admin can do.

---

### Section 2 — Managing branches
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **Settings → Branch Management**.
2. Show the list of existing branches with their codes.
3. Click **Create branch** (or the add button). Fill in name, code, address, phone, email.
4. Save, then open the new branch and show the edit view.
5. Briefly show the soft-delete / deactivate option, then cancel out without deleting.

**Narration:**
> In this video, we'll manage branches. Only the Super Admin can create or remove a
> branch, so this is a foundational setup task.
>
> Open System Settings, then Branch Management. Here's the list of every branch in
> the business. Each one has a name and a branch code — the code appears on documents
> and reports, so make sure it's correct.
>
> To add a branch, click "Create branch". Give it a clear name, set its branch code,
> and fill in the address, phone number, and email. These details print on receipts
> and official reports, so take a moment to get them right. When you're done, click
> Save.
>
> To change a branch later, open it from the list and edit any field. If a branch
> closes, you don't delete its history — instead you deactivate it. That keeps all of
> its past sales, stock, and accounting records intact while removing it from the
> active list. We'll leave this branch active for now.

---

### Section 3 — Roles and permissions
**Estimated length:** ~3:00
**Screen / on-screen actions:**
1. Go to **Settings → Roles & Permissions**.
2. Show the list of roles and their levels (Staff, Supervisor, Manager, etc.).
3. Open one role and show the permissions attached to it.
4. Go to **Role Assignments**. Search for an employee, assign a role.
5. Demonstrate granting a single extra permission to a user on top of their role.

**Narration:**
> In this video, we'll look at roles and permissions — how you control who can do
> what.
>
> Open Roles and Permissions. The system is built on five access levels. Level one is
> Staff, who carry out daily work. Level two is Supervisors, who oversee and approve.
> Level three is Department Managers. Level four is administrative roles like the
> Accounting Manager. And level five is the Managing Director and Super Admin, with
> full access.
>
> Each role is a bundle of permissions. If I open a role, I can see exactly which
> actions it allows — for example, processing a sale, or posting a journal entry.
>
> To give someone a role, go to Role Assignments. Search for the employee, then
> assign the role that matches their job. Remember: Human Resources can assign roles
> up to Manager level, but only a level-five user can grant the Managing Director or
> Super Admin role.
>
> Sometimes a person needs just one extra ability without changing their whole role.
> For example, a sales staff member who also needs to view inventory reports. Instead
> of promoting them, you can grant that single permission directly to the user, right
> here. Their role stays the same, but they gain that one extra screen.

---

### Section 4 — System settings and configuration
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **Settings → System Settings**.
2. Show currency / locale settings.
3. Show tax rate (VAT) configuration.
4. Show POS configuration: receipt footer, table management toggle.
5. Save a change to demonstrate.

**Narration:**
> In this video, we'll configure the system-wide settings. These control how the
> whole branch behaves, so they're usually set once at go-live and rarely changed.
>
> Open System Settings. First, set the currency and locale so amounts and dates
> display correctly for the business. Next, if the business charges value-added tax,
> set the tax rate here — the point of sale and accounting modules use this rate
> automatically.
>
> Below that is the point-of-sale configuration. You can set the message that prints
> at the bottom of every receipt, and you can turn table management on or off,
> depending on whether the branch runs table service. When you change a setting,
> remember to save. Your change applies the next time staff open the affected screen.

---

### Section 5 — User Activity Monitor
**Estimated length:** ~2:00
**Screen / on-screen actions:**
1. Go to **Settings → User Activity Monitor** (super-admin only).
2. Show the live feed of logins, logouts, and actions.
3. Filter by user and by date.
4. Open a single user's activity trail.

**Narration:**
> In this video, we'll look at the User Activity Monitor — a tool only the Super
> Admin can see.
>
> Every time a user logs in, logs out, or performs an action in the system, it's
> recorded here. This gives you a complete audit trail of who did what, and when.
>
> You can filter the feed by a specific user or by a date range to narrow things
> down. If you're investigating an issue — say, a record that changed unexpectedly —
> open that user's activity trail and you'll see the sequence of actions they took.
>
> Note that developer accounts are deliberately hidden from this log, so what you see
> here is genuine business activity.

---

### Section 6 — MD Reports dashboard *(MD-focused)*
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **MD Reports** dashboard.
2. Show the list of compiled reports sent up from departments.
3. Open a compiled report; scroll through its sections.
4. Add an annotation / comment on a report.
5. Show the consolidated executive summary view.

**Narration:**
> In this video, we'll explore the Managing Director's reports dashboard. This is the
> executive view, designed for the business owner rather than the technical admin.
>
> As each department finishes its reporting cycle, it compiles its reports and sends
> them up to the Managing Director. They all arrive here, in one place.
>
> Open a compiled report and you'll see it pulls together the key numbers from a
> department — sales, production, inventory, or accounting — into a single document.
> Scroll through to review the figures.
>
> If you have a question or a direction for the team, you can add an annotation
> directly on the report. The department sees your note, so this becomes a simple
> back-and-forth without leaving the system.
>
> The consolidated summary at the top gives you the whole business at a glance,
> across every department.

---

### Section 7 — Cross-branch financial overview *(MD-focused)*
**Estimated length:** ~3:00
**Screen / on-screen actions:**
1. Switch branches using the top switcher to show data changing.
2. Go to **Accounting → Financial Reports**.
3. Open the Income Statement, then the Balance Sheet, then Cash Flow.
4. Open the **Branch Comparison** report.
5. Export a report to PDF.

**Narration:**
> In this final video for the Managing Director, we'll look at the financial picture
> across the whole business.
>
> Because you can switch branches at the top, you can review the numbers for any
> single branch. Watch how the data updates when I change the branch.
>
> Open the Accounting module and go to Financial Reports. Here you have the full set
> of financial statements: the Income Statement shows profit and loss over a period;
> the Balance Sheet shows what the business owns and owes at a point in time; and the
> Cash Flow Statement shows how money moved in and out.
>
> When you run more than one branch, the Branch Comparison report is especially
> useful — it puts the branches side by side so you can see which is performing and
> where attention is needed.
>
> Any report can be exported to a PDF for sharing or filing. That completes the Super
> Admin and Managing Director videos.
