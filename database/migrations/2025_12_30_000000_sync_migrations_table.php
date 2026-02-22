<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // List of migrations that have already been applied to the database but weren't recorded
        $appliedMigrations = [
            '2025_10_27_000838_create_approval_requests_table',
            '2025_10_27_042254_remove_business_type_from_global_business_configurations',
            '2025_10_27_042747_remove_columns_from_global_business_configurations',
            '2025_11_02_021715_create_product_dispatch_callbacks_table',
            '2025_11_02_104432_create_production_callbacks_table',
            '2025_11_02_110718_add_sales_shift_and_received_quantity_to_product_dispatches_table',
            '2025_11_06_055946_create_tables_table',
            '2025_11_06_055949_add_table_id_to_sales_table',
            '2025_11_06_055953_add_table_management_settings_to_branches_table',
            '2025_11_06_063045_add_table_management_to_departments_table',
            '2025_11_06_063047_add_department_id_to_sale_items_table',
            '2025_11_06_063247_add_department_id_to_tables_table',
            '2025_11_06_175546_make_product_dispatch_id_nullable_in_product_dispatch_callbacks_table',
            '2025_11_07_175717_add_sales_department_id_to_product_dispatches_table',
            '2025_11_07_183133_remove_redundant_yield_fields_from_products_table',
            '2025_11_09_053147_create_employee_leave_balances_table',
            '2025_11_09_053149_create_leave_applications_table',
            '2025_11_09_053151_create_probation_reviews_table',
            '2025_11_09_053154_create_salary_history_table',
            '2025_11_09_053507_create_employee_stepouts_table',
            '2025_11_09_103341_create_employee_leave_allocations_table',
            '2025_11_11_055910_add_branch_id_to_leave_application_table',
            '2025_11_14_000001_create_department_reports_table',
            '2025_11_14_000002_create_compiled_reports_table',
            '2025_11_14_000003_create_report_schedules_table',
            '2025_11_14_000004_create_report_distributions_table',
            '2025_11_14_000005_create_report_templates_table',
            '2025_11_14_000006_create_compiled_report_department_report_table',
            '2025_11_14_225458_create_product_transfers_table',
            '2025_11_15_170737_add_department_id_to_products_table',
            '2025_11_15_173556_add_is_siper_admin_request_to_production_requests_table',
            '2025_11_15_174249_add_is_super_admin_to_employees_table',
            '2025_11_24_032703_update_audit_logs_table_add_missing_columns',
            '2025_11_24_032823_update_audit_logs_setting_type_nullable',
            '2025_11_24_033049_alter_audit_logs_details_nullable',
            '2025_11_24_034645_create_approval_audit_requests_table',
            '2025_11_24_035053_add_branch_id_to_approval_audit_requests_table',
            '2025_12_02_000001_fix_audit_logs_auditable_id_column',
            '2025_12_02_000002_increase_audit_logs_action_column_size',
            '2025_12_02_100000_recreate_approval_requests_table',
            '2025_12_03_004522_make_audit_log_description_string_text',
            '2025_12_04_103748_drop_currency_and_exchange_rate_from_purchases',
            '2025_12_04_110900_add_status_to_purchases_table',
            '2025_12_04_111720_create_purchase_approval_requests_table',
            '2025_12_07_000002_add_callback_indexes',
            '2025_12_09_185202_add_yield_fields_to_recipes_table',
            '2025_12_12_000001_add_protection_to_roles',
            '2025_12_12_000002_create_role_permission_audit_logs_table',
            '2025_12_13_100001_create_gl_accounts_table',
            '2025_12_13_100002_create_accounting_periods_table',
            '2025_12_13_100003_create_gl_entries_table',
            '2025_12_13_100004_create_bank_accounts_table',
            '2025_12_13_100005_create_daily_bank_positions_table',
            '2025_12_13_100006_create_daily_bank_transactions_table',
            '2025_12_13_100007_create_cash_positions_table',
            '2025_12_15_000001_add_reconciliation_fields_to_bank_tables',
            '2025_12_15_000001_add_unit_cost_to_production_records',
            '2025_12_15_000002_create_bank_reconciliations_tables',
            '2025_12_15_000002_ensure_accounting_fields_in_sales_and_payments',
            '2025_12_15_000003_link_sales_payments_to_bank_accounts',
            '2025_12_15_120000_add_gl_posting_status_to_transaction_tables',
            '2025_12_15_120001_add_branch_id_to_tables',
            '2025_12_15_181510_alter_entry_type_in_gl_entries_table',
            '2025_12_16_000001_add_workflow_metadata_to_shifts',
            '2025_12_16_000002_add_stock_workflow_fields',
            '2025_12_16_000003_create_workflow_audit_logs_table',
            '2025_12_17_041526_create_shift_configurations_table',
            '2025_12_17_041725_add_auto_clock_out_fields_to_shifts_table',
            '2025_12_20_000001_add_progress_fields_to_existing_tables',
            '2025_12_20_000002_create_production_milestones_and_alerts_tables',
            '2025_12_20_000004_add_quality_fields_to_production_records',
            '2025_12_22_173350_make_item_request_morph_columns_nullable',
            '2025_12_23_100822_add_production_record_id_to_product_dispatches_table',
            '2025_12_23_181627_create_appraisal_system_tables',
            '2025_12_24_092911_make_appraisal_cycle_and_template_nullable_in_appraisals_table',
            '2025_12_26_220705_alter_appraisal_templates_created_by_column',
            '2025_12_26_225338_drop_appraisal_templates_table',
            '2025_12_27_061851_create_appraisals_table',
            '2025_12_28_235959_create_suppliers_table',
            '2025_12_28_235960_create_supplier_contacts_table',
            '2025_12_28_235961_create_supplier_terms_table',
            '2025_12_28_235962_create_supplier_bank_accounts_table',
            '2025_12_28_235963_create_supplier_documents_table',
            '2025_12_28_235964_create_supplier_performance_history_table',
            '2025_12_28_235965_add_supplier_id_to_purchases_table',
            '2025_12_29_000001_create_manager_io_accounting_tables',
            '2025_12_29_000002_make_item_request_id_nullable_in_production_requests',
            '2025_12_29_000003_fix_accounting_periods_uuid_fields',
            '2025_12_29_000004_fix_gl_entries_uuid_fields',
            '2025_12_29_075219_make_shift_id_nullable_in_production_requests_for_pos',
            '2025_12_29_113100_add_approved_to_production_requests_status',
        ];

        $batchNum = DB::table('migrations')->max('batch') + 1;

        foreach ($appliedMigrations as $migration) {
            if (!DB::table('migrations')->where('migration', $migration)->exists()) {
                DB::table('migrations')->insert([
                    'migration' => $migration,
                    'batch' => $batchNum,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete from migrations table on rollback
        // This is a sync operation, not a real migration
    }
};
