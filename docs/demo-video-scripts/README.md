# SweetTooth ERP — Demo Video Scripts (Training)

This folder contains the narration scripts and screen-recording cues for the
SweetTooth ERP training videos. There is **one file per core role**, and each
file is split into **sections — one section equals one short demo video**.

## Files

| File | Role | Audience |
|------|------|----------|
| [01-super-admin-md.md](01-super-admin-md.md) | Super Admin / MD | System owner, business owner/director |
| [02-hr.md](02-hr.md) | HR Manager | People, shifts, leave, payroll |
| [03-accounting.md](03-accounting.md) | Accounting Manager | Finance, GL, reports |
| [04-inventory.md](04-inventory.md) | Inventory Manager | Items, purchases, stock |
| [05-production.md](05-production.md) | Production Manager | Recipes, production, kitchens |
| [06-sales.md](06-sales.md) | Sales Manager / Staff | POS, shift workflow |
| [07-reporting.md](07-reporting.md) | Reporting / MD Reports | Generate → review → compile → send |

## How each section is laid out

Every section (= one video) follows the same structure:

> ### Section N — <Title>
> **Estimated length:** ~X:XX
> **Screen / on-screen actions:** what to click and show while recording.
> **Narration:** the exact words to feed to the AI voice tool. Record the screen,
> then lay the generated audio over it.

- **Narration** blocks are written to be read aloud by a text-to-speech engine.
  Copy a narration block straight into your AI voice tool.
- **Screen / on-screen actions** are *for you, the recorder* — they are not read aloud.

## Recording & voice guidance (read once before you start)

**Voice settings**
- Use one consistent voice across every video. A warm, clear, mid-pace narrator
  works best for training. Aim for ~150 words per minute.
- Insert a short pause (the audio tool's comma/period pause) between steps so the
  viewer's eye can follow the cursor.

**Pronunciation notes for the TTS tool**
- The currency symbol "₦" is written out as **"naira"** in all narration so the
  voice reads it correctly. Numbers are written as you'd say them.
- "GL" is read as **"general ledger"** in narration; "POS" is read as
  **"point of sale"**; "WIP" is read as **"work in progress"**; "MD" is read as
  **"managing director"**; "UOM" is read as **"unit of measure"**.
- "SweetTooth" is one word, said "sweet tooth".

**Screen prep before recording**
- Log in with a demo account that has the role you're demonstrating, on the
  **SweetTooth Port Harcourt** branch.
- Use clean demo data (no half-finished test transactions on screen).
- Hide personal browser bookmarks / notifications; record at 1080p, browser zoomed
  so text is comfortably readable.
- Keep `?b_id=` in the URL — every operational screen is branch-scoped.

**Workflow gotchas to remember while recording**
- Most operational screens require an **active shift**. Clock in and select a shift
  before recording Inventory, Production, or Sales operations.
- Sales screens enforce order: **Stock Opening → POS → Shift Closing**. You cannot
  open the POS until stock opening is done — film it in that order.
- Production and Sales screens are **department-scoped** — the user only sees their
  own department's data.

## Suggested intro / outro (reuse on every video)

**Intro narration (first 5 seconds of every video):**
> "Welcome to SweetTooth ERP training. In this video, we'll look at <topic>."

**Outro narration (last 5 seconds of every video):**
> "That completes this section. In the next video, we'll cover <next topic>."
