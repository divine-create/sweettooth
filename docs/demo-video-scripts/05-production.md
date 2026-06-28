# Demo Scripts — Production Manager

**Role:** Level 3. Full control over production for the kitchens (Hot Kitchen,
Pastry, Gelato, Cornerstone): products, recipes, production records, the production
store, material requests and returns, fulfilling sales requests, and quality. Read-
only on inventory; no access to sales or accounting operations.

> **Note for recording:** Production screens require an active shift and are
> department-scoped — you only see your own kitchen's data. Clock in, select a shift,
> and pick the right department before recording.

**Total videos in this role:** 9

---

### Section 1 — Production dashboard tour
**Estimated length:** ~1:30
**Screen / on-screen actions:**
1. Log in as Production Manager; clock in / select shift / department; land on dashboard.
2. Pan across the menu: Products, Recipes, Production Records, Quick Produce, Store,
   Material Requests, Material Returns, Sales Production Requests, Callbacks, Reports.

**Narration:**
> Welcome to SweetTooth ERP training. In this video, we'll tour the Production module.
>
> Production screens need an active shift, and they're scoped to your department — so
> I've clocked in and selected my kitchen. Everything I see now is for this kitchen
> only.
>
> The dashboard shows what's in progress, what's been produced, and any requests
> waiting on you. From the menu you can reach products, recipes, production records,
> quick produce, the production store, material requests and returns, sales production
> requests, callbacks, and reports.
>
> A quick reminder of the core idea: a "product" is something the kitchen makes or
> sells; an "item" is the raw ingredient held in inventory. Production turns items
> into products using recipes.

---

### Section 2 — Products and product types
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **Production → Products**. Show the list filtered by department.
2. Open a product; show its type (finished good or work-in-progress) and department.
3. Create a product; set its type and department.
4. Show how to deactivate a product no longer made.

**Narration:**
> In this video, we'll manage products.
>
> Open Products. These are the things your kitchen makes. Each product has a type and
> belongs to a department. There are two main types: a finished good, which is sold to
> the customer, and a work-in-progress item — a sauce, batter, or dough — that's used
> as a building block inside other products.
>
> To add a product, click add, name it, choose its type, and assign it to the right
> kitchen. Getting the department right matters, because when sales requests an item,
> the request is routed to the kitchen the product belongs to.
>
> If you stop making a product, deactivate it rather than deleting it, so its history
> is kept.

---

### Section 3 — Building recipes
**Estimated length:** ~3:30
**Screen / on-screen actions:**
1. Go to **Production → Recipes**.
2. Create a recipe for a finished good: add ingredients, quantity per ingredient,
   unit of measure, yield quantity.
3. Show the calculated recipe cost.
4. Create a WIP recipe and show how it can be used inside a finished-good recipe.

**Narration:**
> In this video, we'll build a recipe. This is the most important production setup
> task, because a product without a recipe can't be produced through the system and
> won't be costed.
>
> Open Recipes and create a new one for a finished good. Add each ingredient, the
> quantity you use, and its unit of measure. Then set the yield — how many units the
> recipe produces. As you add ingredients, the system pulls their cost from inventory
> and calculates the total recipe cost for you. That's how you know what a dish costs
> to make.
>
> You can also build work-in-progress recipes — for example a sauce or a dough. Once a
> work-in-progress item has its own recipe, you can use it as an ingredient inside a
> finished-good recipe, so your costing flows all the way through from raw material to
> finished plate.

---

### Section 4 — Requesting materials from inventory
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **Production → Material Requests**. Create a request to inventory.
2. Add items and quantities needed. Submit.
3. Show the request status (pending → approved by inventory).

**Narration:**
> In this video, we'll request materials from the store.
>
> Before you can cook, you need ingredients. Open Material Requests and create a new
> request. Add the items and quantities you need for your production, then submit it.
>
> The request goes to the inventory team for approval. You can track its status here —
> pending until they approve and issue the materials. Once issued, the ingredients are
> handed over to your kitchen and you're ready to produce. This keeps a clean record
> of exactly what moved from the store into production.

---

