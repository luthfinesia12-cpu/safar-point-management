<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Enums\PaymentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Adjustment;
use App\Models\DocumentStatusHistory;
use App\Models\DocumentWorkflowRequest;
use App\Models\GoodsReceipt;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderRevision;
use App\Models\PurchaseRequest;
use App\Models\StockLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProcurementService
{
    public function __construct(private DocumentNumberService $numbers) {}

    public function createPurchaseRequest(array $data, int $requesterId): PurchaseRequest
    {
        return DB::transaction(function () use ($data, $requesterId): PurchaseRequest {
            $request = PurchaseRequest::create([
                'requester_id' => $requesterId,
                'department' => $data['department'] ?? null,
                'priority' => $data['priority'] ?? 'normal',
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);
            $request->items()->createMany($data['items']);

            return $request->load('items');
        });
    }

    public function submitPurchaseRequest(PurchaseRequest $request): PurchaseRequest
    {
        if ($request->status !== DocumentStatus::Draft || $request->items()->doesntExist()) {
            throw ValidationException::withMessages(['status' => 'PR harus memiliki item dan berstatus draft.']);
        }
        $this->recordStatus($request, DocumentStatus::Submitted, $request->requester_id);
        $request->update(['document_number' => $request->document_number ?: $this->numbers->next('PR'), 'status' => DocumentStatus::Submitted, 'submitted_at' => now()]);

        return $request->fresh();
    }

    public function decidePurchaseRequest(PurchaseRequest $request, int $approverId, string $decision, ?string $notes = null): PurchaseRequest
    {
        if (! in_array($decision, ['approved', 'rejected'], true) || $request->status !== DocumentStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'PR tidak sedang menunggu persetujuan.']);
        }
        if ($request->requester_id === $approverId) {
            throw ValidationException::withMessages(['approver' => 'Pembuat PR tidak boleh menyetujui PR sendiri.']);
        }

        return DB::transaction(function () use ($request, $approverId, $decision, $notes): PurchaseRequest {
            $request->approvals()->create(['approver_id' => $approverId, 'decision' => $decision, 'notes' => $notes, 'decided_at' => now()]);
            $this->recordStatus($request, $decision === 'approved' ? DocumentStatus::Approved : DocumentStatus::Rejected, $approverId, $notes);
            $request->update(['status' => $decision === 'approved' ? DocumentStatus::Approved : DocumentStatus::Rejected]);

            return $request->fresh();
        });
    }

    public function createPurchaseOrder(array $data, int $purchaserId): PurchaseOrder
    {
        $request = PurchaseRequest::with('items')->findOrFail($data['purchase_request_id']);
        if ($request->status !== DocumentStatus::Approved) {
            throw ValidationException::withMessages(['purchase_request_id' => 'PO hanya dapat dibuat dari PR yang disetujui.']);
        }

        return DB::transaction(function () use ($data, $purchaserId, $request): PurchaseOrder {
            $items = collect($data['items']);
            $subtotal = $items->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price']);
            $total = $subtotal - (float) ($data['discount'] ?? 0) + (float) ($data['tax'] ?? 0) + (float) ($data['shipping_cost'] ?? 0);
            $order = PurchaseOrder::create([
                'document_number' => $this->numbers->next('PO'),
                'purchase_request_id' => $request->id,
                'supplier_id' => $data['supplier_id'],
                'purchaser_id' => $purchaserId,
                'order_date' => $data['order_date'] ?? now()->toDateString(),
                'expected_arrival' => $data['expected_arrival'] ?? null,
                'shipping_address' => $data['shipping_address'] ?? null,
                'notes' => $data['notes'] ?? null,
                'discount' => $data['discount'] ?? 0,
                'tax' => $data['tax'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'total' => $total,
                'status' => DocumentStatus::Approved,
            ]);
            $order->items()->createMany($items->all());
            $this->recordStatus($order, DocumentStatus::Approved, $purchaserId);

            return $order->load('items');
        });
    }

    public function postGoodsReceipt(array $data, int $receiverId): GoodsReceipt
    {
        $order = PurchaseOrder::with('items')->findOrFail($data['purchase_order_id']);
        if ($order->status === DocumentStatus::Cancelled) {
            throw ValidationException::withMessages(['purchase_order_id' => 'PO dibatalkan tidak dapat menerima barang.']);
        }

        return DB::transaction(function () use ($data, $receiverId, $order): GoodsReceipt {
            $receipt = GoodsReceipt::create(['document_number' => $this->numbers->next('GR'), 'purchase_order_id' => $order->id, 'warehouse_id' => $data['warehouse_id'], 'receiver_id' => $receiverId, 'received_date' => $data['received_date'] ?? now()->toDateString(), 'notes' => $data['notes'] ?? null, 'status' => DocumentStatus::Approved, 'posted_at' => now(), 'posted_by' => $receiverId]);
            foreach ($data['items'] as $item) {
                $ordered = (float) $order->items->firstWhere('product_id', $item['product_id'])?->quantity;
                $received = (float) GoodsReceipt::where('purchase_order_id', $order->id)
                    ->where('status', DocumentStatus::Approved)
                    ->whereHas('items', fn ($query) => $query->where('product_id', $item['product_id']))
                    ->with('items')
                    ->get()
                    ->flatMap->items
                    ->where('product_id', $item['product_id'])
                    ->sum('quantity');
                if ($received + (float) $item['quantity'] > $ordered) {
                    throw ValidationException::withMessages(['items' => 'Jumlah penerimaan melebihi jumlah PO.']);
                }
                $receipt->items()->create($item);
                $this->postStock($item['product_id'], $data['warehouse_id'], (float) $item['quantity'], 'goods_receipt', $receipt->id, $receiverId);
            }

            return $receipt->load('items');
        });
    }

    public function createPayment(array $data, int $picId): Payment
    {
        $order = PurchaseOrder::findOrFail($data['purchase_order_id']);
        if ($order->status === DocumentStatus::Cancelled) {
            throw ValidationException::withMessages(['purchase_order_id' => 'PO dibatalkan tidak dapat dibayar.']);
        }
        if ($data['method'] === 'transfer' && empty($data['bank_account_id'])) {
            throw ValidationException::withMessages(['bank_account_id' => 'Rekening sumber wajib untuk transfer.']);
        }
        if ($data['method'] === 'transfer' && empty($data['proof_path'])) {
            throw ValidationException::withMessages(['proof_path' => 'Bukti transfer wajib untuk pembayaran transfer.']);
        }
        if ($data['method'] === 'cash' && blank($data['recipient'] ?? null)) {
            throw ValidationException::withMessages(['recipient' => 'Penerima wajib untuk pembayaran tunai.']);
        }
        $verified = (float) $order->payments()->where('status', PaymentTransactionStatus::Verified)->sum('amount');
        if ($verified + (float) $data['amount'] > (float) $order->total) {
            throw ValidationException::withMessages(['amount' => 'Pembayaran melebihi total PO.']);
        }

        return $order->payments()->create(array_merge($data, ['pic_id' => $picId, 'status' => PaymentTransactionStatus::PendingVerification]));
    }

    public function createAdjustment(array $data, int $requesterId): Adjustment
    {
        return DB::transaction(function () use ($data, $requesterId): Adjustment {
            $adjustment = Adjustment::create([
                'warehouse_id' => $data['warehouse_id'],
                'requester_id' => $requesterId,
                'adjustment_type' => $data['adjustment_type'],
                'reason' => $data['reason'],
            ]);
            $adjustment->items()->createMany($data['items']);

            return $adjustment->load('items');
        });
    }

    public function submitAdjustment(Adjustment $adjustment): Adjustment
    {
        if ($adjustment->status !== DocumentStatus::Draft || $adjustment->items()->doesntExist()) {
            throw ValidationException::withMessages(['status' => 'ADJ harus memiliki item dan berstatus draft.']);
        }

        $adjustment->update([
            'document_number' => $adjustment->document_number ?: $this->numbers->next('ADJ'),
            'status' => DocumentStatus::Submitted,
        ]);

        return $adjustment->fresh();
    }

    public function decideAdjustment(Adjustment $adjustment, int $approverId, string $decision, ?string $notes = null): Adjustment
    {
        if (! in_array($decision, ['approved', 'rejected'], true) || $adjustment->status !== DocumentStatus::Submitted) {
            throw ValidationException::withMessages(['status' => 'ADJ tidak sedang menunggu persetujuan.']);
        }
        if ($adjustment->requester_id === $approverId) {
            throw ValidationException::withMessages(['approver' => 'Pembuat ADJ tidak boleh menyetujui ADJ sendiri.']);
        }

        return DB::transaction(function () use ($adjustment, $approverId, $decision, $notes): Adjustment {
            $adjustment->load('items');
            $adjustment->approvals()->create(['approver_id' => $approverId, 'decision' => $decision, 'notes' => $notes, 'decided_at' => now()]);
            if ($decision === 'rejected') {
                $adjustment->update(['status' => DocumentStatus::Rejected]);

                return $adjustment->fresh();
            }

            foreach ($adjustment->items as $item) {
                $this->postStock($item->product_id, $adjustment->warehouse_id, (float) $item->quantity_change, 'adjustment', $adjustment->id, $approverId);
            }
            $adjustment->update(['status' => DocumentStatus::Approved, 'posted_at' => now(), 'posted_by' => $approverId]);

            return $adjustment->fresh();
        });
    }

    public function revisePurchaseOrder(PurchaseOrder $order, array $data, int $changedBy): PurchaseOrderRevision|PurchaseOrder
    {
        if ($order->status !== DocumentStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'PO hanya dapat direvisi setelah diterbitkan dan sebelum dibatalkan.']);
        }

        $order->load('items');
        $items = collect($data['items'] ?? $order->items->map(fn ($item): array => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all());
        $materialChanged = (int) ($data['supplier_id'] ?? $order->supplier_id) !== $order->supplier_id
            || $this->itemsDiffer($order->items->map(fn ($item): array => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price]), $items)
            || (float) ($data['discount'] ?? $order->discount) !== (float) $order->discount
            || (float) ($data['tax'] ?? $order->tax) !== (float) $order->tax
            || (float) ($data['shipping_cost'] ?? $order->shipping_cost) !== (float) $order->shipping_cost;

        if (! $materialChanged) {
            $before = $order->toArray();
            $order->update(['expected_arrival' => $data['expected_arrival'] ?? $order->expected_arrival, 'notes' => $data['notes'] ?? $order->notes]);

            return $order->fresh();
        }

        return DB::transaction(function () use ($order, $data, $changedBy, $items): PurchaseOrderRevision {
            $snapshot = [
                'supplier_id' => $data['supplier_id'] ?? $order->supplier_id,
                'expected_arrival' => $data['expected_arrival'] ?? $order->expected_arrival?->toDateString(),
                'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                'notes' => $data['notes'] ?? $order->notes,
                'discount' => $data['discount'] ?? $order->discount ?? 0,
                'tax' => $data['tax'] ?? $order->tax ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? $order->shipping_cost ?? 0,
                'total' => $this->calculateTotal($items, $data),
            ];
            $revision = $order->revisions()->create(['changed_by' => $changedBy, 'version' => ((int) $order->revisions()->max('version')) + 1, 'status' => 'submitted', 'reason' => $data['reason'], 'snapshot' => $snapshot]);
            $revision->items()->createMany($items->all());

            return $revision->load('items');
        });
    }

    public function decidePurchaseOrderRevision(PurchaseOrderRevision $revision, int $approverId, string $decision, ?string $notes = null): PurchaseOrderRevision
    {
        if ($revision->status !== 'submitted' || ! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Revisi PO tidak sedang menunggu persetujuan.']);
        }
        if ($revision->changed_by === $approverId) {
            throw ValidationException::withMessages(['approver' => 'Pembuat revisi tidak boleh menyetujui revisi sendiri.']);
        }

        return DB::transaction(function () use ($revision, $approverId, $decision, $notes): PurchaseOrderRevision {
            if ($decision === 'approved') {
                $revision->load('items');
                $snapshot = $revision->snapshot;
                $revision->purchaseOrder->update($snapshot);
                $revision->purchaseOrder->items()->delete();
                $revision->purchaseOrder->items()->createMany($revision->items->map(fn ($item): array => ['product_id' => $item->product_id, 'quantity' => $item->quantity, 'unit_price' => $item->unit_price])->all());
            }
            $revision->update(['status' => $decision, 'decided_by' => $approverId, 'decided_at' => now(), 'decision_notes' => $notes]);

            return $revision->fresh();
        });
    }

    public function verifyPayment(Payment $payment, int $verifierId, string $decision, ?string $notes = null): Payment
    {
        if ($payment->pic_id === $verifierId) {
            throw ValidationException::withMessages(['verifier' => 'PIC pembayaran tidak boleh memverifikasi sendiri.']);
        }
        if ($payment->status !== PaymentTransactionStatus::PendingVerification || ! in_array($decision, ['verified', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Pembayaran tidak sedang menunggu verifikasi.']);
        }

        return DB::transaction(function () use ($payment, $verifierId, $decision, $notes): Payment {
            $payment->update(['status' => $decision, 'verified_by' => $verifierId, 'verified_at' => now(), 'verification_notes' => $notes]);
            $order = $payment->purchaseOrder()->with('payments')->first();
            $verified = (float) $order->payments->where('status', PaymentTransactionStatus::Verified)->sum('amount');
            $order->update(['payment_status' => $verified === (float) $order->total ? PaymentStatus::Paid : ($verified > 0 ? PaymentStatus::Partial : PaymentStatus::Unpaid)]);

            return $payment->fresh();
        });
    }

    public function cancelPurchaseRequest(PurchaseRequest $request, int $actorId, string $reason): PurchaseRequest
    {
        if ($request->status === DocumentStatus::Draft && $request->requester_id !== $actorId) {
            throw ValidationException::withMessages(['request' => 'Hanya pembuat PR yang dapat membatalkan draft.']);
        }
        if (! in_array($request->status, [DocumentStatus::Draft, DocumentStatus::Submitted, DocumentStatus::Approved], true)) {
            throw ValidationException::withMessages(['status' => 'PR tidak dapat dibatalkan dari status saat ini.']);
        }
        if ($request->status !== DocumentStatus::Draft && $request->requester_id === $actorId) {
            throw ValidationException::withMessages(['approver' => 'Pembatalan PR yang sudah diajukan memerlukan Owner/Direksi.']);
        }

        return DB::transaction(function () use ($request, $actorId, $reason): PurchaseRequest {
            $this->recordStatus($request, DocumentStatus::Cancelled, $actorId, $reason);
            $request->update(['status' => DocumentStatus::Cancelled, 'cancellation_reason' => $reason, 'cancelled_by' => $actorId, 'cancelled_at' => now()]);

            return $request->fresh();
        });
    }

    public function cancelPurchaseOrder(PurchaseOrder $order, int $actorId, string $reason, bool $financeConfirmed = false): PurchaseOrder
    {
        if (! in_array($order->status, [DocumentStatus::Approved, DocumentStatus::Completed, DocumentStatus::ClosedShort], true)) {
            throw ValidationException::withMessages(['status' => 'PO tidak dapat dibatalkan dari status saat ini.']);
        }
        if ($order->payments()->whereIn('status', [PaymentTransactionStatus::PendingVerification, PaymentTransactionStatus::Verified])->exists() && ! $financeConfirmed) {
            throw ValidationException::withMessages(['finance_confirmed' => 'Pembatalan PO dengan pembayaran memerlukan konfirmasi Finance.']);
        }

        return DB::transaction(function () use ($order, $actorId, $reason): PurchaseOrder {
            $this->recordStatus($order, DocumentStatus::Cancelled, $actorId, $reason);
            $order->update(['status' => DocumentStatus::Cancelled, 'cancellation_reason' => $reason, 'cancelled_by' => $actorId, 'cancelled_at' => now()]);

            return $order->fresh();
        });
    }

    public function requestCancellation(PurchaseOrder|PurchaseRequest $document, int $requesterId, string $reason): DocumentWorkflowRequest
    {
        if (! in_array($document->status, [DocumentStatus::Submitted, DocumentStatus::Approved, DocumentStatus::Completed, DocumentStatus::ClosedShort], true)) {
            throw ValidationException::withMessages(['status' => 'Dokumen tidak memerlukan pengajuan pembatalan.']);
        }
        if ($document instanceof PurchaseOrder && $document->status === DocumentStatus::Cancelled) {
            throw ValidationException::withMessages(['status' => 'PO sudah dibatalkan.']);
        }

        return DocumentWorkflowRequest::create([
            'document_type' => $document::class,
            'document_id' => $document->id,
            'action' => 'cancel',
            'requested_by' => $requesterId,
            'reason' => $reason,
        ]);
    }

    public function requestCloseShort(PurchaseOrder $order, int $requesterId, string $reason): DocumentWorkflowRequest
    {
        if ($order->status !== DocumentStatus::Approved) {
            throw ValidationException::withMessages(['status' => 'PO harus aktif sebelum dapat diajukan TUTUP KURANG.']);
        }
        if ($order->goodsReceipts()->doesntExist()) {
            throw ValidationException::withMessages(['goods_receipts' => 'TUTUP KURANG memerlukan penerimaan barang terlebih dahulu.']);
        }

        return DocumentWorkflowRequest::create(['document_type' => PurchaseOrder::class, 'document_id' => $order->id, 'action' => 'close_short', 'requested_by' => $requesterId, 'reason' => $reason]);
    }

    public function decideWorkflowRequest(DocumentWorkflowRequest $workflow, int $deciderId, string $decision, ?string $notes = null, bool $financeConfirmed = false): DocumentWorkflowRequest
    {
        if ($workflow->status !== 'submitted' || ! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['status' => 'Pengajuan workflow sudah diputuskan atau tidak valid.']);
        }
        if ($workflow->requested_by === $deciderId) {
            throw ValidationException::withMessages(['decider' => 'Pembuat pengajuan tidak boleh memutuskan pengajuannya sendiri.']);
        }

        return DB::transaction(function () use ($workflow, $deciderId, $decision, $notes, $financeConfirmed): DocumentWorkflowRequest {
            if ($decision === 'approved') {
                $document = $workflow->document_type::query()->findOrFail($workflow->document_id);
                if ($workflow->action === 'cancel') {
                    if ($document instanceof PurchaseOrder) {
                        $this->cancelPurchaseOrder($document, $deciderId, $workflow->reason, $financeConfirmed);
                    } else {
                        $this->cancelPurchaseRequest($document, $deciderId, $workflow->reason);
                    }
                } elseif ($workflow->action === 'close_short' && $document instanceof PurchaseOrder) {
                    $this->recordStatus($document, DocumentStatus::ClosedShort, $deciderId, $workflow->reason);
                    $document->update(['status' => DocumentStatus::ClosedShort, 'close_short_reason' => $workflow->reason, 'close_short_requested_by' => $workflow->requested_by, 'close_short_requested_at' => $workflow->created_at, 'close_short_approved_by' => $deciderId, 'close_short_approved_at' => now()]);
                }
            }
            $workflow->update(['status' => $decision, 'decided_by' => $deciderId, 'decision_notes' => $notes, 'decided_at' => now()]);

            return $workflow->fresh();
        });
    }

    private function recordStatus(PurchaseRequest|PurchaseOrder $document, DocumentStatus $toStatus, int $userId, ?string $reason = null): void
    {
        DocumentStatusHistory::create(['document_type' => $document::class, 'document_id' => $document->id, 'from_status' => $document->status?->value, 'to_status' => $toStatus->value, 'changed_by' => $userId, 'reason' => $reason, 'changed_at' => now()]);
    }

    private function postStock(int $productId, int $warehouseId, float $change, string $referenceType, int $referenceId, int $userId): void
    {
        $balance = (float) StockLedger::where('product_id', $productId)->where('warehouse_id', $warehouseId)->lockForUpdate()->latest('id')->value('balance_after');
        $after = $balance + $change;
        if ($after < 0) {
            throw ValidationException::withMessages(['items' => 'Stok tidak boleh negatif.']);
        }
        StockLedger::create(['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity_change' => $change, 'balance_after' => $after, 'reference_type' => $referenceType, 'reference_id' => $referenceId, 'posted_by' => $userId, 'posted_at' => now()]);
    }

    private function itemsDiffer($current, $revised): bool
    {
        return $current->values()->map(fn (array $item): array => array_map('strval', $item))->all() !== $revised->values()->map(fn (array $item): array => array_map('strval', $item))->all();
    }

    private function calculateTotal($items, array $data): float
    {
        return (float) $items->sum(fn (array $item): float => (float) $item['quantity'] * (float) $item['unit_price'])
            - (float) ($data['discount'] ?? 0)
            + (float) ($data['tax'] ?? 0)
            + (float) ($data['shipping_cost'] ?? 0);
    }
}
