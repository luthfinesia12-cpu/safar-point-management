<?php

namespace App\Models;

use App\Enums\PaymentTransactionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $attributes = ['status' => 'pending_verification'];

    protected $fillable = ['purchase_order_id', 'bank_account_id', 'pic_id', 'amount', 'payment_date', 'payment_type', 'method', 'recipient', 'reference_number', 'proof_path', 'receipt_path', 'status', 'verified_by', 'verified_at', 'verification_notes'];

    protected $casts = ['status' => PaymentTransactionStatus::class, 'amount' => 'decimal:2', 'payment_date' => 'date', 'verified_at' => 'datetime'];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
