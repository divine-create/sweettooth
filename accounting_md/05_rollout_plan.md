# 05. Rollout Plan (Safe In Production)

Use a phased rollout to avoid data corruption and downtime.

## Phase 0: Prep

1. Full MySQL backup.
2. Enable maintenance window for schema migrations.
3. Confirm open accounting period exists.
4. Freeze deployment of unrelated accounting changes.

## Phase 1: Schema

1. Run new migrations from `02_mysql_schema_alignment.md`.
2. Verify foreign keys and indexes.
3. Run data backfill scripts.

Checks:

1. no null constraint failures
2. no orphaned FK values
3. migration runtime acceptable for production data size

## Phase 2: Posting Engine Refactor

1. Unify to one posting service.
2. Update observers (`created` + `updated` flows).
3. Fix field names (`payment_time`, stock movement type fields).
4. Deploy idempotency protections.

Checks:

1. new sale posts exactly once
2. new payment posts exactly once
3. purchase approved on create posts correctly

## Phase 3: Retry/Monitoring

1. update retry action to execute posting, not only reset status
2. add accounting health dashboard cards
3. expose failure reason with next action hint

Checks:

1. forced failure can be retried to success
2. repeated retry does not create duplicate entries

## Phase 4: UX Simplification

1. release `Cash Sale` / `Invoice Sale`
2. release `Cash Purchase` / `Bill Purchase`
3. enforce status-driven actions in UI

Checks:

1. cashier can complete cash sale without GL knowledge
2. accountant can reconcile and trace each document to GL entries

## Phase 5: Historical Backfill

1. run corrected backfill command for old records
2. reconcile totals:
   1. sales total vs revenue entries
   2. receipts total vs cash/bank entries
   3. purchase total vs AP entries
3. mark unresolved records for manual review

## Rollback Strategy

1. application rollback first
2. data rollback only from backup snapshot if required
3. never destructive-reset production tables for accounting fixes

