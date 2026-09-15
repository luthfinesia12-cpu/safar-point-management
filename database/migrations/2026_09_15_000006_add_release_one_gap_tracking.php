<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('purchase_requests', 'cancellation_reason')) {
            Schema::table('purchase_requests', function (Blueprint $table): void {
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
            });
        }

        if (! Schema::hasColumn('purchase_orders', 'cancellation_reason')) {
            Schema::table('purchase_orders', function (Blueprint $table): void {
                $table->text('cancellation_reason')->nullable();
                $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('cancelled_at')->nullable();
                $table->text('close_short_reason')->nullable();
                $table->foreignId('close_short_requested_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('close_short_requested_at')->nullable();
                $table->foreignId('close_short_approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('close_short_approved_at')->nullable();
            });
        }

        if (! Schema::hasTable('document_status_histories')) {
            Schema::create('document_status_histories', function (Blueprint $table): void {
                $table->id();
                $table->string('document_type');
                $table->unsignedBigInteger('document_id');
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason')->nullable();
                $table->timestamp('changed_at');
                $table->index(['document_type', 'document_id', 'changed_at'], 'doc_status_hist_idx');
            });
        }

        if (! Schema::hasTable('document_workflow_requests')) {
            Schema::create('document_workflow_requests', function (Blueprint $table): void {
                $table->id();
                $table->string('document_type');
                $table->unsignedBigInteger('document_id');
                $table->string('action');
                $table->string('status')->default('submitted');
                $table->foreignId('requested_by')->constrained('users');
                $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('reason');
                $table->text('decision_notes')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
                $table->index(['document_type', 'document_id', 'action', 'status'], 'doc_workflow_idx');
            });
        }

        Schema::create('master_import_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('master_type');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            $table->string('status')->default('preview');
            $table->unsignedInteger('row_count')->default(0);
            $table->json('valid_rows')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('recipient_id')->constrained('users');
            $table->string('title');
            $table->text('message');
            $table->json('context')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->text('failure_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->foreignId('resent_from_id')->nullable()->constrained('notification_deliveries')->nullOnDelete();
            $table->timestamps();
            $table->index(['recipient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('master_import_batches');
        Schema::dropIfExists('document_workflow_requests');
        Schema::dropIfExists('document_status_histories');
        Schema::table('purchase_orders', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by']);
            $table->dropForeign(['close_short_requested_by']);
            $table->dropForeign(['close_short_approved_by']);
            $table->dropColumn(['cancellation_reason', 'cancelled_by', 'cancelled_at', 'close_short_reason', 'close_short_requested_by', 'close_short_requested_at', 'close_short_approved_by', 'close_short_approved_at']);
        });
        Schema::table('purchase_requests', function (Blueprint $table): void {
            $table->dropForeign(['cancelled_by']);
            $table->dropColumn(['cancellation_reason', 'cancelled_by', 'cancelled_at']);
        });
    }
};
