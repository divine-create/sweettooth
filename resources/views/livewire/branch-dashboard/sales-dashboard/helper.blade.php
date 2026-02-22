<div class="p-4 space-y-4">
    <x-breadcrumb
        title="Sales Helper"
        :items="[
            ['label' => 'Dashboard', 'url' => branch_route('branch-dashboard.index')],
            ['label' => 'Sales Helper']
        ]"
        :compact="false"
        :with-icons="true" />

    <div class="rounded-xl border border-blue-200 dark:border-blue-800 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950/30 dark:to-indigo-950/30 p-4">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm text-blue-700 dark:text-blue-300">Department Guide</div>
                <div class="text-xl font-semibold text-blue-900 dark:text-blue-100">
                    {{ $departmentName ?? 'Sales Department' }}
                </div>
                <div class="text-xs text-blue-700 dark:text-blue-300">
                    {{ $branchName ?? 'Branch' }} • This guide is department-specific
                </div>
            </div>
            <div class="text-right text-xs text-blue-700 dark:text-blue-300">
                Stock Opening → POS → Dispatch Receiving → Shift Closing
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">1. Stock Opening</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Capture the starting stock for your department. This unlocks POS for the day.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Button: `Verify & Save Stock Opening`</div>
                <div>Workflow: search product → set actual opening → save</div>
                <div>Notes: counts are in production/base UOM; sales UOM shown as reference where applicable</div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">2. Production Dispatch Receiving</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Receive products sent from production. These add to your available POS stock.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Button: `Receive` on each dispatch row</div>
                <div>Popup: confirm received quantity and notes</div>
                <div>Display: sales UOM shown first, base UOM shown in small text</div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">3. POS Sales</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Sell products with sales UOM. Stock is deducted in base UOM behind the scenes.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Buttons: `Add`, `Clear`, `Complete Sale`</div>
                <div>Payments: cash, transfer, POS machine</div>
                <div>Stock: shows sales UOM quantity, with base UOM in smaller text</div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">4. Table Tabs (Optional)</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Save a table tab to reserve stock and prevent double-selling.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Button: `Save Tab`</div>
                <div>Behavior: reserves stock immediately (reduces available in POS)</div>
                <div>Button: `Clear Table` releases reserved stock</div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">5. Sales → Production Requests</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Request production when sales stock is low. Requests are in sales UOM, production sees base UOM.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Button: `Create Production Request`</div>
                <div>Workflow: select product → set qty → submit</div>
                <div>Conversion: sales UOM shown, base UOM used for production and inventory</div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 p-4 space-y-3">
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">6. Shift Closing</div>
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                Reconcile sales, cash, POS, transfers, and stock variance for your department.
            </div>
            <div class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1">
                <div>Button: `Submit Closing`</div>
                <div>Workflow: enter closing counts → review variance → submit</div>
                <div>Reports: totals by product and payment method</div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-950/30 p-4">
        <div class="text-sm font-semibold text-emerald-900 dark:text-emerald-100">Key Rules</div>
        <div class="text-xs text-emerald-800 dark:text-emerald-200 space-y-1 mt-2">
            <div>Sales UOM is what staff see and sell.</div>
            <div>Base UOM is what production and inventory use.</div>
            <div>Dispatch receiving updates POS stock immediately after acceptance.</div>
            <div>Saving a table tab reserves stock to prevent double-selling.</div>
        </div>
    </div>
</div>
