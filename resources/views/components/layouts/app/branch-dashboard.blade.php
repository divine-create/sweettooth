<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen">
    <!-- Universal Loading Indicator -->
    <div x-data="{
            loading: false,
            timeout: null,
            activeRequests: 0
         }"
         x-init="
            Livewire.hook('commit', ({ succeed, fail }) => {
                activeRequests++;
                clearTimeout(timeout);
                timeout = setTimeout(() => { if (activeRequests > 0) loading = true; }, 150);

                succeed(() => {
                    activeRequests--;
                    if (activeRequests <= 0) {
                        activeRequests = 0;
                        clearTimeout(timeout);
                        loading = false;
                    }
                });

                fail(() => {
                    activeRequests--;
                    if (activeRequests <= 0) {
                        activeRequests = 0;
                        clearTimeout(timeout);
                        loading = false;
                    }
                });
            });
         "
         x-show="loading"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-[-10px]"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-[-10px]"
         x-cloak
         class="fixed top-4 right-4 z-[9999] flex items-center gap-2 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-full px-3 py-1.5 shadow-lg">
        <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span class="text-xs font-medium text-zinc-600 dark:text-zinc-300">Loading...</span>
    </div>
    <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

        <a href="{{ branch_route('branch-dashboard.index') }}"
            class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
            <x-app-logo />
        </a>

        <div id="sidebar-scroll" x-data="sidebarScroll()" x-init="init()" 
             @scroll.debounce.150ms="save($event)" class="max-h-[calc(100vh-6rem)] overflow-y-auto pr-2 sidebar-scroll-hide">
        <flux:navlist variant="outline">

            @php
                $currentUser = function_exists('get_user_auth')
                    ? get_user_auth()
                    : auth()->user();
                // Ensure currentUser is a User object, not a string
                if ($currentUser && is_string($currentUser)) {
                    $currentUser = \App\Models\User::find($currentUser);
                }
                $isSuperAdmin = is_super_admin();
                $sidebarService = \App\Services\SidebarVisibilityService::class;
                $isAdmin = $sidebarService::isAdmin($currentUser);
                // HR check: use permissions so any role with HR permissions qualifies
                $isHrRole = $currentUser && !$isSuperAdmin && !$isAdmin && (
                    $currentUser->hasRole(['HR', 'HR Manager', 'HR Officer'])
                    || $currentUser->can('manage-leave')
                    || $currentUser->can('manage-payroll')
                    || $currentUser->can('manage-employees')
                );
                $isHrOnly = $isHrRole && !$isSuperAdmin && !$isAdmin
                    && !$currentUser->can('view-sales')
                    && !$currentUser->can('view-inventory')
                    && !$currentUser->can('view-production')
                    && !$currentUser->can('view-accounting');

            @endphp

            {{-- ==================== GO-LIVE: OPENING STOCK (temporary) ==================== --}}
            @if (function_exists('opening_stock_entry_enabled') && opening_stock_entry_enabled()
                && ($isSuperAdmin || ($currentUser && ($currentUser->can('manage-opening-stock') || $currentUser->can('manage-system')))))
                <flux:navlist.item icon="rocket-launch" :href="branch_route('branch-dashboard.go-live.opening-stock')"
                    :current="request()->routeIs('branch-dashboard.go-live.*')" wire:navigate
                    class="text-teal-700 dark:text-teal-400 font-semibold">
                    {{ __('Go-Live: Opening Stock') }}
                </flux:navlist.item>
            @endif

            {{-- ==================== ADMINISTRATION (SUPER ADMIN ONLY) ==================== --}}
            @if (($sidebarService::canSeeAdministration($currentUser) || $sidebarService::isSuperAdmin()) && !$isHrOnly)
            <flux:navlist.group :heading="__('Administration')" icon="cog-6-tooth">
                <flux:navlist.item icon="shield-check" :href="branch_route('branch-dashboard.roles.index')"
                    :current="request()->routeIs('branch-dashboard.roles.*')" wire:navigate>
                    {{ __('Roles & Permissions') }}
                </flux:navlist.item>

                <flux:navlist.group :heading="__('Branch Management')" expandable
                    :expanded="request()->routeIs('branch-dashboard.branches.*')" class="grid">
                    <flux:navlist.item icon="building-office-2" :href="branch_route('branch-dashboard.branches.index')"
                        :current="request()->routeIs('branch-dashboard.branches.index')" wire:navigate>
                        {{ __('All Branches') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="building-office-2" :href="branch_route('branch-dashboard.branches.deleted')"
                        :current="request()->routeIs('branch-dashboard.branches.deleted')" wire:navigate>
                        {{ __('Deleted Branches') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                <flux:navlist.item icon="document-text" :href="branch_route('branch-dashboard.md-reports.dashboard')"
                    :current="request()->routeIs('branch-dashboard.md-reports.*')" wire:navigate>
                    {{ __('Managing Director Reports') }}
                </flux:navlist.item>

                <flux:navlist.item icon="cog" :href="branch_route('branch-dashboard.settings.index')"
                    :current="request()->routeIs('branch-dashboard.settings.index')" wire:navigate>
                    {{ __('System Settings') }}
                </flux:navlist.item>

                <flux:navlist.item icon="cog" :href="branch_route('branch-dashboard.audit.index')"
                :current="request()->routeIs('branch-dashboard.audit.index')" wire:navigate>
                {{ __('Audit Management') }}
                </flux:navlist.item>

                @if ($isSuperAdmin)
                <flux:navlist.item icon="eye" :href="branch_route('branch-dashboard.audit.user-activity')"
                    :current="request()->routeIs('branch-dashboard.audit.user-activity')" wire:navigate>
                    {{ __('User Activity Monitor') }}
                </flux:navlist.item>
                @endif
            </flux:navlist.group>
            @endif
            @if ($isHrOnly)
            <flux:navlist.group :heading="__('Audit')" class="grid">
                <flux:navlist.item icon="cog" :href="branch_route('branch-dashboard.audit.index')"
                    :current="request()->routeIs('branch-dashboard.audit.index')" wire:navigate>
                    {{ __('Audit Management') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif

            {{-- ==================== SHIFT MANAGEMENT FOR HR STAFF AND SUPER ADMINS ==================== --}}
            @if ($isHrRole || $isSuperAdmin || ($currentUser && ($currentUser->can('manage-staff-schedule') || $currentUser->can('manage-organization'))))
            <flux:navlist.group :heading="__('Shift Management')" expandable
                :expanded="request()->routeIs('branch-dashboard.shift-management.*')" class="grid">
                <flux:navlist.item icon="clock" :href="branch_route('branch-dashboard.shift-management.index')"
                    :current="request()->routeIs('branch-dashboard.shift-management.index')" wire:navigate>
                    {{ __('Shift Dashboard') }}
                </flux:navlist.item>
                <flux:navlist.item icon="cog" :href="branch_route('branch-dashboard.shift-management.configuration')"
                    :current="request()->routeIs('branch-dashboard.shift-management.configuration')" wire:navigate>
                    {{ __('Shift Configuration') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif
            {{-- ==================== END SHIFT MANAGEMENT ==================== --}}

            @if ($sidebarService::canSeeOrganization($currentUser) || $sidebarService::isSuperAdmin() || $isHrRole)
            <flux:navlist.group :heading="__('Organization')" class="grid">
                @if ($sidebarService::canSeeDepartments($currentUser) || $isHrRole)
                <flux:navlist.group :heading="__('Organization')" expandable
                    :expanded="request()->routeIs('branch-dashboard.branch.departments.*')" class="grid">
                    <flux:navlist.item icon="tag"
                        :href="branch_route('branch-dashboard.branch.departments.category')"
                        :current="request()->routeIs('branch-dashboard.branch.departments.category')" wire:navigate>
                        {{ __('Department Categories') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="building-storefront"
                        :href="branch_route('branch-dashboard.branch.departments.index')"
                        :current="request()->routeIs('branch-dashboard.branch.departments.index')" wire:navigate>
                        {{ __('Departments') }}
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif

                <flux:navlist.group :heading="__('Units & Measure')" expandable
                    :expanded="request()->routeIs('branch-dashboard.organization.uom.*')" class="grid">
                    <flux:navlist.item icon="square-3-stack-3d"
                        :href="branch_route('branch-dashboard.organization.uom.index')"
                        :current="request()->routeIs('branch-dashboard.organization.uom.index')" wire:navigate>
                        {{ __('UOM Overview') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="queue-list"
                        :href="branch_route('branch-dashboard.organization.uom.units')"
                        :current="request()->routeIs('branch-dashboard.organization.uom.units')" wire:navigate>
                        {{ __('Units Catalog') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="arrows-right-left"
                        :href="branch_route('branch-dashboard.organization.uom.conversions')"
                        :current="request()->routeIs('branch-dashboard.organization.uom.conversions')" wire:navigate>
                        {{ __('Conversion Rules') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth"
                        :href="branch_route('branch-dashboard.organization.uom.manage')"
                        :current="request()->routeIs('branch-dashboard.organization.uom.manage')" wire:navigate>
                        {{ __('Manage Rules') }}
                    </flux:navlist.item>
                </flux:navlist.group>

                @if ($sidebarService::canSeeEmployeeManagement($currentUser) || $sidebarService::isSuperAdmin() || $isHrRole)
                <flux:navlist.group :heading="__('Employee Management')" expandable
                    :expanded="request()->routeIs('branch-dashboard.employees.*') || request()->routeIs('branch-dashboard.assignments.*') || request()->routeIs('branch-dashboard.clock-in-board.*')"
                    class="grid" icon='users'>
                    <flux:navlist.item icon="user" :href="branch_route('branch-dashboard.employee.index')"
                    :current="request()->routeIs('branch-dashboard.employee.index')" wire:navigate>
                        {{ __('All Employees') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="user-plus" :href="branch_route('branch-dashboard.employee.create')"
                        :current="request()->routeIs('branch-dashboard.employee.create')" wire:navigate>
                        {{ __('Create Employee') }}
                    </flux:navlist.item>

                    <flux:navlist.group :heading="__('Clock-In Board')" expandable
                        :expanded="request()->routeIs('branch-dashboard.clock-in-board.*')" class="grid" icon='clock'>
                        <flux:navlist.item icon="calendar-days" :href="branch_route('branch-dashboard.clock-in-board.today')"
                            :current="request()->routeIs('branch-dashboard.clock-in-board.today')" wire:navigate>
                            {{ __('Today') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="calendar" :href="branch_route('branch-dashboard.clock-in-board.all')"
                            :current="request()->routeIs('branch-dashboard.clock-in-board.all')" wire:navigate>
                            {{ __('All Employees (Date Range)') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    {{--   @if ($sidebarService::canSeeRolesPermissions($currentUser))
                    <flux:navlist.item icon="user-plus" :href="branch_route('branch-dashboard.role-permission')"
                        :current="request()->routeIs('branch-dashboard.employee.role-permission')" wire:navigate>
                        {{ __('Roles') }}
                    </flux:navlist.item>
                    @endif
                    @if ($sidebarService::canSeeRoleAssignments($currentUser))
                     <flux:navlist.item icon="user-plus" :href="branch_route('branch-dashboard.role-assignments.index')"
                         :current="request()->routeIs('branch-dashboard.role-assignments.index')" wire:navigate>
                         {{ __('Assign Roles') }}
                     </flux:navlist.item>
                     @endif --}}

                     <flux:navlist.item icon="clipboard-document-list" :href="branch_route('branch-dashboard.employee-appraisals')"
                         :current="request()->routeIs('branch-dashboard.employee-appraisals')" wire:navigate>
                         {{ __('Employee Appraisals') }}
                     </flux:navlist.item>

                     <flux:navlist.item icon="calendar" :href="branch_route('branch-dashboard.hr.appraisals.cycles')"
                         :current="request()->routeIs('branch-dashboard.hr.appraisals.cycles')" wire:navigate>
                         {{ __('Appraisal Cycles') }}
                     </flux:navlist.item>

                     <flux:navlist.item icon="chart-bar" :href="branch_route('branch-dashboard.hr.reports.index')"
                         :current="request()->routeIs('branch-dashboard.hr.reports.*')" wire:navigate>
                         {{ __('HR Reports') }}
                     </flux:navlist.item>


                    {{-- //role-assignments.index --}}
              </flux:navlist.group>
            @endif

            {{-- ==================== ORGANIZATION HELPER ==================== --}}
              <flux:navlist.item icon="question-mark-circle" :href="branch_route('branch-dashboard.organization.helper')"
                  :current="request()->routeIs('branch-dashboard.organization.helper')" wire:navigate>
                  {{ __('Helper') }}
              </flux:navlist.item>
            </flux:navlist.group>
            @endif
            {{-- ==================== END ORGANIZATION ==================== --}}

            {{-- ==================== LEAVE MANAGEMENT ==================== --}}
            <flux:navlist.group :heading="__('Leave Management')" class="grid" icon="calendar">
                <flux:navlist.item icon="calendar-days" :href="branch_route('leave.apply')"
                    :current="request()->routeIs('leave.apply')" wire:navigate>
                    {{ __('Apply Leave') }}
                </flux:navlist.item>
                <flux:navlist.item icon="clipboard-document-list"
                    :href="branch_route('leave.my-leaves')"
                    :current="request()->routeIs('leave.my-leaves')" wire:navigate>
                    {{ __('My Leaves') }}
                </flux:navlist.item>
                <flux:navlist.item icon="chart-pie" :href="branch_route('leave.balance')"
                    :current="request()->routeIs('leave.balance')" wire:navigate>
                    {{ __('Leave Balance') }}
                </flux:navlist.item>
                @if ($isSuperAdmin || $isAdmin || $currentUser?->can('manage-leave'))
                    <flux:navlist.item icon="clipboard-document-check"
                        :href="branch_route('branch-dashboard.leave.approve')"
                        :current="request()->routeIs('branch-dashboard.leave.approve')" wire:navigate>
                        {{ __('Approve Leaves') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth" :href="branch_route('branch-dashboard.leave.types')"
                        :current="request()->routeIs('branch-dashboard.leave.types')" wire:navigate>
                        {{ __('Leave Types') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="calendar-days" :href="branch_route('branch-dashboard.leave.manage-allocations')"
                        :current="request()->routeIs('branch-dashboard.leave.manage-allocations')" wire:navigate>
                        {{ __('Leave Allocations') }}
                    </flux:navlist.item>
                @endif
            </flux:navlist.group>
            {{-- ==================== END LEAVE MANAGEMENT ==================== --}}

            {{-- ==================== PAYROLL ==================== --}}
            @if ($isSuperAdmin || $isAdmin || $isHrRole || ($currentUser && ($currentUser->can('manage-payroll') || $currentUser->can('access_accounting'))))
            <flux:navlist.group :heading="__('Payroll')" icon="banknotes" expandable
                :expanded="request()->routeIs('branch-dashboard.accounting.payroll.*')" class="grid">
                <flux:navlist.item icon="list-bullet" :href="branch_route('branch-dashboard.accounting.payroll.index')"
                    :current="request()->routeIs('branch-dashboard.accounting.payroll.index')" wire:navigate>
                    {{ __('All Payroll') }}
                </flux:navlist.item>
                <flux:navlist.item icon="user-group" :href="branch_route('branch-dashboard.accounting.payroll.run.create')"
                    :current="request()->routeIs('branch-dashboard.accounting.payroll.run.create')" wire:navigate>
                    {{ __('New Payroll Run') }}
                </flux:navlist.item>
                <flux:navlist.item icon="document-plus" :href="branch_route('branch-dashboard.accounting.payroll.payslip.create')"
                    :current="request()->routeIs('branch-dashboard.accounting.payroll.payslip.create')" wire:navigate>
                    {{ __('New Payslip') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif
            {{-- ==================== END PAYROLL ==================== --}}

            {{-- ==================== ACCOUNTING DASHBOARD ==================== --}}
            @if (!$isHrOnly && $sidebarService::canSeeAccounting($currentUser))
            <flux:navlist.group :heading="__('Accounting')" icon="currency-dollar" expandable
                :expanded="request()->routeIs('branch-dashboard.accounting.*')" class="grid">
                <flux:navlist.item icon="chart-bar" :href="branch_route('branch-dashboard.accounting.dashboard')"
                    :current="request()->routeIs('branch-dashboard.accounting.dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navlist.item>

                <flux:navlist.item icon="eye" :href="branch_route('branch-dashboard.accounting.overview')"
                    :current="request()->routeIs('branch-dashboard.accounting.overview')" wire:navigate>
                    {{ __('Overview') }}
                </flux:navlist.item>
                
                @if ($currentUser->hasAnyRole(['Super Admin', 'MD', 'Managing Director', 'Admin', 'Accounting Manager']) || $sidebarService::isSuperAdmin())
                <flux:navlist.item icon="book-open" :href="branch_route('branch-dashboard.accounting.accounts')"
                    :current="request()->routeIs('branch-dashboard.accounting.accounts')" wire:navigate>
                    {{ __('Chart of Accounts') }}
                </flux:navlist.item>

                <flux:navlist.item icon="calendar" :href="branch_route('branch-dashboard.accounting.periods')"
                    :current="request()->routeIs('branch-dashboard.accounting.periods')" wire:navigate>
                    {{ __('Accounting Periods') }}
                </flux:navlist.item>
                @endif

                @if (
                    $currentUser->hasAnyRole(['Super Admin', 'MD', 'Managing Director', 'Admin', 'Accountant', 'Accounting Manager'])
                    || $currentUser->can('view_bank_accounts')
                    || $currentUser->can('create_bank_accounts')
                    || $currentUser->can('edit_bank_accounts')
                    || $sidebarService::isSuperAdmin()
                )
                <flux:navlist.item icon="building-library" :href="branch_route('branch-dashboard.accounting.bank-accounts')"
                    :current="request()->routeIs('branch-dashboard.accounting.bank-accounts')" wire:navigate>
                    {{ __('Bank Accounts') }}
                </flux:navlist.item>
                @endif
                
                @if ($currentUser->hasAnyRole(['Super Admin', 'MD', 'Managing Director', 'Admin', 'Accountant', 'Accounting Manager'])  || $sidebarService::isSuperAdmin())
                <flux:navlist.item icon="document-plus" :href="branch_route('branch-dashboard.accounting.journal-entry')"
                    :current="request()->routeIs('branch-dashboard.accounting.journal-entry')" wire:navigate>
                    {{ __('Journal Entries') }}
                </flux:navlist.item>
                <flux:navlist.item icon="banknotes" :href="branch_route('branch-dashboard.accounting.quick-expense')"
                    :current="request()->routeIs('branch-dashboard.accounting.quick-expense', 'branch-dashboard.accounting.journal-entry')" wire:navigate>
                    {{ __('Quick Expense') }}
                </flux:navlist.item>

                <flux:navlist.item icon="rectangle-stack" :href="branch_route('branch-dashboard.accounting.posting-status')"
                    :current="request()->routeIs('branch-dashboard.accounting.posting-status')" wire:navigate>
                    {{ __('Posting Status') }}
                </flux:navlist.item>

                <flux:navlist.item icon="currency-dollar" :href="branch_route('branch-dashboard.accounting.bank-reconciliation')"
                    :current="request()->routeIs('branch-dashboard.accounting.bank-reconciliation')" wire:navigate>
                    {{ __('Bank Reconciliation') }}
                </flux:navlist.item>

                <flux:navlist.item icon="arrow-up-tray" :href="branch_route('branch-dashboard.accounting.bank-statement-import')"
                    :current="request()->routeIs('branch-dashboard.accounting.bank-statement-import')" wire:navigate>
                    {{ __('Import Bank Statement') }}
                </flux:navlist.item>

                <flux:navlist.item icon="scale" :href="branch_route('branch-dashboard.accounting.inventory-valuation')"
                    :current="request()->routeIs('branch-dashboard.accounting.inventory-valuation')" wire:navigate>
                    {{ __('Inventory Valuation') }}
                </flux:navlist.item>

                <flux:navlist.item icon="home-modern" :href="branch_route('branch-dashboard.accounting.fixed-assets')"
                    :current="request()->routeIs('branch-dashboard.accounting.fixed-assets')" wire:navigate>
                    {{ __('Fixed Assets') }}
                </flux:navlist.item>

                <flux:navlist.item icon="chart-bar" :href="branch_route('branch-dashboard.accounting.budgets')"
                    :current="request()->routeIs('branch-dashboard.accounting.budgets')" wire:navigate>
                    {{ __('Budgets') }}
                </flux:navlist.item>
                @endif

                @php
                    $canSeePurchasePayments = $currentUser->can('view-purchase-payments');
                    $canSeeTaxPayments      = $currentUser->can('view-tax-payments');
                    $canSeeExpenseEntries   = $currentUser->can('view-expense-entries');
                    $canSeeExpenseImports   = $currentUser->can('view-expense-imports');
                    $canSeePosRemittances   = $currentUser->can('view-pos-remittances');
                    $canSeeAnyTransaction   = $canSeePurchasePayments || $canSeeTaxPayments || $canSeeExpenseEntries || $canSeeExpenseImports || $canSeePosRemittances;
                @endphp
                @if ($isSuperAdmin || $currentUser->hasRole('Accounting Manager') || $currentUser->hasRole('Accountant'))
                <flux:navlist.group :heading="__('Notes')" expandable
                    :expanded="request()->routeIs('branch-dashboard.accounting.credit-notes', 'branch-dashboard.accounting.debit-notes')" class="grid">
                    <flux:navlist.item icon="document-minus" :href="branch_route('branch-dashboard.accounting.credit-notes')"
                        :current="request()->routeIs('branch-dashboard.accounting.credit-notes')" wire:navigate>
                        {{ __('Credit Notes') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-plus" :href="branch_route('branch-dashboard.accounting.debit-notes')"
                        :current="request()->routeIs('branch-dashboard.accounting.debit-notes')" wire:navigate>
                        {{ __('Debit Notes') }}
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif

                @if ($canSeeAnyTransaction || $isSuperAdmin)
                <flux:navlist.group :heading="__('Transactions')" expandable
                    :expanded="request()->routeIs('branch-dashboard.accounting.purchase-payments', 'branch-dashboard.accounting.tax-payments', 'branch-dashboard.accounting.accounting-entries', 'branch-dashboard.accounting.expense-imports', 'branch-dashboard.accounting.pos-remittances')" class="grid">
                    @if ($canSeePurchasePayments || $isSuperAdmin)
                    <flux:navlist.item icon="credit-card" :href="branch_route('branch-dashboard.accounting.purchase-payments')"
                        :current="request()->routeIs('branch-dashboard.accounting.purchase-payments')" wire:navigate>
                        {{ __('Purchase Payments') }}
                    </flux:navlist.item>
                    @endif
                    @if ($canSeeTaxPayments || $isSuperAdmin)
                    <flux:navlist.item icon="receipt-percent" :href="branch_route('branch-dashboard.accounting.tax-payments')"
                        :current="request()->routeIs('branch-dashboard.accounting.tax-payments')" wire:navigate>
                        {{ __('Tax Payments') }}
                    </flux:navlist.item>
                    @endif
                    @if ($canSeeExpenseEntries || $isSuperAdmin)
                    <flux:navlist.item icon="document-text" :href="branch_route('branch-dashboard.accounting.accounting-entries')"
                        :current="request()->routeIs('branch-dashboard.accounting.accounting-entries')" wire:navigate>
                        {{ __('Expense Entries') }}
                    </flux:navlist.item>
                    @endif
                    @if ($canSeeExpenseImports || $isSuperAdmin)
                    <flux:navlist.item icon="arrow-up-tray" :href="branch_route('branch-dashboard.accounting.expense-imports')"
                        :current="request()->routeIs('branch-dashboard.accounting.expense-imports')" wire:navigate>
                        {{ __('Expense Imports') }}
                    </flux:navlist.item>
                    @endif
                    @if ($canSeePosRemittances || $isSuperAdmin)
                    <flux:navlist.item icon="banknotes" :href="branch_route('branch-dashboard.accounting.pos-remittances')"
                        :current="request()->routeIs('branch-dashboard.accounting.pos-remittances')" wire:navigate>
                        {{ __('POS Remittances') }}
                    </flux:navlist.item>
                    @endif
                </flux:navlist.group>
                @endif

                <flux:navlist.group :heading="__('Reports')" expandable
                    :expanded="request()->routeIs('branch-dashboard.accounting.reports.*')" class="grid">
                    <flux:navlist.item icon="list-bullet" :href="branch_route('branch-dashboard.accounting.reports.general-ledger')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.general-ledger')" wire:navigate>
                        {{ __('General Ledger') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="squares-2x2" :href="branch_route('branch-dashboard.accounting.reports.trial-balance')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.trial-balance')" wire:navigate>
                        {{ __('Trial Balance') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="chart-pie" :href="branch_route('branch-dashboard.accounting.reports.income-statement')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.income-statement')" wire:navigate>
                        {{ __('Income Statement') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="rectangle-group" :href="branch_route('branch-dashboard.accounting.reports.balance-sheet')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.balance-sheet')" wire:navigate>
                        {{ __('Balance Sheet') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="chart-bar-square" :href="branch_route('branch-dashboard.accounting.reports.cash-flow-statement')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.cash-flow-statement')" wire:navigate>
                        {{ __('Cash Flow') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="presentation-chart-bar" :href="branch_route('branch-dashboard.accounting.reports.budget-vs-actual')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.budget-vs-actual')" wire:navigate>
                        {{ __('Budget vs Actual') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="clock" :href="branch_route('branch-dashboard.accounting.reports.ap-aging')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.ap-aging')" wire:navigate>
                        {{ __('AP Aging') }}
                    </flux:navlist.item>
                    @if ($isSuperAdmin || $isAdmin)
                    <flux:navlist.item icon="building-office-2" :href="branch_route('branch-dashboard.accounting.reports.branch-comparison')"
                        :current="request()->routeIs('branch-dashboard.accounting.reports.branch-comparison')" wire:navigate>
                        {{ __('Branch Comparison') }}
                    </flux:navlist.item>
                    @endif
                </flux:navlist.group>

            </flux:navlist.group>
            @endif
            {{-- ==================== END ACCOUNTING DASHBOARD ==================== --}}

@if (!$isHrOnly && ($sidebarService::canSeeInventoryDashboard($currentUser) || $sidebarService::isSuperAdmin()))
            <flux:navlist.group :heading="__('Inventory')" icon='cube'>
                @if($sidebarService::canSeeInventory($currentUser))
                 <flux:navlist.group :heading="__('Inventory Management')" expandable
                     :expanded="request()->routeIs('branch-dashboard.inventory.*')" class="grid" icon='cube'>
                    <flux:navlist.item icon="squares-2x2" :href="branch_route('branch-dashboard.inventory.items')"
                        :current="request()->routeIs('branch-dashboard.inventory.items')" wire:navigate>
                        {{ __('Items') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="tag" :href="branch_route('branch-dashboard.inventory.item-categories')"
                        :current="request()->routeIs('branch-dashboard.inventory.item-categories')" wire:navigate>
                        {{ __('Item Categories') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="truck" :href="branch_route('branch-dashboard.inventory.purchases')"
                        :current="request()->routeIs('branch-dashboard.inventory.purchases')" wire:navigate>
                        {{ __('Purchases') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cube-transparent"
                        :href="branch_route('branch-dashboard.inventory.stocks')"
                        :current="request()->routeIs('branch-dashboard.inventory.stocks')" wire:navigate>
                        {{ __('Stock Levels') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="cube-transparent"
                        :href="branch_route('branch-dashboard.inventory.health-checks')"
                        :current="request()->routeIs('branch-dashboard.inventory.health-checks')" wire:navigate>
                        {{ __('Health Check') }}
                    </flux:navlist.item>

                      <flux:navlist.item icon="cube"
                          :href="branch_route('branch-dashboard.inventory.material-requests')"
                          :current="request()->routeIs('branch-dashboard.inventory.material-requests')" wire:navigate>
                          {{ __('Material Requests') }}
                      </flux:navlist.item>
                      <flux:navlist.item icon="check-circle"
                          :href="branch_route('branch-dashboard.inventory.material-approvals')"
                          :current="request()->routeIs('branch-dashboard.inventory.material-approvals')" wire:navigate>
                          {{ __('Material Approvals') }}
                      </flux:navlist.item>

                      <flux:navlist.item icon="question-mark-circle"
                         :href="branch_route('branch-dashboard.inventory.helper')"
                         :current="request()->routeIs('branch-dashboard.inventory.helper')" wire:navigate>
                         {{ __('Helper') }}
                     </flux:navlist.item>

                     @if($sidebarService::canSeeSuppliers($currentUser))
                     <flux:navlist.item icon="building-storefront"
                         :href="branch_route('branch-dashboard.suppliers.index')"
                         :current="request()->routeIs('branch-dashboard.suppliers.*')" wire:navigate>
                         {{ __('Suppliers') }}
                     </flux:navlist.item>
                     @endif

                 </flux:navlist.group>
                @endif

                {{-- Inventory Reports removed as requested --}}

                @if($sidebarService::canSeeInventory($currentUser))
                 <flux:navlist.group :heading="__('Material Returns')" class="grid" expandable
                     :expanded="request()->routeIs('branch-dashboard.inventory.callbacks.*')">
                    <flux:navlist.item icon="arrow-path"
                        :href="branch_route('branch-dashboard.inventory.callbacks.index')"
                        :current="request()->routeIs('branch-dashboard.inventory.callbacks.index')" wire:navigate>
                        {{ __('Production Returns') }}
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif
            </flux:navlist.group>
            @endif

            @if (!$isHrOnly && $sidebarService::canSeeInventory($currentUser))
             <flux:navlist.group :heading="__('Analytics')" expandable
                 :expanded="request()->routeIs('branch-dashboard.analytics.*')" class="grid" icon='chart-bar-square'>
                <flux:navlist.item icon="squares-plus" :href="branch_route('branch-dashboard.analytics.overview')"
                    :current="request()->routeIs('branch-dashboard.analytics.overview')">
                    {{ __('Overview Dashboard') }}
                </flux:navlist.item>
                <flux:navlist.item icon="cube" :href="branch_route('branch-dashboard.analytics.stock-level')"
                    :current="request()->routeIs('branch-dashboard.analytics.stock-level')" wire:navigate>
                    {{ __('Stock Levels') }}
                </flux:navlist.item>
                <flux:navlist.item icon="arrows-right-left"
                    :href="branch_route('branch-dashboard.analytics.stock-movement')"
                    :current="request()->routeIs('branch-dashboard.analytics.stock-movement')">
                    {{ __('Stock Movement') }}
                </flux:navlist.item>
                <flux:navlist.item icon="shopping-cart" :href="branch_route('branch-dashboard.analytics.purchase')"
                    :current="request()->routeIs('branch-dashboard.analytics.purchase')">
                    {{ __('Purchases') }}
                </flux:navlist.item>
                <flux:navlist.item icon="currency-dollar"
                    :href="branch_route('branch-dashboard.analytics.stock-valuation')"
                    :current="request()->routeIs('branch-dashboard.analytics.stock-valuation')">
                    {{ __('Stock Valuation') }}
                </flux:navlist.item>
                <flux:navlist.item icon="chart-bar"
                    :href="branch_route('branch-dashboard.analytics.item-usage')"
                    :current="request()->routeIs('branch-dashboard.analytics.item-usage')">
                    {{ __('Item Usage') }}
                </flux:navlist.item>
                <flux:navlist.item icon="bell-alert" :href="branch_route('branch-dashboard.analytics.alerts')"
                    :current="request()->routeIs('branch-dashboard.analytics.alerts')">
                    {{ __('Alerts Dashboard') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif

             

            {{-- ==================== DYNAMIC PRODUCTION MENU ==================== --}}
            @php
                $employee = \Illuminate\Support\Facades\Auth::user();
                // Safety check: ensure employee is a User object
                if ($employee && is_string($employee)) {
                    $employee = null;
                }
                // Reload to ensure department and roles relationships are loaded
                if ($employee && method_exists($employee, 'load')) {
                    $employee->load('department', 'roles');
                }
                $branchId = request()->get('b_id');
                $departments = collect();
                $OPEN_PRODUCTION = false;
                $OPEN_DEPT = null;

                // For Super Admin, always show production departments
                $forceShowProduction = $isSuperAdmin;

                // Get user's department for filtering
                $userDepartment = $employee?->department;

                // Production roles that can see all departments
                $adminProductionRoles = ['Head of Production'];

                // Department-specific production roles
                $departmentRestrictedRoles = [
                    'Production Supervisor',
                    'Production Staff',
                ];

                $adminSalesRoles = ['Sales Manager'];

                // Department-specific sales roles
                $departmentRestrictedSalesRoles = [
                    'Sales Supervisor',
                    'Sales Staff',
                ];
            @endphp

            @if (!$isHrOnly && $sidebarService::canSeeProduction($currentUser))
                @php
                if ($branchId || $forceShowProduction) {
                    $query = \App\Models\Department::with([
                        'category',
                        'pages' => fn($q) => $q->where('is_active', true)->orderBy('order')->orderBy('name'),
                    ]);

                    // For Super Admin, show all production departments across all branches
                    if ($forceShowProduction) {
                        $query->whereHas('category', fn($q) => $q->where('name', 'Production'));
                    } else {
                        // For regular users, filter by branch
                        $query->where(fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
                              ->whereHas('category', fn($q) => $q->where('name', 'Production'));
                    }

                    $departments = $query->get();

                    // Filter departments based on user role
                    if (!$sidebarService::isProductionAdminRole($currentUser)) {
                        if ($userDepartment) {
                            $departments = $departments->filter(fn($d) => $d->id === $userDepartment->id);
                        } else {
                            $departments = collect();
                        }
                    }

                    $departments = $departments->map(function ($dept) {
                        $dept->pages = $dept->pages->reject(
                            fn($p) => str_contains($p->route_name, 'edit') ||
                                str_contains($p->route_name, 'detail') ||
                                str_contains($p->route_name, 'shift-closing'),
                        );
                        return $dept;
                    });

                    $currentRoute = request()->route()?->getName();
                    $OPEN_DEPT = $departments->firstWhere(
                        fn($d) => $d->pages->pluck('route_name')->contains($currentRoute),
                    )?->id;

                    $nonDepartmentRoutes = [
                        'branch-dashboard.production.material-returns.create',
                        'branch-dashboard.production.material-returns.history',
                        'branch-dashboard.production.customer-orders.index',
                    ];                    
                    $isProductionRoute = in_array($currentRoute, $nonDepartmentRoutes);

                    $OPEN_PRODUCTION = ($departments->isNotEmpty() || $OPEN_DEPT !== null || $isProductionRoute);
                }
            @endphp
            @endif

            @if ($OPEN_PRODUCTION)
            <flux:navlist.group :heading="__('Production')" icon="o-cog-6-tooth">
                @forelse($departments as $dept)
                    <flux:navlist.group :heading="$dept->name" :badge="$dept->category?->name" expandable
                        :expanded="(request()->get('dept_slug') == $dept->slug) ? true : false" class="grid">
                        <flux:navlist.item icon="clipboard-document-check"
                            :href="branch_route('branch-dashboard.production.sales-requests.workflow', [
                                'deptSlug' => $dept->slug,
                                'dept_slug' => $dept->slug,
                                'page' => 'Sales Requests Review' . '_' . $dept->slug
                            ])"
                            :current="request()->routeIs('branch-dashboard.production.sales-requests.*')
                                && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)"
                            wire:navigate>
                            {{ __('Sales Requests Review') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="document-chart-bar"
                            :href="branch_route('branch-dashboard.production.records.index', [
                                'deptSlug' => $dept->slug,
                                'dept_slug' => $dept->slug,
                                'page' => 'Production Records' . '_' . $dept->slug
                            ])"
                            :current="request()->routeIs('branch-dashboard.production.records.*')
                                && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)"
                            wire:navigate>
                            {{ __('Production Records') }}
                        </flux:navlist.item>

@forelse($dept->pages as $page)
                            <flux:navlist.item icon="{{ $page->icon ?? 'o-beaker' }}"
                                :href="branch_route($page->route_name, [
                                    'deptSlug' => $dept->slug,
                                    'dept_slug' => $dept->slug,
                                    'page' => $page->name . '_' . $dept->slug
                                ])"
                                :current="request()->get('page') === $page->name . '_' . $dept->slug" wire:navigate>
                                {{ $page->name }}
                            </flux:navlist.item>
                        @empty
                            <div class="pl-10 pr-4 py-1.5 text-xs text-gray-500 italic">
                                {{ __('No pages configured') }}
                            </div>
                        @endforelse

                        <flux:navlist.item icon="cube"
                            :href="branch_route('branch-dashboard.production.store.stock', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.store.*')" wire:navigate>
                            {{ __('Production Store') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="clipboard-document-list"
                            :href="branch_route('branch-dashboard.production.finished-goods.index', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.finished-goods.*')" wire:navigate>
                            {{ __('Finished Goods') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="play"
                            :href="branch_route('branch-dashboard.production.quick-produce.finished-good', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.quick-produce.finished-good.*')" wire:navigate>
                            {{ __('Produce Finished Goods') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="cog"
                            :href="branch_route('branch-dashboard.production.quick-produce.wip', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.quick-produce.wip.*')" wire:navigate>
                            {{ __('Produce WIP') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="clipboard-document-list"
                            :href="branch_route('branch-dashboard.production.material-request', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.material-request.*')" wire:navigate>
                            {{ __('Material Request') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="arrow-left-end-on-rectangle"
                            :href="branch_route('branch-dashboard.inventory.material-requests', ['b_id' => request()->query('b_id')])"
                            :current="request()->routeIs('branch-dashboard.inventory.material-requests')" wire:navigate>
                            {{ __('Request from Inventory') }}
                        </flux:navlist.item>

                        <flux:navlist.group :heading="__('Material Returns')" class="grid" expandable
                            :expanded="request()->routeIs('branch-dashboard.production.material-returns.*') && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)">
                            <flux:navlist.item icon="arrow-path"
                                :href="branch_route('branch-dashboard.production.material-returns.create', ['deptSlug' => $dept->slug])"
                                :current="request()->routeIs('branch-dashboard.production.material-returns.create') && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)"
                                wire:navigate>
                                {{ __('Return Item') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="clock"
                                :href="branch_route('branch-dashboard.production.material-returns.history', ['deptSlug' => $dept->slug])"
                                :current="request()->routeIs('branch-dashboard.production.material-returns.history') && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)"
                                wire:navigate>
                                {{ __('Return History') }}
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.item icon="arrows-right-left"
                            :href="branch_route('branch-dashboard.production.store-transfers.index', ['deptSlug' => $dept->slug])"
                            :current="request()->routeIs('branch-dashboard.production.store-transfers.*') && ((request()->route('deptSlug') ?? request()->get('deptSlug')) == $dept->slug)" wire:navigate>
                            {{ __('Store Transfers') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                @empty
                    <div class="pl-10 pr-4 py-1.5 text-xs text-gray-500 italic">
                        {{ __('No production departments') }}
                    </div>
                @endforelse

                <flux:navlist.item icon="shopping-bag"
                    :href="branch_route('branch-dashboard.production.customer-orders.index')"
                    :current="request()->routeIs('branch-dashboard.production.customer-orders.*')"
                    wire:navigate>
                    {{ __('Customer Orders') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif

            @if(!$isHrOnly && $sidebarService::canSeeProduction($currentUser))
             <flux:navlist.item icon="question-mark-circle"
                 :href="branch_route('branch-dashboard.production.helper')"
                 :current="request()->routeIs('branch-dashboard.production.helper')" wire:navigate>
                 {{ __('Production Helper') }}
             </flux:navlist.item>
             @endif

            @if(!$isHrOnly && $sidebarService::canSeeProduction($currentUser) && has_permission('view-production-reports'))
            <flux:navlist.group :heading="__('Production Reports')" class="grid" expandable
                :expanded="request()->routeIs('branch-dashboard.production.reports.*')">
                <flux:navlist.item icon="chart-bar"
                    :href="branch_route('branch-dashboard.production.reports.operations')"
                    :current="request()->routeIs('branch-dashboard.production.reports.operations')" wire:navigate>
                    {{ __('Operations Reports') }}
                </flux:navlist.item>
                <flux:navlist.item icon="presentation-chart-line"
                    :href="branch_route('branch-dashboard.production.reports.performance')"
                    :current="request()->routeIs('branch-dashboard.production.reports.performance')" wire:navigate>
                    {{ __('Performance Reports') }}
                </flux:navlist.item>
                <flux:navlist.item icon="document-text"
                    :href="branch_route('branch-dashboard.production.reporting.index')"
                    :current="request()->routeIs('branch-dashboard.production.reporting.*')" wire:navigate>
                    {{ __('Reports Library') }}
                </flux:navlist.item>
            </flux:navlist.group>
            @endif
            {{-- ==================== END PRODUCTION MENU ==================== --}}


            {{-- ==================== DYNAMIC SALES DEPARTMENTS (POS) ==================== --}}
            @php
                $salesDepartments = collect();
                $OPEN_SALES = false;
                $OPEN_SALES_DEPT = null;
                $isSalesSupervisor = $currentUser?->hasRole('Sales Supervisor') ?? false;
                $isSalesManager = $currentUser?->hasRole('Sales Manager') ?? false;
                $isSalesStaff = $currentUser?->hasRole('Sales Staff') ?? false;
                $canSeeAllSalesDepartments = $isSuperAdmin || $isAdmin || $isSalesSupervisor;
                $canSeeSalesReports = $sidebarService::isSalesAdminRole($currentUser) || $isSuperAdmin || $isAdmin;

                if (!$isHrOnly && ($branchId || $isSuperAdmin) && $sidebarService::canSeeSalesManagement($currentUser)) {
                    $excludedSalesRoutes = [
                        'branch-dashboard.sales-dashboard.stock-opening.index',
                        'branch-dashboard.sales-dashboard.dispatches.index',
                        'branch-dashboard.sales-dashboard.stock-monitor',
                        'branch-dashboard.sales-dashboard.my-sales.index',
                        'branch-dashboard.sales-dashboard.reports.sales-performance',
                        'branch-dashboard.sales-dashboard.helper',
                        'branch-dashboard.sales-dashboard.callbacks.index',
                        'branch-dashboard.sales-dashboard.callbacks.dispatch-callbacks',
                        'branch-dashboard.sales-dashboard.variance-resolution.index',
                    ];
                    $excludedSalesNames = [
                        'Stock Opening',
                        'Kitchen Dispatches',
                        'Production Dispatches',
                        'Monitor Product Stock',
                        'My Sales Dashboard',
                        'Sales Reports',
                        'Helper',
                        'Callbacks',
                        'Product Callbacks',
                        'Dispatch Callbacks',
                        'My Sales',
                        'Sales Analytics',
                    ];

                    $query = \App\Models\Department::with([
                        'category',
                        'pages' => fn($q) => $q->where('is_active', true)->orderBy('order')->orderBy('name'),
                    ]);

                    // For Super Admin, show all sales departments across all branches
                    if ($isSuperAdmin) {
                        $query->whereHas('category', fn($q) => $q->where('name', 'Sales'));
                    } else {
                        // For regular users, filter by branch
                        $query->where(fn($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'))
                              ->whereHas('category', fn($q) => $q->where('name', 'Sales'));
                    }

                    $salesDepartments = $query->get();

                    // Sales Manager and Sales Staff only see their own sales department.
                    // Sales Supervisor (and Admin/Super Admin) can see all sales departments.
                    if (!$canSeeAllSalesDepartments && $userDepartment && ($isSalesManager || $isSalesStaff)) {
                        $salesDepartments = $salesDepartments->filter(fn($d) => $d->id === $userDepartment->id);
                    }

                    $salesDepartments = $salesDepartments->map(function ($dept) use ($excludedSalesRoutes, $excludedSalesNames) {
                        $dept->pages = $dept->pages->reject(
                            fn($p) => str_contains($p->route_name, 'edit') ||
                                str_contains($p->route_name, 'detail') ||
                                str_contains($p->route_name, 'shift-closing') ||
                                in_array($p->route_name, $excludedSalesRoutes, true) ||
                                in_array($p->name, $excludedSalesNames, true),
                        );
                        return $dept;
                    });

                    $currentRoute = request()->route()?->getName();
                    $OPEN_SALES_DEPT = $salesDepartments->firstWhere(
                        fn($d) => $d->pages->pluck('route_name')->contains($currentRoute),
                    )?->id;
                    
                    // Exclude production roles from seeing sales departments
                    $OPEN_SALES = ($salesDepartments->isNotEmpty() || $OPEN_SALES_DEPT !== null);
                }
            @endphp

            @if ($OPEN_SALES)
            <flux:navlist.group :heading="__('Sales Departments')" icon="building-storefront">
                @forelse($salesDepartments as $dept)
                    <flux:navlist.group :heading="$dept->name" :badge="$dept->category?->name" expandable
                        :expanded="(request()->get('sales_dept_slug') == $dept->slug) ? true : false" class="grid">
                        <flux:navlist.item icon="clipboard-document-check"
                            :href="branch_route('branch-dashboard.sales-dashboard.stock-opening.index', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Stock Opening' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Stock Opening' . '_' . $dept->slug" wire:navigate>
                            {{ __('Stock Opening') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="clock"
                            :href="branch_route('branch-dashboard.sales-dashboard.production-requests.create', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Create Production Request' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Create Production Request' . '_' . $dept->slug" wire:navigate>
                            {{ __('Create Production Request') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="clock"
                            :href="branch_route('branch-dashboard.sales-dashboard.production-requests.workflow', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Production Requests' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Production Requests' . '_' . $dept->slug" wire:navigate>
                            {{ __('Production Requests') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="archive-box"
                            :href="branch_route('branch-dashboard.sales-dashboard.item-requests', [
                                'deptSlug' => $dept->slug,
                                'page' => 'Item Requests' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Item Requests' . '_' . $dept->slug" wire:navigate>
                            {{ __('Item Requests') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="clipboard-document-check"
                            :href="branch_route('branch-dashboard.sales-dashboard.stock-monitor', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Monitor Product Stock' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Monitor Product Stock' . '_' . $dept->slug" wire:navigate>
                            {{ __('Monitor Product Stock') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="truck"
                            :href="branch_route('branch-dashboard.sales-dashboard.dispatches.index', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Production Dispatches' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Production Dispatches' . '_' . $dept->slug" wire:navigate>
                            {{ __('Production Dispatches') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="document-text"
                            :href="branch_route('branch-dashboard.sales-dashboard.quotations.index', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Quotations' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Quotations' . '_' . $dept->slug" wire:navigate>
                            {{ __('Quotations') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="chart-bar"
                            :href="branch_route('branch-dashboard.sales-dashboard.my-sales.index', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'My Sales Dashboard' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'My Sales Dashboard' . '_' . $dept->slug" wire:navigate>
                            {{ __('My Sales Dashboard') }}
                        </flux:navlist.item>

                        @if($canSeeSalesReports)
                            <flux:navlist.item icon="document-text"
                                :href="branch_route('branch-dashboard.sales-dashboard.reports.sales-performance', [
                                    'salesDeptSlug' => $dept->slug,
                                    'sales_dept_slug' => $dept->slug,
                                    'page' => 'Sales Reports' . '_' . $dept->slug
                                ])"
                                :current="request()->get('page') === 'Sales Reports' . '_' . $dept->slug" wire:navigate>
                                {{ __('Sales Reports') }}
                            </flux:navlist.item>
                        @endif

                        <flux:navlist.item icon="question-mark-circle"
                            :href="branch_route('branch-dashboard.sales-dashboard.helper', [
                                'salesDeptSlug' => $dept->slug,
                                'sales_dept_slug' => $dept->slug,
                                'page' => 'Helper' . '_' . $dept->slug
                            ])"
                            :current="request()->get('page') === 'Helper' . '_' . $dept->slug" wire:navigate>
                            {{ __('Helper') }}
                        </flux:navlist.item>

                        <flux:navlist.group :heading="__('Callbacks')" class="grid" expandable
                            :expanded="request()->get('page') === 'Callbacks' . '_' . $dept->slug">
                            <flux:navlist.item icon="arrow-uturn-left"
                                :href="branch_route('branch-dashboard.sales-dashboard.callbacks.index', [
                                    'salesDeptSlug' => $dept->slug,
                                    'sales_dept_slug' => $dept->slug,
                                    'page' => 'Callbacks' . '_' . $dept->slug
                                ])"
                                :current="request()->get('page') === 'Callbacks' . '_' . $dept->slug" wire:navigate>
                                {{ __('Product Callbacks') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="arrow-path-rounded-square"
                                :href="branch_route('branch-dashboard.sales-dashboard.callbacks.dispatch-callbacks', [
                                    'salesDeptSlug' => $dept->slug,
                                    'sales_dept_slug' => $dept->slug,
                                    'page' => 'Callbacks' . '_' . $dept->slug
                                ])"
                                :current="request()->get('page') === 'Callbacks' . '_' . $dept->slug" wire:navigate>
                                {{ __('Dispatch Callbacks') }}
                            </flux:navlist.item>
                        </flux:navlist.group>

                        @if($isSalesSupervisor || $isSalesManager || $isAdmin || $isSuperAdmin)
                            @php
                                $pendingVarianceCount = \Illuminate\Support\Facades\Cache::remember(
                                    "pending_variances_{$branchId}",
                                    3600,
                                    fn () => \App\Models\Callback::where('branch_id', $branchId)
                                        ->whereNotNull('product_stock_id')
                                        ->whereIn('reason', ['shortage', 'excess'])
                                        ->where('status', 'pending')
                                        ->count()
                                );
                            @endphp
                            <flux:navlist.item icon="exclamation-triangle"
                                :href="branch_route('branch-dashboard.sales-dashboard.variance-resolution.index', [
                                    'salesDeptSlug' => $dept->slug,
                                    'sales_dept_slug' => $dept->slug,
                                ])"
                                :current="request()->routeIs('branch-dashboard.sales-dashboard.variance-resolution.*')" wire:navigate>
                                {{ __('Variance Resolution') }}
                                @if($pendingVarianceCount > 0)
                                    <flux:badge size="sm" color="amber" class="ml-auto">{{ $pendingVarianceCount }}</flux:badge>
                                @endif
                            </flux:navlist.item>
                        @endif

                        @forelse($dept->pages as $page)
                            <flux:navlist.item icon="{{ $page->icon ?? 'o-shopping-bag' }}"
                                :href="branch_route($page->route_name, [
                                    'salesDeptSlug' => $dept->slug,
                                    'sales_dept_slug' => $dept->slug,
                                    'page' => $page->name . '_' . $dept->slug
                                ])"
                                :current="request()->get('page') === $page->name . '_' . $dept->slug" wire:navigate>
                                {{ $page->name }}
                            </flux:navlist.item>

                        @empty
                            <div class="pl-10 pr-4 py-1.5 text-xs text-gray-500 italic">
                                {{ __('No pages configured') }}
                            </div>
                        @endforelse
                    </flux:navlist.group>
                @empty
                    <div class="pl-10 pr-4 py-1.5 text-xs text-gray-500 italic">
                        {{ __('No sales departments') }}
                    </div>
                @endforelse
            </flux:navlist.group>
            @endif
            {{-- ====================RS 30793 SALES DEPARTMENTS MENU ==================== --}}

            @if ($sidebarService::canSeeReporting($currentUser))
                <flux:navlist.group :heading="__('Reporting')" icon="document-text">
                    <flux:navlist.item icon="chart-bar" :href="branch_route('branch-dashboard.reporting.dashboard')"
                        :current="request()->routeIs('branch-dashboard.reporting.dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-chart-bar" :href="branch_route('branch-dashboard.reporting.generate')"
                        :current="request()->routeIs('branch-dashboard.reporting.generate')" wire:navigate>
                        {{ __('Generate Reports') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="clipboard-document-check"
                        :href="branch_route('branch-dashboard.reporting.review')"
                        :current="request()->routeIs('branch-dashboard.reporting.review')" wire:navigate>
                        {{ __('Review Reports') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="document-duplicate"
                        :href="branch_route('branch-dashboard.reporting.compile')"
                        :current="request()->routeIs('branch-dashboard.reporting.compile')" wire:navigate>
                        {{ __('Compile Reports') }}
                    </flux:navlist.item>
                    <flux:navlist.item icon="paper-airplane" :href="branch_route('branch-dashboard.reporting.send-to-md')"
                        :current="request()->routeIs('branch-dashboard.reporting.send-to-md')" wire:navigate>
                        {{ __('Send to Managing Director') }}
                    </flux:navlist.item>
                </flux:navlist.group>
                @endif
           

        </flux:navlist>
        </div>

        <flux:spacer />


        <!-- Desktop User Menu -->
        <flux:dropdown class="hidden lg:block" position="bottom" align="start">
            <flux:profile :name="get_user_auth()?->name ?? 'User'" :initials="get_user_auth()?->initials() ?? 'U'"
                icon:trailing="chevrons-up-down" />

            <flux:menu class="w-[220px]">
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ get_user_auth()?->initials() ?? 'U' }}
                                </span>
                            </span>
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ get_user_auth()?->name ?? 'User' }}</span>
                                <span class="truncate text-xs">{{ get_user_auth()?->email ?? 'user@example.com' }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="branch_route('branch-dashboard.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ branch_route('logout') }}" class="w-full" id="logout-form-sidebar">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:sidebar>
    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="get_user_auth()?->initials() ?? 'U'" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ get_user_auth()?->initials() ?? 'U' }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ get_user_auth()?->name ?? 'User' }}</span>
                                <span class="truncate text-xs">{{ get_user_auth()?->email ?? 'user@example.com' }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="branch_route('branch-dashboard.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}</flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{-- navbar section --}}
    <flux:header sticky container class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <!-- Sidebar Toggle -->
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />


        <flux:spacer />

        <!-- Shift Status & Digital Clock -->
        <div class="flex items-center gap-6 text-sm text-zinc-700 dark:text-zinc-200 me-3">
            <!-- Shift Status Header Component -->
            @livewire('shift-status-header')

            <!-- Digital Clock -->
            <div x-data="{ currentTime: '' }" x-init="setInterval(() => {
                const now = new Date();
                currentTime = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            }, 1000);"
                class="font-mono text-base md:text-lg tracking-widest" x-text="currentTime">
            </div>
        </div>

        <!-- Notification Bell -->
        <livewire:notification-bell />

        <!-- Search Navbar -->
        <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
            <div x-data="{
                open: false,
                query: '',
                isMac: navigator.platform.toUpperCase().includes('MAC'),
                toggle() {
                    this.open = !this.open;
                    if (this.open) {
                        this.$nextTick(() => this.$refs.searchInput.focus());
                    }
                }
            }" x-init="window.addEventListener('keydown', e => {
                if ((isMac ? e.metaKey : e.ctrlKey) && e.key.toLowerCase() === 'k') {
                    e.preventDefault();
                    toggle();
                }
                if (e.key === 'Escape') open = false;
            });" class="relative">
                <button @click="toggle" type="button"
                    class="inline-flex items-center gap-3 px-3 py-1.5 rounded-full border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 shadow-sm hover:shadow-md transition-all duration-150">
                    <!-- Search icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-zinc-600 dark:text-zinc-300"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                    <span class="text-sm text-zinc-700 dark:text-zinc-200">Search</span>

                    <kbd
                        class="ml-1 inline-flex items-center gap-1 rounded border border-zinc-200 dark:border-zinc-700 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:text-zinc-300">
                        <span x-text="isMac ? '⌘' : 'Ctrl'"></span>
                        <span class="text-[10px] opacity-70">+</span>
                        <span>K</span>
                    </kbd>
                </button>

                <!-- Search Modal -->
                <div x-show="open" x-transition.opacity.duration.300ms @click.away="open = false"
                    class="fixed inset-0 bg-black/40 flex items-start justify-center pt-24 z-50" x-cloak>
                    <!-- Modal Box -->
                    <div x-show="open" x-transition:enter="ease-out duration-300"
                        x-transition:enter-start="opacity-0 -translate-y-8 scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                        x-transition:leave="ease-in duration-200"
                        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                        x-transition:leave-end="opacity-0 -translate-y-4 scale-95" x-trap.noscroll.inert="open"
                        class="bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl w-full max-w-3xl p-6 mx-4">
                        <!-- Header -->
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">Quick Search</h2>
                            <button @click="open = false"
                                class="text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke-width="2" stroke="currentColor" class="w-5 h-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Search Input -->
                        <form @submit.prevent="$dispatch('search', query); open = false">
                            <input x-ref="searchInput" type="text" x-model="query"
                                placeholder="Type to search..."
                                class="w-full border border-zinc-300 dark:border-zinc-700 rounded-lg p-3 text-base focus:ring-2 focus:ring-indigo-500 focus:outline-none dark:bg-zinc-800 dark:text-zinc-100" />
                        </form>

                        <!-- Footer -->
                        <p class="mt-3 text-xs text-zinc-500 dark:text-zinc-400 text-right">
                            Press <kbd class="px-1 py-0.5 border rounded">Esc</kbd> to close
                        </p>
                    </div>
                </div>
            </div>
        </flux:navbar>

        <!-- Desktop User Menu -->
        <flux:dropdown position="top" align="end">
            <flux:profile class="cursor-pointer" :initials="get_user_auth()?->initials() ?? 'U'" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                <span
                                    class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                    {{ get_user_auth()?->initials() ?? 'U' }}
                                </span>
                            </span>

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <span class="truncate font-semibold">{{ get_user_auth()?->name ?? 'User' }}</span>
                                <span class="truncate text-xs">{{ get_user_auth()?->email ?? 'user@example.com' }}</span>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="branch_route('branch-dashboard.profile')" icon="user" wire:navigate>
                        {{ __('Profile') }}
                    </flux:menu.item>
                    <flux:menu.item :href="branch_route('branch-dashboard.profile')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full">
                        {{ __('Log Out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>

    </flux:header>

    <flux:main>
        {{-- Branch Selector for Super Admins --}}
        @if (auth()->user() && auth()->user()->hasRole('Super Admin'))
            <livewire:components.branch-selector />
        @endif

        <x-toast />
        <x-dialog />
        {{ $slot }}
    </flux:main>

    @auth
        <livewire:components.chat-bot />
    @endauth

    @fluxScripts
    @livewireScripts
    @stack('scripts')

    <script>
        // Handle 419 CSRF errors on logout forms
        document.addEventListener('DOMContentLoaded', function() {
            const logoutForms = document.querySelectorAll('form[action*="/logout"]');
            logoutForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Let the form submit normally first
                    // If it fails with 419, we'll handle it in the fetch override
                });
            });
        });

        // Override fetch to handle 419 errors on logout requests
        if (!window.originalFetch) {
            window.originalFetch = window.fetch;
        }
        window.fetch = function(...args) {
            return window.originalFetch.apply(this, args).then(response => {
                if (response.status === 419 && args[0].includes('/logout')) {
                    // CSRF token expired on logout, redirect to login
                    window.location.href = '{{ route("login") }}';
                    return response;
                }
                return response;
            }).catch(error => {
                // Handle network errors
                console.error('Logout request failed:', error);
                // Still redirect to login on error
                window.location.href = '{{ route("login") }}';
                throw error;
            });
        };
        // purchaseItemsForm removed (reverted to Livewire-driven items)

        function sidebarScroll() {
            const key = 'sidebar-scroll-top';
            return {
                init() {
                    const el = this.$el;
                    const saved = window.localStorage.getItem(key);
                    if (saved !== null) {
                        el.scrollTop = parseInt(saved, 10) || 0;
                    }
                    document.addEventListener('livewire:navigated', () => {
                        const savedAgain = window.localStorage.getItem(key);
                        if (savedAgain !== null) {
                            el.scrollTop = parseInt(savedAgain, 10) || 0;
                        }
                    });
                },
                save(evt) {
                    window.localStorage.setItem(key, evt.target.scrollTop);
                }
            };
        }
    </script>

    <style>
        #sidebar-scroll.sidebar-scroll-hide {
            scrollbar-width: none;
        }
        #sidebar-scroll.sidebar-scroll-hide::-webkit-scrollbar {
            display: none;
        }
    </style>
</body>

</html>
