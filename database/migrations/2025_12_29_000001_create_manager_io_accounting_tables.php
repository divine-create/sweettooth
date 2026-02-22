<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Manager.io-inspired accounting tables migration
     * Adds comprehensive accounting features including:
     * - Account transfers
     * - Expense claims
     * - Sales workflow (quotes, orders, credit notes, delivery notes)
     * - Purchase workflow (quotes, orders, debit notes, goods receipts)
     * - Late payment fees and billable time
     * - Inventory kits and production orders
     */
    public function up(): void
    {
        // =====================================================
        // CORE ACCOUNTING TABLES
        // =====================================================

        // Inter-Account Transfers
        if (!Schema::hasTable('account_transfers')) {
            Schema::create('account_transfers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_bank_account_id')->constrained('bank_accounts');
                $table->foreignId('to_bank_account_id')->constrained('bank_accounts');
                $table->date('transfer_date');
                $table->decimal('amount', 15, 2);
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Expense Claims
        if (!Schema::hasTable('expense_claims')) {
            Schema::create('expense_claims', function (Blueprint $table) {
                $table->id();
                $table->uuid('employee_id');
                $table->foreign('employee_id')->references('id')->on('users');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('claim_date');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->string('status')->default('pending'); // pending, approved, rejected, paid
                $table->text('description')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('users');
                $table->timestamp('approved_at')->nullable();
                $table->foreignId('paid_via_bank_account_id')->nullable()->constrained('bank_accounts');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Expense Claim Items
        if (!Schema::hasTable('expense_claim_items')) {
            Schema::create('expense_claim_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('expense_claim_id')->constrained()->cascadeOnDelete();
                $table->date('expense_date');
                $table->string('category'); // travel, meals, supplies, etc.
                $table->text('description');
                $table->decimal('amount', 15, 2);
                $table->string('receipt_path')->nullable();
                $table->foreignId('gl_account_id')->nullable()->constrained('gl_accounts');
                $table->timestamps();
            });
        }

        // =====================================================
        // SALES & RECEIVABLES TABLES
        // =====================================================

        // Sales Quotes
        if (!Schema::hasTable('sales_quotes')) {
            Schema::create('sales_quotes', function (Blueprint $table) {
                $table->id();
                $table->string('quote_number')->unique();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('quote_date');
                $table->date('valid_until')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, sent, accepted, rejected, expired
                $table->text('notes')->nullable();
                $table->text('terms_conditions')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('sales_quote_items')) {
            Schema::create('sales_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_quote_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Sales Orders
        if (!Schema::hasTable('sales_orders')) {
            Schema::create('sales_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('sales_quote_id')->nullable()->constrained();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('discount_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('pending'); // pending, confirmed, processing, shipped, delivered, cancelled
                $table->text('notes')->nullable();
                $table->text('shipping_address')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('sales_order_items')) {
            Schema::create('sales_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity_ordered', 15, 4);
                $table->decimal('quantity_delivered', 15, 4)->default(0);
                $table->decimal('quantity_invoiced', 15, 4)->default(0);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('discount_percent', 5, 2)->default(0);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // Credit Notes
        if (!Schema::hasTable('credit_notes')) {
            Schema::create('credit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('credit_note_number')->unique();
                $table->foreignId('sale_id')->nullable()->constrained('sales');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('credit_note_date');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, issued, applied, voided
                $table->text('reason')->nullable();
                $table->decimal('amount_applied', 15, 2)->default(0);
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('credit_note_items')) {
            Schema::create('credit_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->timestamps();
            });
        }

        // Delivery Notes
        if (!Schema::hasTable('delivery_notes')) {
            Schema::create('delivery_notes', function (Blueprint $table) {
                $table->id();
                $table->string('delivery_note_number')->unique();
                $table->foreignId('sales_order_id')->nullable()->constrained();
                $table->foreignId('sale_id')->nullable()->constrained('sales');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('delivery_date');
                $table->text('delivery_address')->nullable();
                $table->string('status')->default('pending'); // pending, dispatched, delivered, returned
                $table->string('carrier')->nullable();
                $table->string('tracking_number')->nullable();
                $table->text('notes')->nullable();
                $table->uuid('delivered_by')->nullable();
                $table->foreign('delivered_by')->references('id')->on('users');
                $table->timestamp('delivered_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('delivery_note_items')) {
            Schema::create('delivery_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('delivery_note_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->foreignId('sales_order_item_id')->nullable()->constrained();
                $table->string('description');
                $table->decimal('quantity', 15, 4);
                $table->timestamps();
            });
        }

        // Late Payment Fees
        if (!Schema::hasTable('late_payment_fees')) {
            Schema::create('late_payment_fees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales');
                $table->decimal('fee_amount', 15, 2);
                $table->decimal('fee_rate', 5, 2)->nullable(); // Percentage rate if applicable
                $table->date('fee_date');
                $table->integer('days_overdue');
                $table->string('status')->default('pending'); // pending, invoiced, paid, waived
                $table->text('notes')->nullable();
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Billable Time Entries
        if (!Schema::hasTable('billable_time_entries')) {
            Schema::create('billable_time_entries', function (Blueprint $table) {
                $table->id();
                $table->uuid('employee_id');
                $table->foreign('employee_id')->references('id')->on('users');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->string('project_name')->nullable();
                $table->date('entry_date');
                $table->decimal('hours', 8, 2);
                $table->decimal('hourly_rate', 15, 2);
                $table->decimal('total_amount', 15, 2);
                $table->text('description')->nullable();
                $table->boolean('billable')->default(true);
                $table->boolean('billed')->default(false);
                $table->foreignId('sale_id')->nullable()->constrained('sales');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Withholding Tax Receipts
        if (!Schema::hasTable('withholding_tax_receipts')) {
            Schema::create('withholding_tax_receipts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sale_id')->constrained('sales');
                $table->decimal('gross_amount', 15, 2);
                $table->decimal('tax_rate', 5, 2);
                $table->decimal('withheld_amount', 15, 2);
                $table->decimal('net_amount', 15, 2);
                $table->date('receipt_date');
                $table->string('certificate_number')->nullable();
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // =====================================================
        // PURCHASES & PAYABLES TABLES
        // =====================================================

        // Enhance suppliers table if exists
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                if (!Schema::hasColumn('suppliers', 'tax_id')) {
                    $table->string('tax_id')->nullable();
                }
                if (!Schema::hasColumn('suppliers', 'payment_terms_days')) {
                    $table->integer('payment_terms_days')->default(30);
                }
                if (!Schema::hasColumn('suppliers', 'outstanding_balance')) {
                    $table->decimal('outstanding_balance', 15, 2)->default(0);
                }
            });
        }

        // Purchase Quotes (RFQs)
        if (!Schema::hasTable('purchase_quotes')) {
            Schema::create('purchase_quotes', function (Blueprint $table) {
                $table->id();
                $table->string('quote_number')->unique();
                $table->foreignId('supplier_id')->nullable()->constrained();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('quote_date');
                $table->date('valid_until')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, sent, received, accepted, rejected
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('purchase_quote_items')) {
            Schema::create('purchase_quote_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_quote_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->timestamps();
            });
        }

        // Purchase Orders
        if (!Schema::hasTable('purchase_orders')) {
            Schema::create('purchase_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->foreignId('supplier_id')->nullable()->constrained();
                $table->foreignId('purchase_quote_id')->nullable()->constrained();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('order_date');
                $table->date('expected_delivery_date')->nullable();
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, sent, confirmed, partially_received, received, cancelled
                $table->text('notes')->nullable();
                $table->text('shipping_address')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('users');
                $table->timestamp('approved_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('purchase_order_items')) {
            Schema::create('purchase_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity_ordered', 15, 4);
                $table->decimal('quantity_received', 15, 4)->default(0);
                $table->decimal('quantity_invoiced', 15, 4)->default(0);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->timestamps();
            });
        }

        // Debit Notes (Purchase Returns)
        if (!Schema::hasTable('debit_notes')) {
            Schema::create('debit_notes', function (Blueprint $table) {
                $table->id();
                $table->string('debit_note_number')->unique();
                $table->foreignId('supplier_id')->constrained();
                $table->foreignId('purchase_id')->nullable()->constrained('purchases');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('debit_note_date');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('tax_amount', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->string('status')->default('draft'); // draft, issued, applied, voided
                $table->text('reason')->nullable();
                $table->decimal('amount_applied', 15, 2)->default(0);
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('debit_note_items')) {
            Schema::create('debit_note_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('debit_note_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->string('description');
                $table->decimal('quantity', 15, 4);
                $table->decimal('unit_price', 15, 2);
                $table->decimal('tax_rate', 5, 2)->default(0);
                $table->decimal('line_total', 15, 2);
                $table->timestamps();
            });
        }

        // Goods Receipts
        if (!Schema::hasTable('goods_receipts')) {
            Schema::create('goods_receipts', function (Blueprint $table) {
                $table->id();
                $table->string('receipt_number')->unique();
                $table->foreignId('purchase_order_id')->nullable()->constrained();
                $table->foreignId('purchase_id')->nullable()->constrained('purchases');
                $table->foreignId('supplier_id')->constrained();
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('receipt_date');
                $table->string('status')->default('pending'); // pending, inspecting, accepted, rejected
                $table->text('notes')->nullable();
                $table->uuid('received_by')->nullable();
                $table->foreign('received_by')->references('id')->on('users');
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('goods_receipt_items')) {
            Schema::create('goods_receipt_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id')->nullable();
                $table->foreign('product_id')->references('id')->on('products');
                $table->foreignId('purchase_order_item_id')->nullable()->constrained();
                $table->string('description');
                $table->decimal('quantity_expected', 15, 4);
                $table->decimal('quantity_received', 15, 4);
                $table->decimal('quantity_accepted', 15, 4)->default(0);
                $table->decimal('quantity_rejected', 15, 4)->default(0);
                $table->text('rejection_reason')->nullable();
                $table->timestamps();
            });
        }

        // =====================================================
        // INVENTORY ENHANCEMENT TABLES
        // =====================================================

        // Inventory Kits (Bundling)
        if (!Schema::hasTable('inventory_kits')) {
            Schema::create('inventory_kits', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique()->nullable();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('kit_price', 15, 2)->default(0);
                $table->boolean('active')->default(true);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('inventory_kit_components')) {
            Schema::create('inventory_kit_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('inventory_kit_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id');
                $table->foreign('product_id')->references('id')->on('products');
                $table->decimal('quantity', 15, 4);
                $table->timestamps();
            });
        }

        // Inventory Adjustments (consolidated tracking)
        if (!Schema::hasTable('inventory_adjustments')) {
            Schema::create('inventory_adjustments', function (Blueprint $table) {
                $table->id();
                $table->string('adjustment_number')->unique();
                $table->uuid('product_id');
                $table->foreign('product_id')->references('id')->on('products');
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->string('type'); // transfer, write_off, adjustment, count, production
                $table->decimal('quantity_before', 15, 4);
                $table->decimal('quantity_change', 15, 4);
                $table->decimal('quantity_after', 15, 4);
                $table->decimal('unit_cost', 15, 2)->nullable();
                $table->decimal('cost_impact', 15, 2)->nullable();
                $table->date('adjustment_date');
                $table->text('reason')->nullable();
                $table->uuid('approved_by')->nullable();
                $table->foreign('approved_by')->references('id')->on('users');
                $table->timestamp('approved_at')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // Production Orders (enhanced)
        if (!Schema::hasTable('production_orders')) {
            Schema::create('production_orders', function (Blueprint $table) {
                $table->id();
                $table->string('order_number')->unique();
                $table->uuid('output_product_id');
                $table->foreign('output_product_id')->references('id')->on('products');
                $table->decimal('output_quantity', 15, 4);
                $table->uuid('branch_id')->nullable();
                $table->foreign('branch_id')->references('id')->on('branches');
                $table->date('planned_date');
                $table->date('completed_date')->nullable();
                $table->string('status')->default('planned'); // planned, in_progress, completed, cancelled
                $table->decimal('total_material_cost', 15, 2)->default(0);
                $table->decimal('total_labor_cost', 15, 2)->default(0);
                $table->decimal('total_overhead_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->text('notes')->nullable();
                $table->uuid('created_by')->nullable();
                $table->foreign('created_by')->references('id')->on('users');
                $table->string('gl_posting_status')->default('pending');
                $table->foreignId('gl_entry_id')->nullable()->constrained('gl_entries');
                $table->text('gl_posting_error')->nullable();
                $table->timestamp('gl_posted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('production_order_components')) {
            Schema::create('production_order_components', function (Blueprint $table) {
                $table->id();
                $table->foreignId('production_order_id')->constrained()->cascadeOnDelete();
                $table->uuid('product_id');
                $table->foreign('product_id')->references('id')->on('products');
                $table->decimal('quantity_required', 15, 4);
                $table->decimal('quantity_used', 15, 4)->default(0);
                $table->decimal('unit_cost', 15, 2)->default(0);
                $table->decimal('total_cost', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        // =====================================================
        // ENHANCE EXISTING SALES TABLE
        // =====================================================

        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (!Schema::hasColumn('sales', 'sales_order_id')) {
                    $table->foreignId('sales_order_id')->nullable()->constrained();
                }
                if (!Schema::hasColumn('sales', 'due_date')) {
                    $table->date('due_date')->nullable();
                }
            });
        }

        // =====================================================
        // ENHANCE EXISTING PURCHASES TABLE
        // =====================================================

        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (!Schema::hasColumn('purchases', 'purchase_order_id')) {
                    $table->foreignId('purchase_order_id')->nullable()->constrained();
                }
                if (!Schema::hasColumn('purchases', 'due_date')) {
                    $table->date('due_date')->nullable();
                }
                if (!Schema::hasColumn('purchases', 'three_way_match_status')) {
                    $table->string('three_way_match_status')->nullable(); // pending, matched, discrepancy
                }
            });
        }
    }

    public function down(): void
    {
        // Drop in reverse order due to foreign key constraints

        // Remove enhancements from purchases
        if (Schema::hasTable('purchases')) {
            Schema::table('purchases', function (Blueprint $table) {
                if (Schema::hasColumn('purchases', 'purchase_order_id')) {
                    $table->dropForeign(['purchase_order_id']);
                    $table->dropColumn('purchase_order_id');
                }
                if (Schema::hasColumn('purchases', 'due_date')) {
                    $table->dropColumn('due_date');
                }
                if (Schema::hasColumn('purchases', 'three_way_match_status')) {
                    $table->dropColumn('three_way_match_status');
                }
            });
        }

        // Remove enhancements from sales
        if (Schema::hasTable('sales')) {
            Schema::table('sales', function (Blueprint $table) {
                if (Schema::hasColumn('sales', 'sales_order_id')) {
                    $table->dropForeign(['sales_order_id']);
                    $table->dropColumn('sales_order_id');
                }
                if (Schema::hasColumn('sales', 'due_date')) {
                    $table->dropColumn('due_date');
                }
            });
        }

        // Drop inventory tables
        Schema::dropIfExists('production_order_components');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('inventory_adjustments');
        Schema::dropIfExists('inventory_kit_components');
        Schema::dropIfExists('inventory_kits');

        // Drop purchase tables
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
        Schema::dropIfExists('debit_note_items');
        Schema::dropIfExists('debit_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_quote_items');
        Schema::dropIfExists('purchase_quotes');

        // Drop sales tables
        Schema::dropIfExists('withholding_tax_receipts');
        Schema::dropIfExists('billable_time_entries');
        Schema::dropIfExists('late_payment_fees');
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_quote_items');
        Schema::dropIfExists('sales_quotes');

        // Drop core accounting tables
        Schema::dropIfExists('expense_claim_items');
        Schema::dropIfExists('expense_claims');
        Schema::dropIfExists('account_transfers');

        // Remove supplier enhancements
        if (Schema::hasTable('suppliers')) {
            Schema::table('suppliers', function (Blueprint $table) {
                $columns = ['tax_id', 'payment_terms_days', 'outstanding_balance'];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('suppliers', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