### Section 5 — Producing: quick produce and production records
**Estimated length:** ~3:30
**Screen / on-screen actions:**
1. Go to **Production → Quick Produce**. Select a product; enter quantity produced.
2. Show the finished-good path and the WIP path.
3. Confirm; show ingredients consumed automatically per the recipe.
4. Go to **Production Records** to show the logged production and its status.
5. Mention automatic GL posting.

**Narration:**
> In this video, we'll record production.
>
> The fastest way is Quick Produce. Choose the product you're making and enter how
> many you produced. There are two paths: producing a finished good ready for sale, or
> producing a work-in-progress item like a batch of sauce to use later.
>
> When you confirm, the system reads the recipe and automatically deducts the
> ingredients you used from your materials. You don't count them out by hand — the
> recipe does it. The finished output is added to your production store.
>
> Every production run is logged under Production Records, where you can follow its
> status and review what was made. And because production consumes ingredients and
> creates value, it posts to the general ledger automatically, keeping costs accurate.

---

### Section 6 — The production store and dispatching to sales
**Estimated length:** ~3:00
**Screen / on-screen actions:**
1. Go to **Production → Store**. Show finished goods and WIP held in the store.
2. Show store movements (in from production, out to sales).
3. Go to **Sales Production Requests**. Open a request from a sales station.
4. Fulfil the request and dispatch stock to sales.

**Narration:**
> In this video, we'll work with the production store and send stock to the sales
> floor.
>
> Open Store. This is the holding area for everything your kitchen has made — finished
> goods ready to sell, and work-in-progress stock waiting to be used. The store
> movements show what's come in from production and what's gone out to sales.
>
> When a sales station runs low, they send you a sales production request. Open Sales
> Production Requests to see them. Review what's being asked for, then fulfil the
> request — this dispatches the stock from your production store to that sales station,
> so the cashier can sell it. The handover is tracked, so both sides agree on what was
> sent.

---

### Section 7 — Material returns
**Estimated length:** ~1:30
**Screen / on-screen actions:**
1. Go to **Production → Material Returns**.
2. Create a return of unused materials back to inventory.
3. Submit; show stock returning to the store.

**Narration:**
> In this short video, we'll return unused materials to the store.
>
> Sometimes you request more ingredients than you end up using. Rather than letting
> them sit in the kitchen, send them back. Open Material Returns, create a return,
> list the unused items and quantities, and submit. The stock goes back into inventory
> and your records stay accurate. This keeps the difference between what was issued and
> what was actually consumed honest.

---

### Section 8 — Quality and callbacks
**Estimated length:** ~2:00
**Screen / on-screen actions:**
1. Show the quality check step on a production record (mark pass/fail).
2. Go to **Production → Callbacks**. Open a callback (a return from sales).
3. Review and approve or reject the callback.

**Narration:**
> In this video, we'll cover quality checks and callbacks.
>
> As part of production, you can record a quality check — marking a batch as passed or
> rejected before it moves on. This stops sub-standard product reaching the customer
> and gives you a quality record over time.
>
> A callback is when something comes back from the sales floor — for example a return.
> Open Callbacks, review the item and the reason, and approve or reject it. Approving
> brings it back into your process so it can be handled correctly, whether that's
> remaking, reworking, or writing it off.

---

### Section 9 — Production reports and sending to the MD
**Estimated length:** ~2:30
**Screen / on-screen actions:**
1. Go to **Production → Reports**.
2. Open Efficiency, Quality, Waste, Cost, Ingredient Utilisation reports.
3. Export a report.
4. Compile a report and use **Send to MD**.

**Narration:**
> In this final production video, we'll look at reports.
>
> Open Reports. You have several views into how the kitchen is performing. Efficiency
> shows output against effort. Quality summarises pass and reject rates. Waste shows
> what was lost. Cost shows what production is costing. And ingredient utilisation
> shows how your raw materials are being used. Together they tell you where you're
> running well and where there's waste to cut.
>
> Any report can be exported. And when you want the Managing Director to see one,
> compile it and use "Send to Managing Director". That completes the Production videos.
