<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 10);
            $table->string('period', 7);
            $table->unsignedInteger('last_number')->default(0);
            $table->unique(['document_type', 'period']);
        });

        foreach ([
            'categories' => ['code' => 'CAT-001', 'name' => 'string'],
            'brands' => ['code' => 'BRD-001', 'name' => 'string'],
            'units' => ['code' => 'UNT-001', 'name' => 'string'],
            'warehouses' => ['code' => 'WH-001', 'name' => 'string'],
        ] as $tableName => $columns) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->text('address')->nullable();
                $table->string('pic')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('contact')->nullable();
            $table->text('address')->nullable();
            $table->string('bank_account')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('sku')->unique();
            $table->string('name');
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('unit_id')->constrained();
            $table->string('specification')->nullable();
            $table->string('barcode')->nullable()->unique();
            $table->decimal('minimum_stock', 15, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['is_active', 'name']);
        });

        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['bank_name', 'account_number']);
        });

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable()->unique();
            $table->foreignId('requester_id')->constrained('users');
            $table->string('department')->nullable();
            $table->string('priority')->default('normal');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 3);
            $table->decimal('estimated_price', 15, 2)->default(0);
            $table->text('specification')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users');
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->index(['purchase_request_id', 'decision']);
        });

        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable()->unique();
            $table->foreignId('purchase_request_id')->constrained();
            $table->foreignId('supplier_id')->constrained();
            $table->foreignId('purchaser_id')->constrained('users');
            $table->date('order_date');
            $table->date('expected_arrival')->nullable();
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->string('payment_status')->default('unpaid')->index();
            $table->timestamps();
        });

        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });

        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable()->unique();
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('receiver_id')->constrained('users');
            $table->date('received_date');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 3);
            $table->decimal('damaged_quantity', 15, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('quantity_change', 15, 3);
            $table->decimal('balance_after', 15, 3);
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->foreignId('posted_by')->constrained('users');
            $table->timestamp('posted_at');
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
        });

        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->nullable()->unique();
            $table->foreignId('warehouse_id')->constrained();
            $table->foreignId('requester_id')->constrained('users');
            $table->string('status')->default('draft')->index();
            $table->string('adjustment_type');
            $table->text('reason');
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity_change', 15, 3);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pic_id')->constrained('users');
            $table->decimal('amount', 15, 2);
            $table->date('payment_date');
            $table->string('payment_type')->default('dp');
            $table->string('method');
            $table->string('recipient')->nullable();
            $table->string('reference_number')->nullable();
            $table->string('proof_path')->nullable();
            $table->string('receipt_path')->nullable();
            $table->string('status')->default('pending_verification')->index();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->timestamps();
            $table->index(['purchase_order_id', 'status']);
        });
    }

    public function down(): void
    {
        foreach (['payments', 'adjustment_items', 'adjustments', 'stock_ledgers', 'goods_receipt_items', 'goods_receipts', 'purchase_order_items', 'purchase_orders', 'purchase_request_approvals', 'purchase_request_items', 'purchase_requests', 'bank_accounts', 'products', 'suppliers', 'warehouses', 'units', 'brands', 'categories', 'document_sequences'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
