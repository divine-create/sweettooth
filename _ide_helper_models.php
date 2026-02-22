<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string $setting_type
 * @property string $proposed_value
 * @property string $status
 * @property string|null $super_admin_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $superAdmin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereProposedValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSettingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereSuperAdminId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovalRequest whereUpdatedAt($value)
 */
	class ApprovalRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $request_id
 * @property int $item_id
 * @property string $approved_by
 * @property string $branch_id
 * @property string $quantity
 * @property string $uom
 * @property string $approved_time
 * @property string $status
 * @property string|null $shift
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\ItemRequest $itemRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem forShift(string $shift)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereApprovedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ApprovedItem whereUpdatedAt($value)
 */
	class ApprovedItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string $setting_type
 * @property string $action
 * @property string|null $user_id
 * @property array<array-key, mixed> $details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereSettingType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AuditLog whereUserId($value)
 */
	class AuditLog extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $code
 * @property string $location
 * @property string|null $phone
 * @property string|null $email
 * @property string|null $description
 * @property string|null $manager_user_id
 * @property string|null $country
 * @property string|null $state
 * @property string|null $city
 * @property string|null $postal_code
 * @property string|null $timezone
 * @property bool $is_active
 * @property bool $enable_table_management
 * @property array<array-key, mixed>|null $table_management_settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $manager
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Table> $tables
 * @property-read int|null $tables_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereEnableTableManagement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereManagerUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereTableManagementSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Branch withoutTrashed()
 */
	class Branch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $local_expenses
 * @property string|null $branch_id
 * @property string|null $cash_transactions
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereCashTransactions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereLocalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchAccountingCash whereUpdatedAt($value)
 */
	class BranchAccountingCash extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string|null $company_name
 * @property string|null $logo_upload
 * @property array<array-key, mixed>|null $contact_details
 * @property array<array-key, mixed>|null $business_type
 * @property array<array-key, mixed>|null $storage_settings
 * @property string|null $subscription_plan
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereBusinessType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereContactDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereLogoUpload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereStorageSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereSubscriptionPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchBusinessConfiguration whereUpdatedAt($value)
 */
	class BranchBusinessConfiguration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string|null $currency_display
 * @property string|null $language
 * @property string|null $units_local
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereCurrencyDisplay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereUnitsLocal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCurrencyLocalization whereUpdatedAt($value)
 */
	class BranchCurrencyLocalization extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCustomerSupplierManagement forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCustomerSupplierManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCustomerSupplierManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchCustomerSupplierManagement query()
 */
	class BranchCustomerSupplierManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchEmployeeManagement forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchEmployeeManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchEmployeeManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchEmployeeManagement query()
 */
	class BranchEmployeeManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchInventoryManagement forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchInventoryManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchInventoryManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchInventoryManagement query()
 */
	class BranchInventoryManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchManagement forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchManagement query()
 */
	class BranchManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property array<array-key, mixed>|null $alerts
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereAlerts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchNotificationsAlerts whereUpdatedAt($value)
 */
	class BranchNotificationsAlerts extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string|null $pos_use
 * @property string|null $payment_modes
 * @property string|null $receipt_custom
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration wherePaymentModes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration wherePosUse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereReceiptCustom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchPosConfiguration whereUpdatedAt($value)
 */
	class BranchPosConfiguration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property array<array-key, mixed>|null $branch_reports
 * @property string|null $date_filter
 * @property string|null $export
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereBranchReports($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereDateFilter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereExport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchReportsAnalytics whereUpdatedAt($value)
 */
	class BranchReportsAnalytics extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string|null $local_logs
 * @property bool $is_overridden
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess forBranch(int $branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereIsOverridden($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereLocalLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BranchSecurityAccess whereUpdatedAt($value)
 */
	class BranchSecurityAccess extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property string $date
 * @property string $shift
 * @property string $clock_in_time
 * @property string $clock-out-time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $employee
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereClockInTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereClockOutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ClockIn whereUpdatedAt($value)
 */
	class ClockIn extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $recipe_id
 * @property \Illuminate\Support\Carbon $produce_date
 * @property string $shift_type
 * @property numeric $opening_quantity
 * @property numeric $requested_quantity
 * @property numeric $produced_quantity
 * @property numeric $sent_out_quantity
 * @property numeric $order_quantity
 * @property numeric $callback_quantity
 * @property numeric $closing_quantity
 * @property numeric $expected_closing
 * @property numeric $variance
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionRecord> $productionRecords
 * @property-read int|null $production_records_count
 * @property-read \App\Models\Recipe $recipe
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereCallbackQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereClosingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereExpectedClosing($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereOpeningQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereOrderQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereProduceDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereProducedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereRequestedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereSentOutQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DailyProduce whereVariance($value)
 */
	class DailyProduce extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property string $category_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property bool $enable_table_management
 * @property array<array-key, mixed>|null $table_management_settings
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\DepartmentCategory $category
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Employee> $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DepartmentPage> $pages
 * @property-read int|null $pages_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductType> $productTypes
 * @property-read int|null $product_types_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Table> $tables
 * @property-read int|null $tables_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereEnableTableManagement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereTableManagementSettings($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Department whereUpdatedAt($value)
 */
	class Department extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentCategory whereUpdatedAt($value)
 */
	class DepartmentCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $slug
 * @property string $route_name
 * @property string|null $icon
 * @property int $order
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department $department
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereRouteName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DepartmentPage whereUpdatedAt($value)
 */
	class DepartmentPage extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string|null $branch_id
 * @property int|null $department_id
 * @property string|null $manager_id
 * @property string $employee_number
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $date_of_birth
 * @property string|null $gender
 * @property string|null $nationality
 * @property string|null $emergency_contact_name
 * @property string|null $emergency_contact_phone
 * @property string $hire_date
 * @property string|null $termination_date
 * @property string $status
 * @property string|null $probation_end_date
 * @property string|null $shift_preference
 * @property string|null $salary
 * @property string|null $hourly_rate
 * @property string|null $tax_id
 * @property string|null $bank_account
 * @property string|null $allergies
 * @property string|null $profile_photo
 * @property string|null $last_performance_review_date
 * @property string|null $performance_rating
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveApplication> $approvedLeaveApplications
 * @property-read int|null $approved_leave_applications_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeLeaveAllocation> $leaveAllocations
 * @property-read int|null $leave_allocations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveApplication> $leaveApplications
 * @property-read int|null $leave_applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeLeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @property-read Employee|null $manager
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeStepout> $stepouts
 * @property-read int|null $stepouts_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Employee> $subordinates
 * @property-read int|null $subordinates_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereAllergies($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBankAccount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDateOfBirth($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmergencyContactPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereEmployeeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHireDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereHourlyRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereLastPerformanceReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereNationality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePerformanceRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProbationEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereProfilePhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereSalary($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereShiftPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTaxId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereTerminationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Employee withoutTrashed()
 */
	class Employee extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property numeric $allocated_days
 * @property string|null $notes
 * @property string|null $allocated_by
 * @property \Illuminate\Support\Carbon|null $allocated_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $allocatedBy
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation forEmployee($employeeId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation forYear($year)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereAllocatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereAllocatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereAllocatedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveAllocation whereYear($value)
 */
	class EmployeeLeaveAllocation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property int $leave_type_id
 * @property int $year
 * @property numeric $total_days
 * @property numeric $used_days
 * @property numeric $pending_days
 * @property numeric $remaining_days
 * @property numeric $carried_forward
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\EmployeeLeaveAllocation|null $allocation
 * @property-read \App\Models\Employee $employee
 * @property-read \App\Models\LeaveType $leaveType
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereCarriedForward($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance wherePendingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereRemainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereUsedDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeLeaveBalance whereYear($value)
 */
	class EmployeeLeaveBalance extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeShift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeShift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeShift query()
 */
	class EmployeeShift extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property string $branch_id
 * @property int|null $shift_id
 * @property string $reason
 * @property string|null $reason_details
 * @property \Illuminate\Support\Carbon $request_time
 * @property \Illuminate\Support\Carbon|null $approved_time
 * @property \Illuminate\Support\Carbon|null $stepout_time
 * @property \Illuminate\Support\Carbon|null $return_time
 * @property int|null $duration_minutes
 * @property int $expected_duration_minutes
 * @property string $status
 * @property string|null $approved_by
 * @property string|null $approval_notes
 * @property string|null $rejected_by
 * @property string|null $rejection_reason
 * @property bool $is_overdue
 * @property int|null $overdue_minutes
 * @property string|null $overdue_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approvedBy
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\Employee $employee
 * @property-read mixed $reason_label
 * @property-read mixed $status_badge_class
 * @property-read \App\Models\Employee|null $rejectedBy
 * @property-read \App\Models\Shift|null $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout inProgress()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout overdue()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereApprovalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereApprovedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereExpectedDurationMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereIsOverdue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereOverdueMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereOverdueReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereReasonDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereRequestTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereReturnTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereStepoutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EmployeeStepout whereUpdatedAt($value)
 */
	class EmployeeStepout extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $product_stock_id
 * @property int $sales_shift_id
 * @property string $confirmed_by
 * @property string $action Action taken: confirmed still good or marked as callback
 * @property string|null $notes Reason for confirmation or callback
 * @property \Illuminate\Support\Carbon $confirmed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $confirmedBy
 * @property-read \App\Models\ProductStock $productStock
 * @property-read \App\Models\SalesShift $salesShift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation confirmedGood()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation forShift($shiftId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation markedCallback()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereAction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereConfirmedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereProductStockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereSalesShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExpiryConfirmation whereUpdatedAt($value)
 */
	class ExpiryConfirmation extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $expenses_categories
 * @property string $cash_bank
 * @property string $profit_loss_reports
 * @property string $accounting_entries
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereAccountingEntries($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereCashBank($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereExpensesCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereProfitLossReports($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalAccountingCash whereUpdatedAt($value)
 */
	class GlobalAccountingCash extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $warehouse_add
 * @property string $branch_edit
 * @property string $branch_delete
 * @property string $branch_admin_assign
 * @property string $inter_branch_transfer
 * @property string $central_warehouse
 * @property string $branch_hours
 * @property string $saas_tenant
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereBranchAdminAssign($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereBranchDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereBranchEdit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereBranchHours($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereCentralWarehouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereInterBranchTransfer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereSaasTenant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBranchManagement whereWarehouseAdd($value)
 */
	class GlobalBranchManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $company_name
 * @property string $logo_upload
 * @property array<array-key, mixed> $contact_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int $backup_interval
 * @property string $backup_period
 * @property bool $auto_backup
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereAutoBackup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereBackupInterval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereBackupPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereCompanyName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereContactDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereLogoUpload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalBusinessConfiguration whereUpdatedAt($value)
 */
	class GlobalBusinessConfiguration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $multi_currency
 * @property string $primary_currency
 * @property string|null $primary_currency_exchange_rate
 * @property array<array-key, mixed> $currency_list
 * @property string $multi_tax
 * @property string $default_language
 * @property array<array-key, mixed> $language_options
 * @property string $date_format
 * @property array<array-key, mixed> $units_of_measure
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereCurrencyList($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereDateFormat($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereDefaultLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereLanguageOptions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereMultiCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereMultiTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization wherePrimaryCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization wherePrimaryCurrencyExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereUnitsOfMeasure($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCurrencyLocalization whereUpdatedAt($value)
 */
	class GlobalCurrencyLocalization extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $customers
 * @property array<array-key, mixed> $suppliers
 * @property string $party_import
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement whereCustomers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement wherePartyImport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement whereSuppliers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalCustomerSupplierManagement whereUpdatedAt($value)
 */
	class GlobalCustomerSupplierManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $roles
 * @property array<array-key, mixed> $permissions
 * @property array<array-key, mixed> $staff_profiles
 * @property array<array-key, mixed> $departments
 * @property string $shift_scheduling
 * @property string $pin_login
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereDepartments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement wherePermissions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement wherePinLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereRoles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereShiftScheduling($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereStaffProfiles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalEmployeeManagement whereUpdatedAt($value)
 */
	class GlobalEmployeeManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $categories
 * @property array<array-key, mixed> $brands
 * @property array<array-key, mixed> $products
 * @property string $multi_variant
 * @property string $stock_adjustment
 * @property string $purchase_returns
 * @property array<array-key, mixed> $supplier_management
 * @property string $low_stock_alert
 * @property string $expiry_tracking
 * @property string $import_csv
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereBrands($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereExpiryTracking($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereImportCsv($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereLowStockAlert($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereMultiVariant($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereProducts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement wherePurchaseReturns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereStockAdjustment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereSupplierManagement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalInventoryManagement whereUpdatedAt($value)
 */
	class GlobalInventoryManagement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $alerts
 * @property string $task_todo
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts whereAlerts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts whereTaskTodo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalNotificationsAlerts whereUpdatedAt($value)
 */
	class GlobalNotificationsAlerts extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $pos_interface
 * @property array<array-key, mixed> $payment_modes
 * @property string $receipt_template
 * @property string $sales_returns
 * @property string $offline_mode
 * @property string $online_shop_sync
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereOfflineMode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereOnlineShopSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration wherePaymentModes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration wherePosInterface($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereReceiptTemplate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereSalesReturns($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalPosConfiguration whereUpdatedAt($value)
 */
	class GlobalPosConfiguration extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property array<array-key, mixed> $reports
 * @property string $custom_date_range
 * @property string $multi_select_delete
 * @property array<array-key, mixed> $export
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereCustomDateRange($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereExport($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereMultiSelectDelete($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereReports($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalReportsAnalytics whereUpdatedAt($value)
 */
	class GlobalReportsAnalytics extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $authentication
 * @property string $audit_logs
 * @property string $data_isolation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereAuditLogs($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereAuthentication($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereDataIsolation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|GlobalSecurityAccess whereUpdatedAt($value)
 */
	class GlobalSecurityAccess extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stock_id
 * @property string $checked_by
 * @property \Illuminate\Support\Carbon $check_date
 * @property string $condition
 * @property numeric|null $quantity_affected
 * @property string|null $observations
 * @property string|null $action_taken
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $checker
 * @property-read \App\Models\Stock $stock
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereActionTaken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereCheckDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereCheckedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereCondition($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereObservations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereQuantityAffected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereStockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HealthCheck withCondition(string $condition)
 */
	class HealthCheck extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property string $name
 * @property string $sku
 * @property string $category
 * @property string $uom
 * @property string|null $description
 * @property numeric|null $reorder_level
 * @property numeric|null $max_stock_level
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemDispatch> $itemDispatches
 * @property-read int|null $item_dispatches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemRequestDetail> $itemRequestDetails
 * @property-read int|null $item_request_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseItem> $purchaseItems
 * @property-read int|null $purchase_items_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTakeDetail> $stockTakeDetails
 * @property-read int|null $stock_take_details_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stock> $stocks
 * @property-read int|null $stocks_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereMaxStockLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereReorderLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Item whereUpdatedAt($value)
 */
	class Item extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $request_id
 * @property int $item_id
 * @property string $dispatched_by
 * @property string $branch_id
 * @property string|null $received_by
 * @property numeric $quantity
 * @property string $uom
 * @property \Illuminate\Support\Carbon $dispatch_time
 * @property \Illuminate\Support\Carbon|null $received_time
 * @property string|null $shift
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee $dispatcher
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\ItemRequest $itemRequest
 * @property-read \App\Models\Employee|null $receiver
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch betweenDates($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch forShift(string $shift)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereDispatchTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereDispatchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereReceivedTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemDispatch whereUpdatedAt($value)
 */
	class ItemDispatch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int $department_id
 * @property string $requested_by
 * @property string $request_number
 * @property \Illuminate\Support\Carbon $request_date
 * @property string|null $shift
 * @property string $status
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approver
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemDispatch> $itemDispatches
 * @property-read int|null $item_dispatches_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionRequest> $productionRequests
 * @property-read int|null $production_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItemRequestDetail> $requestDetails
 * @property-read int|null $request_details_count
 * @property-read \App\Models\Employee $requestedBy
 * @property-read \App\Models\Employee $requester
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest forDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereRequestDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereRequestNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereRequestedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereShift($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequest withStatus(string $status)
 */
	class ItemRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $request_id
 * @property int $item_id
 * @property numeric $quantity_requested
 * @property numeric $quantity_approved
 * @property numeric $quantity_dispatched
 * @property string $uom
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\ItemRequest $itemRequest
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereQuantityApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereQuantityDispatched($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereQuantityRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItemRequestDetail whereUpdatedAt($value)
 */
	class ItemRequestDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $application_number
 * @property string $employee_id
 * @property int $leave_type_id
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon $end_date
 * @property numeric $total_days
 * @property string $reason
 * @property string|null $emergency_contact
 * @property string|null $supporting_document
 * @property string $status
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $approval_notes
 * @property string|null $rejected_by
 * @property \Illuminate\Support\Carbon|null $rejected_at
 * @property string|null $rejection_reason
 * @property string|null $cancelled_by
 * @property \Illuminate\Support\Carbon|null $cancelled_at
 * @property string|null $cancellation_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string|null $branch_id
 * @property-read \App\Models\Employee|null $approvedBy
 * @property-read \App\Models\Employee|null $cancelledBy
 * @property-read \App\Models\Employee $employee
 * @property-read mixed $status_badge_class
 * @property-read \App\Models\LeaveType $leaveType
 * @property-read \App\Models\Employee|null $rejectedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereApplicationNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereApprovalNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereCancellationReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereCancelledBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereEmergencyContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereLeaveTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereRejectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereRejectedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereSupportingDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereTotalDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveApplication whereUpdatedAt($value)
 */
	class LeaveApplication extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $default_days_per_year
 * @property bool $requires_approval
 * @property bool $requires_document
 * @property int|null $max_consecutive_days
 * @property int $min_notice_days
 * @property bool $is_paid
 * @property bool $is_active
 * @property string $color
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $formatted_name
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\LeaveApplication> $leaveApplications
 * @property-read int|null $leave_applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\EmployeeLeaveBalance> $leaveBalances
 * @property-read int|null $leave_balances_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDefaultDaysPerYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereIsPaid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereMaxConsecutiveDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereMinNoticeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresApproval($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereRequiresDocument($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LeaveType whereUpdatedAt($value)
 */
	class LeaveType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sale_id
 * @property string $payment_method
 * @property numeric $amount
 * @property string|null $reference_number
 * @property \Illuminate\Support\Carbon $payment_time
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Sale $sale
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereUpdatedAt($value)
 */
	class Payment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $employee_id
 * @property string $review_date
 * @property string $probation_start_date
 * @property string $probation_end_date
 * @property string $review_type
 * @property string $reviewer_id
 * @property int|null $performance_score
 * @property string|null $strengths
 * @property string|null $areas_for_improvement
 * @property string|null $goals_achievements
 * @property string|null $training_recommendations
 * @property string $overall_comments
 * @property string $recommendation
 * @property string|null $extended_probation_end_date
 * @property int|null $extension_days
 * @property string|null $extension_reason
 * @property string $status
 * @property string|null $acknowledged_by
 * @property string|null $acknowledged_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereAcknowledgedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereAcknowledgedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereAreasForImprovement($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereExtendedProbationEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereExtensionDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereExtensionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereGoalsAchievements($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereOverallComments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview wherePerformanceScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereProbationEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereProbationStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereRecommendation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereReviewDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereReviewType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereReviewerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereStrengths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereTrainingRecommendations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProbationReview whereUpdatedAt($value)
 */
	class ProbationReview extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $sku
 * @property string|null $branch_id
 * @property int $product_type_id
 * @property int|null $category_id
 * @property string|null $description
 * @property numeric $price
 * @property numeric|null $cost Cost price for margin calculation
 * @property int $shelf_life_days
 * @property string $uom
 * @property numeric|null $unit_weight Weight of one unit (in grams/ml)
 * @property bool $is_active
 * @property bool $is_available Available for production/sale
 * @property string|null $image_url
 * @property array<array-key, mixed>|null $allergens List of allergens
 * @property array<array-key, mixed>|null $tags Product tags for filtering
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Department> $departments
 * @property-read int|null $departments_count
 * @property-read float|null $estimated_cost
 * @property-read string $formatted_price
 * @property-read float|null $profit_margin
 * @property-read \App\Models\ProductType $productType
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Recipe> $recipes
 * @property-read int|null $recipes_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product byType($typeId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product forDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereAllergens($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereIsAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereProductTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereShelfLifeDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereTags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUnitWeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product withoutTrashed()
 */
	class Product extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int|null $daily_produce_id
 * @property int $production_shift_id Kitchen/Production shift
 * @property int|null $sales_shift_id
 * @property int|null $sales_department_id
 * @property string $product_id
 * @property string $dispatched_by
 * @property numeric $quantity
 * @property numeric|null $received_quantity
 * @property string $uom
 * @property \Illuminate\Support\Carbon $dispatch_time
 * @property string $shift_type
 * @property \Illuminate\Support\Carbon $dispatch_date
 * @property string|null $received_by Sales employee who received
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\DailyProduce|null $dailyProduce
 * @property-read \App\Models\Employee $dispatchedBy
 * @property-read \App\Models\Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductDispatchCallback> $productDispatchCallbacks
 * @property-read int|null $product_dispatch_callbacks_count
 * @property-read \App\Models\Shift $productionShift
 * @property-read \App\Models\Employee|null $receivedBy
 * @property-read \App\Models\Department|null $salesDepartment
 * @property-read \App\Models\SalesShift|null $salesShift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch forDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch forProduct($productId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch forSalesDepartment($salesDepartmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch forShiftType($shiftType)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch received()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereDailyProduceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereDispatchDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereDispatchTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereDispatchedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereProductionShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereReceivedQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereSalesDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereSalesShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatch whereUpdatedAt($value)
 */
	class ProductDispatch extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $product_dispatch_id
 * @property int $sales_shift_id
 * @property string $product_id
 * @property string $recorded_by
 * @property numeric $quantity
 * @property string $uom
 * @property string|null $reason
 * @property string $status
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $received_by
 * @property \Illuminate\Support\Carbon|null $received_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $callback_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approvedBy
 * @property-read string $formatted_reason
 * @property-read string $formatted_status
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\ProductDispatch|null $productDispatch
 * @property-read \App\Models\Employee|null $receivedBy
 * @property-read \App\Models\Employee $recordedBy
 * @property-read \App\Models\SalesShift $salesShift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback byProduct($productId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback bySalesShift($salesShiftId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback received()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereCallbackTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereProductDispatchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereReceivedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereReceivedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereSalesShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductDispatchCallback whereUpdatedAt($value)
 */
	class ProductDispatchCallback extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $sales_shift_id
 * @property string $product_id
 * @property \Illuminate\Support\Carbon $stock_date
 * @property string $shift_type
 * @property numeric $opening_quantity
 * @property numeric $addition_quantity
 * @property \Illuminate\Support\Carbon|null $production_date
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property numeric $callback_quantity
 * @property numeric $redress_quantity
 * @property numeric $total_available
 * @property numeric $transfer_quantity
 * @property numeric $glovo_quantity
 * @property numeric $quantity_sold
 * @property numeric $closing_quantity
 * @property numeric $amount
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\SalesShift|null $salesShift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereAdditionQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereCallbackQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereClosingQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereGlovoQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereOpeningQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereProductionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereQuantitySold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereRedressQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereSalesShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereStockDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereTotalAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereTransferQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductStock whereUpdatedAt($value)
 */
	class ProductStock extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $department_id
 * @property string $name
 * @property string $code Short code for product type (e.g., GB, GF, PT)
 * @property string|null $description
 * @property string $status
 * @property int $sort_order Display order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Department $department
 * @property-read int $active_products_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Product> $products
 * @property-read int|null $products_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType byDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType ordered()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductType withoutTrashed()
 */
	class ProductType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property string $source_type
 * @property int|null $item_id
 * @property string|null $product_id
 * @property string $recorded_by
 * @property numeric $quantity
 * @property string $uom
 * @property string $reason
 * @property string $status
 * @property string|null $approved_by
 * @property \Illuminate\Support\Carbon|null $approved_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $callback_time
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $approvedBy
 * @property-read string $formatted_reason
 * @property-read string $formatted_source_type
 * @property-read string $formatted_status
 * @property-read string $item_name
 * @property-read \App\Models\Item|null $item
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Employee $recordedBy
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback approved()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback byShift($shiftId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback completed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback finishedProduct()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback pending()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback rawMaterial()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback rejected()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereApprovedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereApprovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereCallbackTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereSourceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionCallback whereUpdatedAt($value)
 */
	class ProductionCallback extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $daily_produce_id
 * @property string|null $batch_number
 * @property int $recipe_id
 * @property string $produced_by
 * @property numeric $quantity_produced
 * @property numeric $quantity_approved
 * @property numeric $quantity_sent_out
 * @property numeric $quantity_for_order
 * @property numeric $quantity_remaining
 * @property numeric $quantity_rejected
 * @property \Illuminate\Support\Carbon $production_time
 * @property string $quality_status
 * @property string $dispatch_status
 * @property string|null $rejection_reason
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DailyProduce $dailyProduce
 * @property-read \App\Models\Employee $producedBy
 * @property-read \App\Models\Recipe $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereBatchNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereDailyProduceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereDispatchStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereProducedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereProductionTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQualityStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantityApproved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantityForOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantityProduced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantityRejected($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantityRemaining($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereQuantitySentOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereRejectionReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRecord whereUpdatedAt($value)
 */
	class ProductionRecord extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $item_request_id
 * @property int|null $recipe_id
 * @property numeric|null $planned_production_quantity
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ItemRequest $itemRequest
 * @property-read \App\Models\Recipe|null $recipe
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest forShift($shiftId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereItemRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest wherePlannedProductionQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductionRequest whereUpdatedAt($value)
 */
	class ProductionRequest extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property string $recorded_by
 * @property string $purchase_number
 * @property \Illuminate\Support\Carbon $purchase_date
 * @property string $supplier_name
 * @property string|null $supplier_contact
 * @property numeric $total_fob_fc
 * @property numeric $total_fob_ngn
 * @property numeric $other_costs
 * @property numeric $landing_cost
 * @property string $total_cost
 * @property string $currency
 * @property numeric $exchange_rate
 * @property string $payment_status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PurchaseItem> $purchaseItems
 * @property-read int|null $purchase_items_count
 * @property-read \App\Models\Employee $recorder
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereExchangeRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereLandingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereOtherCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase wherePurchaseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereRecordedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereSupplierContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereSupplierName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereTotalFobFc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereTotalFobNgn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Purchase whereUpdatedAt($value)
 */
	class Purchase extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $purchase_id
 * @property int $item_id
 * @property numeric $quantity
 * @property string $uom
 * @property string $fob_fc
 * @property string $fob_ngn
 * @property string $other_costs
 * @property string $landing_cost
 * @property numeric $total_cost
 * @property numeric $cost_per_unit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\Purchase $purchase
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereCostPerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereFobFc($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereFobNgn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereLandingCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereOtherCosts($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem wherePurchaseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereTotalCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PurchaseItem whereUpdatedAt($value)
 */
	class PurchaseItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $shift_id
 * @property int $recipe_id
 * @property int $item_id
 * @property numeric $quantity_required
 * @property numeric $quantity_used
 * @property numeric $units_produced
 * @property numeric $variance
 * @property string $variance_type
 * @property numeric $cost_impact
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\Recipe $recipe
 * @property-read \App\Models\Shift $shift
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereCostImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereQuantityRequired($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereQuantityUsed($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereUnitsProduced($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereVariance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RawMaterialUtilization whereVarianceType($value)
 */
	class RawMaterialUtilization extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sale_id
 * @property string $receipt_number
 * @property string $content
 * @property numeric $subtotal
 * @property numeric $tax
 * @property numeric $discount
 * @property numeric $total
 * @property array<array-key, mixed> $payments
 * @property numeric $change_due
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $formatted_change_due
 * @property-read string $formatted_discount
 * @property-read string $formatted_subtotal
 * @property-read string $formatted_tax
 * @property-read string $formatted_total
 * @property-read \App\Models\Sale $sale
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt byPaymentMethod(string $method)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt dateRange($startDate, $endDate)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt minTotal($amount)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt thisMonth()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt today()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereChangeDue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt wherePayments($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereReceiptNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Receipt whereUpdatedAt($value)
 */
	class Receipt extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int $department_id
 * @property string|null $product_id
 * @property string $product_name
 * @property string $sku
 * @property string $product_type
 * @property numeric $cost_per_unit
 * @property string $uom
 * @property numeric $yield_quantity
 * @property int|null $preparation_time
 * @property string|null $instructions
 * @property string $status
 * @property string $created_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\Employee $createdBy
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DailyProduce> $dailyProduces
 * @property-read int|null $daily_produces_count
 * @property-read \App\Models\Department $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RecipeIngredient> $ingredients
 * @property-read int|null $ingredients_count
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionRecord> $productionRecords
 * @property-read int|null $production_records_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionRequest> $productionRequests
 * @property-read int|null $production_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RawMaterialUtilization> $rawMaterialUtilizations
 * @property-read int|null $raw_material_utilizations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCostPerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereInstructions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe wherePreparationTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereProductType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereSku($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Recipe whereYieldQuantity($value)
 */
	class Recipe extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $recipe_id
 * @property int $item_id
 * @property numeric $quantity
 * @property string $uom
 * @property numeric $cost_per_unit
 * @property numeric $waste_percentage Percentage of ingredient lost during preparation
 * @property int $sort_order
 * @property string|null $notes
 * @property string|null $preparation_notes Specific prep instructions for this ingredient
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\Recipe $recipe
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereCostPerUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient wherePreparationNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereRecipeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereUom($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient whereWastePercentage($value)
 */
	class RecipeIngredient extends \Eloquent {}
}

namespace App\Models{
/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryHistory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryHistory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalaryHistory query()
 */
	class SalaryHistory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $sales_shift_id
 * @property string|null $branch_id
 * @property int|null $department_id
 * @property int|null $table_id
 * @property string $sold_by
 * @property string $sale_number
 * @property \Illuminate\Support\Carbon $sale_time
 * @property numeric $subtotal
 * @property numeric $tax
 * @property numeric $discount
 * @property numeric $total
 * @property string $status
 * @property string $order_type
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> $payments
 * @property-read int|null $payments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\SaleItem> $saleItems
 * @property-read int|null $sale_items_count
 * @property-read \App\Models\SalesShift|null $salesShift
 * @property-read \App\Models\Employee $soldBy
 * @property-read \App\Models\Table|null $table
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereOrderType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereSaleNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereSaleTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereSalesShiftId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereSoldBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereTableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereTax($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Sale whereUpdatedAt($value)
 */
	class Sale extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sale_id
 * @property int|null $department_id
 * @property string $product_id
 * @property numeric $quantity
 * @property numeric $unit_price
 * @property numeric $subtotal
 * @property numeric $discount
 * @property numeric $total
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Department|null $department
 * @property-read \App\Models\Product $product
 * @property-read \App\Models\Sale $sale
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereSaleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereSubtotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereUnitPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SaleItem whereUpdatedAt($value)
 */
	class SaleItem extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int $department_id
 * @property string $employee_id
 * @property string $shift_number
 * @property \Illuminate\Support\Carbon $shift_date
 * @property string $shift_type
 * @property \Illuminate\Support\Carbon|null $clock_in
 * @property \Illuminate\Support\Carbon|null $clock_out
 * @property numeric $opening_cash
 * @property numeric $closing_cash
 * @property numeric $expected_cash
 * @property numeric $cash_variance
 * @property string $status
 * @property string|null $verified_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Employee $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductStock> $productStocks
 * @property-read int|null $product_stocks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sale> $sales
 * @property-read int|null $sales_count
 * @property-read \App\Models\Employee|null $verifiedBy
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereCashVariance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereClosingCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereExpectedCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereOpeningCash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereShiftDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereShiftNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SalesShift whereVerifiedBy($value)
 */
	class SalesShift extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int $department_id
 * @property string $employee_id
 * @property string $shift_number
 * @property \Illuminate\Support\Carbon $shift_date
 * @property string $shift_type
 * @property \Illuminate\Support\Carbon|null $clock_in
 * @property \Illuminate\Support\Carbon|null $clock_out
 * @property string $status
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\DailyProduce> $dailyProduces
 * @property-read int|null $daily_produces_count
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\Employee $employee
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionCallback> $productionCallbacks
 * @property-read int|null $production_callbacks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProductionRequest> $productionRequests
 * @property-read int|null $production_requests_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\RawMaterialUtilization> $rawMaterialUtilizations
 * @property-read int|null $raw_material_utilizations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereClockIn($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereClockOut($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereEmployeeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereShiftType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Shift whereUpdatedAt($value)
 */
	class Shift extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property int $item_id
 * @property numeric $quantity_available
 * @property numeric $quantity_reserved
 * @property numeric $quantity_damaged
 * @property numeric $average_cost
 * @property \Illuminate\Support\Carbon|null $last_stock_take_date
 * @property string $health_status
 * @property \Illuminate\Support\Carbon|null $expiry_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read mixed $available_quantity
 * @property-read mixed $reserved_quantity
 * @property-read mixed $total_quantity
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HealthCheck> $healthChecks
 * @property-read int|null $health_checks_count
 * @property-read \App\Models\Item $item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereAverageCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereExpiryDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereHealthStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereLastStockTakeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereQuantityAvailable($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereQuantityDamaged($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereQuantityReserved($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereUpdatedAt($value)
 */
	class Stock extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stock_id
 * @property string $type
 * @property numeric $quantity
 * @property numeric $quantity_before
 * @property numeric $quantity_after
 * @property string|null $reference_type
 * @property int|null $reference_id
 * @property string|null $moved_by
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $movement_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Employee|null $mover
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent|null $reference
 * @property-read \App\Models\Stock $stock
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereMovedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereMovementDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantityAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantityBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereReferenceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereReferenceType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereStockId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUpdatedAt($value)
 */
	class StockMovement extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $branch_id
 * @property string $stock_take_number
 * @property \Illuminate\Support\Carbon $stock_take_date
 * @property string $type
 * @property string $conducted_by
 * @property string $status
 * @property string|null $verified_by
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Branch $branch
 * @property-read \App\Models\Employee $conductor
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StockTakeDetail> $stockTakeDetails
 * @property-read int|null $stock_take_details_count
 * @property-read \App\Models\Employee|null $verifier
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake ofType(string $type)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereConductedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereStockTakeDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereStockTakeNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake whereVerifiedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTake withStatus(string $status)
 */
	class StockTake extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $stock_take_id
 * @property int $item_id
 * @property numeric $system_quantity
 * @property numeric $physical_quantity
 * @property numeric $variance
 * @property string $variance_type
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Item $item
 * @property-read \App\Models\StockTake $stockTake
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail wherePhysicalQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereStockTakeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereSystemQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereVariance($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockTakeDetail whereVarianceType($value)
 */
	class StockTakeDetail extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $branch_id
 * @property int|null $department_id
 * @property string $table_number
 * @property string|null $table_name
 * @property string $status
 * @property int $capacity
 * @property bool $is_active
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sale> $activeSales
 * @property-read int|null $active_sales_count
 * @property-read \App\Models\Branch|null $branch
 * @property-read \App\Models\Department|null $department
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Sale> $sales
 * @property-read int|null $sales_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table active()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table available()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table forBranch($branchId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table forDepartment($departmentId)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table occupied()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereBranchId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereCapacity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereDepartmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereTableName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereTableNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Table whereUpdatedAt($value)
 */
	class Table extends \Eloquent {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property string|null $two_factor_confirmed_at
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorConfirmedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorRecoveryCodes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereTwoFactorSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 */
	class User extends \Eloquent {}
}

