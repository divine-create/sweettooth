# Feature Plan — Finished-Goods Stock Held in Production

**Goal:** When a product is produced, the quantity **not yet dispatched to sales stays as a held finished-goods balance in the production department**. That held balance *is* the day's closing stock and automatically becomes the next day's opening. Daily figures (Opening / Produced / Sent out / Closing) are **derived from real events**, not typed, so they're hard to fake.

Driven by the manual "pastry closing product" sheet (24-06-2026), which is exactly this: a daily per-product Opening → Produced → Sent out → Closing ledger for a production department.

---

## Why this is mostly an assembly job, not a build-from-scratch

The pieces already exist and are currently unused/partly wired:

| Piece | Exists? | Notes |
|---|---|---|
| Per-dept finished-goods balance | **Partly** | `production_store_stocks` can hold products (item_id = Product UUID). WIP output is already added here; **finished (non-WIP) output is not.** |
| Event ledger (produced / sent / adjust) | **Yes** | `production_store_movements` has `in/out/transfer/adjustment`, `quantity_before/after`, `moved_by`. |
| Daily ledger columns | **Yes** | `daily_produces`: opening/produced/sent_out/closing/expected_closing/variance + closing→opening carryover (`getOpeningQuantityFromPreviousShift`). |
| Batch records | **Yes** | `production_records`: produced/approved/sent_out/**remaining**/dispatch_status. |
| Dispatch → sales | **Yes** | `ProductDispatch` rows; sales **Stock Opening** already reads them into the shop's opening stock. |
| Produce screen | **Yes** | `QuickProduceFinishedGood` + `QuickProduceTrait` (consumes raw materials, writes records). |

**The gap:** finished goods aren't persisted as a held balance, dispatch doesn't draw that balance down, and there's no simple movement-derived daily sheet / stock-take / importer.

---

## Design (single source of truth)

- **Authoritative held balance** = `production_store_stocks` rows where `item_id` is a Product UUID, classified as **finished goods** (vs raw / WIP). One balance per product per production store. This answers "what is physically held in production right now."
- **Event log** = `production_store_movements`:
  - `in` = produced (added to held stock)
  - `out` / `transfer` = dispatched to sales (drawn down)
  - `adjustment` = physical stock-take correction
  - `return` / `damaged` = callbacks / waste
- **Daily sheet = a report over the events** (like a bank statement):
  - **Opening** = balance at start of day/shift (carried from prior closing automatically — it's a continuous balance)
  - **Produced** = Σ `in` that day
  - **Sent out** = Σ `out/transfer` that day
  - **Closing** = current balance
  - Because every figure traces to a timestamped, attributed movement, you **can't record what you didn't have**.

This keeps **one** number that matters (the held balance) and derives everything else, avoiding two-sources-of-truth drift.

---

## Phased delivery

### Phase 1 — Persist finished goods as held stock (foundation)
- Extend `QuickProduceTrait` so **non-WIP finished output** is added to `production_store_stocks` (Product row) with an `in` movement — mirroring the existing WIP branch (QuickProduceTrait.php ~lines 370–407).
- Add a **classification** to distinguish raw / WIP / finished-goods rows (a flag column or derive: numeric item_id = raw; product_id + is_wip = WIP; product_id + !is_wip = finished good).
- Surface a **"Finished Goods" tab** on the existing Production Store screen (`Production/Store/Stock.php`) alongside the current raw/WIP filters.
- **Result:** producing a finished good now leaves a visible held balance in production.

### Phase 2 — Dispatch draws down the held balance + reconciliation
- On dispatch to sales (`ProductDispatch` creation), **decrement** the finished-goods held balance and write an `out/transfer` movement.
- Add **two-sided reconciliation**: dispatched qty (production) vs received qty (sales `ProductDispatch.received_at` / shop opening). Flag mismatches as a variance needing sign-off.
- **Result:** "sent out" is a real draw-down; gaps between kitchen-sent and shop-received are caught.

### Phase 3 — Daily Finished-Goods Sheet (the screen they'll use)
- New production page **"Finished Goods" / "Daily Closing"**: pick dept + date + shift → grid of products with **Opening (auto) · Produced · Sent out · Closing**, all derived from movements; a **notes** column.
- **Print** (matches the paper sheet) + **Excel export**.
- Register route under `production` group + add to production sidebar pages.
- **Result:** staff see their paper sheet on screen, populated automatically.

### Phase 4 — Physical count / stock-take
- A "count" action: enter the real shelf count → system writes an `adjustment` movement for the difference, records a **variance + reason**, requires **supervisor approval**, then **locks** the day.
- **Result:** physical reality reconciled to the system, with every correction logged.

### Phase 5 — Historical importer
- Importer for past paper sheets (like 24-06-2026): set the held balance per product (`adjustment` movement, dated), so history isn't lost. (We did this manually for raw materials; this generalises it for finished goods with a reviewable mapping step.)

---

## Anti-fraud controls (built in, per earlier discussion)
- **Opening can't be edited** — it's the continuous carried balance.
- **Numbers derive from events**, not free typing — produced/sent/closing all trace to movements.
- **Every movement is attributed** (`moved_by_id/type`) and timestamped.
- **Locked after closing**; corrections need supervisor-approved, logged adjustments.
- **Two-sided match** (kitchen sent vs shop received) and **produced-vs-materials** cross-check flag impossible numbers.
- **Separation of duties** — producer, closer, and receiver are different logins.

---

## Key decisions to confirm before building
1. **Held-balance home:** reuse `production_store_stocks` (recommended — least footprint, consistent with WIP) vs a dedicated `finished_goods_stocks` table (cleaner separation, more work).
2. **Daily figures source:** derive from `production_store_movements` (recommended — tamper-resistant) vs also maintain `daily_produces` rows (richer but dual-write).
3. **Products without recipes** (Opera, Cinnamon Roll, etc.): finished-goods stock keys on **product**, so these work fine here — but the Produce screen is recipe-based. Decide whether such products get a minimal recipe or a "produce without recipe" path.
4. **Rollout order:** ship Phase 1–3 first (visible value), then 4–5.

---

## Out of scope (for now)
- Auto-costing/GL postings for finished-goods movements (production store is non-GL today; keep it that way unless asked).
- Expiry/shelf-life on finished goods (could reuse ProductStock's expiry logic later).
