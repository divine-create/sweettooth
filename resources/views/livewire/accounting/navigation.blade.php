<div class="bg-white rounded-lg shadow-md mb-6">
    <div class="flex items-center gap-4 p-6 border-b border-gray-200">
        <div class="flex-1">
            <h1 class="text-3xl font-bold text-gray-800">Accounting Module</h1>
            <p class="text-sm text-gray-600 mt-1">
                @if($isSuperAdminOrMd)
                    <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-semibold">
                        Full Access
                    </span>
                @else
                    Financial Management & Reporting
                @endif
            </p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-0 divide-x divide-y">
        <!-- Dashboard -->
        @if($canAccessDashboard)
            <a href="{{ route('branch-dashboard.accounting.dashboard') }}" 
               class="p-4 hover:bg-blue-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📊</div>
                <div class="text-sm font-semibold text-gray-700">Dashboard</div>
                <div class="text-xs text-gray-500">Overview</div>
            </a>
        @endif

        <!-- Chart of Accounts -->
        @if($canManageAccounts || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.accounts') }}" 
               class="p-4 hover:bg-green-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📑</div>
                <div class="text-sm font-semibold text-gray-700">Accounts</div>
                <div class="text-xs text-gray-500">GL Chart</div>
            </a>
        @endif

        <!-- Periods -->
        @if($canManagePeriods || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.periods') }}" 
               class="p-4 hover:bg-yellow-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📅</div>
                <div class="text-sm font-semibold text-gray-700">Periods</div>
                <div class="text-xs text-gray-500">Management</div>
            </a>
        @endif

        <!-- Journal Entry -->
        @if($canCreateJournalEntries || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.journal-entry') }}" 
               class="p-4 hover:bg-purple-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📝</div>
                <div class="text-sm font-semibold text-gray-700">Journal</div>
                <div class="text-xs text-gray-500">Entry</div>
            </a>
        @endif

        <!-- General Ledger Report -->
        @if($canViewReports || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.reports.general-ledger') }}" 
               class="p-4 hover:bg-indigo-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📋</div>
                <div class="text-sm font-semibold text-gray-700">GL Report</div>
                <div class="text-xs text-gray-500">Ledger</div>
            </a>
        @endif

        <!-- Trial Balance Report -->
        @if($canViewReports || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.reports.trial-balance') }}" 
               class="p-4 hover:bg-red-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">⚖️</div>
                <div class="text-sm font-semibold text-gray-700">Trial Balance</div>
                <div class="text-xs text-gray-500">Validation</div>
            </a>
        @endif

        <!-- Income Statement -->
        @if($canViewReports || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.reports.income-statement') }}" 
               class="p-4 hover:bg-orange-50 transition text-center border-r border-b">
                <div class="text-2xl mb-2">📈</div>
                <div class="text-sm font-semibold text-gray-700">P&L</div>
                <div class="text-xs text-gray-500">Income Statement</div>
            </a>
        @endif

        <!-- Balance Sheet -->
        @if($canViewReports || $isSuperAdminOrMd)
            <a href="{{ route('branch-dashboard.accounting.reports.balance-sheet') }}" 
               class="p-4 hover:bg-cyan-50 transition text-center">
                <div class="text-2xl mb-2">⚖️</div>
                <div class="text-sm font-semibold text-gray-700">Balance Sheet</div>
                <div class="text-xs text-gray-500">Financial Position</div>
            </a>
        @endif
    </div>

    <!-- Access Level Badge -->
    <div class="px-6 py-3 bg-gray-50 border-t text-xs text-gray-600">
        @if($isSuperAdminOrMd)
            <span class="inline-flex items-center px-3 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                ✓ You have full access to all accounting features
            </span>
        @else
            <span class="inline-flex items-center px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-xs">
                Your access level: Standard Accountant
            </span>
        @endif
    </div>
</div>
