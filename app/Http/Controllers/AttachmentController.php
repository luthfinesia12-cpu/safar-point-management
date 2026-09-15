<?php

namespace App\Http\Controllers;

use App\Models\Adjustment;
use App\Models\DocumentAttachment;
use App\Models\GoodsReceipt;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AttachmentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['document_type' => ['required', Rule::in(array_keys($this->documents()))], 'document_id' => ['required', 'integer'], 'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120']]);
        $documentClass = $this->documents()[$data['document_type']];
        abort_unless($documentClass::whereKey($data['document_id'])->exists(), 404);
        $previous = DocumentAttachment::where('document_type', $data['document_type'])->where('document_id', $data['document_id'])->latest('version')->first();
        abort_if(DocumentAttachment::where('document_type', $data['document_type'])->where('document_id', $data['document_id'])->count() >= 10, 422, 'Maksimal 10 lampiran per dokumen.');
        if ($previous) {
            abort_unless($request->user()->can('attachments.replace'), 403);
        }
        $file = $data['file'];
        DocumentAttachment::create(['document_type' => $data['document_type'], 'document_id' => $data['document_id'], 'uploaded_by' => $request->user()->id, 'original_name' => $file->getClientOriginalName(), 'storage_path' => $file->store('attachments'), 'mime_type' => $file->getMimeType(), 'size' => $file->getSize(), 'version' => ($previous?->version ?? 0) + 1, 'replaced_attachment_id' => $previous?->id]);

        return back()->with('status', 'Lampiran berhasil disimpan sebagai versi baru.');
    }

    private function documents(): array
    {
        return ['purchase_request' => PurchaseRequest::class, 'purchase_order' => PurchaseOrder::class, 'goods_receipt' => GoodsReceipt::class, 'payment' => Payment::class, 'adjustment' => Adjustment::class];
    }
}
