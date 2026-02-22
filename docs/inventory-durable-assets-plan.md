## Durable Non-Production Inventory Flow

### Overview
The inventory module already has item registration, requests, and dispatches. To ensure non-production items (spoons, cups, pots, security gear, etc.) can only leave store via an approved request, we will classify them as **durable assets** and route every movement through the request/dispatch workflow. Production-only consumables (raw materials, packaging) keep their existing flow.

### Details and Improvements
1. **Item classification**
   - Add a new flag such as `usage_type` (`production`, `durable_asset`, `consumable`) or a boolean `requires_request` to `items`. This field appears in the item modal so whoever creates the item can mark it appropriately. Default to `production`/`false` so existing items behave unchanged.
   - Durable assets should include cleaners/security resident gear and the kitchen utensils that remain in production area until damaged.

2. **Request enforcement**
   - Update `ItemRequests`/`ItemDispatches` to reject items flagged `durable_asset` unless there is a pending request/dispatch cycle. Store users cannot bypass the request.
   - On the request form, filter available items to only those needing requests (durable assets) when the chosen department is non-production (cleaning, security).
   - Add request notes (or a dedicated field) that records the resident location/person, so this information is discoverable in reports or when issuing replacements.

3. **Dispatch controls**
   - In `ItemDispatches`, load the flag for each item and render it in the modal UI/logic so dispatchers know the item is a durable asset. This also powers any UI message that says “must be approved and dispatched via request.”
   - Persists the `received_by`/`received_time` fields along with dispatch notes, giving you a clear audit trail of where each copy currently resides.

4. **Audit and reporting**
   - Durable asset dispatches generate `StockMovement`/`ItemDispatch` records the same way other items do, so reports can cite “which resident utensils are outstanding” by filtering on the new flag.
   - Optionally add a dashboard filter to show only durable assets that have been dispatched and not yet replaced/damaged.

5. **Replacement workflow**
   - When a resident item breaks, the team creates another request for the same department/item and records the failure (link to prior dispatch, notes). The stock record is reduced only once the replacement dispatch is done, meaning you only consume/replenish when damage occurs.

### Todos
- [ ] Create a migration to add `usage_type` or `requires_request` to `items`. Add default value and index if filtering by it.
- [ ] Update `app/Livewire/BranchDashboard/Inventory/Items.php` + modal view to manage the new field and enforce validation.
- [ ] Adjust `ItemRequests` to filter selectable items by the new flag depending on department/type, and carry the flag through to `ItemRequestDetail` records.
- [ ] Update `ItemDispatches` logic/UI to honor the new flag (e.g., block direct dispatch, show warning) and ensure dispatch/audit notes capture resident info.
- [ ] Add or update documentation/screenshots explaining how to request durable assets and interpret the dispatch history.

This document captures the scope for locking durable assets behind a request and keeping every movement in the inventory audit trail.
