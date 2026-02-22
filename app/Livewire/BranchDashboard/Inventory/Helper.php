<?php

namespace App\Livewire\BranchDashboard\Inventory;

use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

/**
 * Inventory Helper Documentation Component
 *
 * This component serves as the comprehensive documentation hub for the entire
 * Inventory Management System. It provides users with detailed guides on:
 *
 * SYSTEM MODULES:
 * - Items Management: Create, edit, delete inventory items with SKU, categories, UOM
 * - Stocks: View and adjust stock levels, manage inventory health
 * - Purchases: Create and manage purchase orders with supplier tracking
 * - Stock Movements: Track all inventory movements (in, out, transfers, adjustments)
 * - Stock Takes: Perform physical stock counts and variance tracking
 * - Item Requests: Department-based request workflow for items
 * - Item Dispatches: Approve and dispatch requested items
 * - Health Checks: Monitor and record item condition assessments
 * - Analytics: Dashboard with KPIs, trends, and actionable insights
 * - Reports: Generate and view inventory reports
 * - Shift Closing: End-of-shift stock reconciliation
 * - Callbacks: Production returns and finished product reject handling
 *
 * WORKFLOWS:
 * - Purchase-to-Stock Flow: Create PO → Approve → Receive Stock → Update Inventory
 * - Request-to-Dispatch Flow: Request → Approve → Dispatch → Update Stock
 * - Stock Adjustment Flow: Edit Stock → Audit Approval → Update Records
 * - Item Management Flow: Create/Update → Audit Approval (if not super admin) → Save
 *
 * APPROVAL SYSTEM:
 * - Non-super admins require audit approval for critical actions
 * - Audit requests are logged and routed to authorized approvers
 * - All actions are tracked with full audit trails
 *
 * @see Helper blade view for complete UI documentation
 */
#[Layout('components.layouts.app.branch-dashboard')]
class Helper extends Component
{
    use Interactions;

    public function mount()
    {
        // Any initialization if needed
    }

    public function render()
    {
        return view('livewire.branch-dashboard.inventory.helper');
    }
}
