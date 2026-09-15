<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustment_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('adjustment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approver_id')->constrained('users');
            $table->string('decision');
            $table->text('notes')->nullable();
            $table->timestamp('decided_at');
            $table->timestamps();
            $table->index(['adjustment_id', 'decision']);
        });

        Schema::create('purchase_order_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->constrained('users');
            $table->unsignedInteger('version');
            $table->string('status')->default('submitted');
            $table->boolean('material_changed')->default(true);
            $table->text('reason');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->json('snapshot');
            $table->timestamps();
            $table->unique(['purchase_order_id', 'version']);
        });

        Schema::create('purchase_order_revision_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_revision_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_price', 15, 2);
            $table->timestamps();
        });

        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('document_type');
            $table->unsignedBigInteger('document_id');
            $table->foreignId('uploaded_by')->constrained('users');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('replaced_attachment_id')->nullable()->constrained('document_attachments')->nullOnDelete();
            $table->timestamps();
            $table->index(['document_type', 'document_id']);
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('document_attachments');
        Schema::dropIfExists('purchase_order_revision_items');
        Schema::dropIfExists('purchase_order_revisions');
        Schema::dropIfExists('adjustment_approvals');
    }
};
