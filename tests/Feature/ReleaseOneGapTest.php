<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\GoodsReceipt;
use App\Models\NotificationDelivery;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\WorkflowNotification;
use App\Services\MasterDataImportService;
use App\Services\NotificationDeliveryService;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseOneGapTest extends TestCase
{
    use RefreshDatabase;

    private User $purchaser;

    private User $owner;

    private Product $product;

    private PurchaseOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->purchaser = User::factory()->create(['must_change_password' => false]);
        $this->owner = User::factory()->create(['must_change_password' => false]);
        $this->purchaser->assignRole('Purchasing');
        $this->owner->assignRole('Owner/Direksi');
        $unit = Unit::create(['code' => 'UNT-001', 'name' => 'Pcs']);
        $this->product = Product::create(['code' => 'PRD-000001', 'sku' => 'SP-000001', 'name' => 'Kopi', 'unit_id' => $unit->id]);
        $supplier = Supplier::create(['code' => 'SUP-00001', 'name' => 'Supplier']);
        $request = PurchaseRequest::create(['requester_id' => $this->purchaser->id, 'reason' => 'Stok', 'status' => DocumentStatus::Approved]);
        $this->order = PurchaseOrder::create(['document_number' => 'PO/SP/2026/09/00001', 'purchase_request_id' => $request->id, 'supplier_id' => $supplier->id, 'purchaser_id' => $this->purchaser->id, 'order_date' => today(), 'total' => 10, 'status' => DocumentStatus::Approved]);
        $this->order->items()->create(['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 5]);
    }

    public function test_po_cancellation_requires_finance_confirmation_and_keeps_status_history(): void
    {
        $service = app(ProcurementService::class);
        $payment = $service->createPayment(['purchase_order_id' => $this->order->id, 'amount' => 5, 'payment_date' => today(), 'method' => 'cash', 'recipient' => 'Supplier'], $this->purchaser->id);
        $service->verifyPayment($payment, $this->owner->id, 'verified');
        $workflow = $service->requestCancellation($this->order, $this->purchaser->id, 'Supplier tidak tersedia');

        $this->expectException(ValidationException::class);
        $service->decideWorkflowRequest($workflow, $this->owner->id, 'approved');
        $this->assertSame(DocumentStatus::Approved, $this->order->fresh()->status);

        $service->decideWorkflowRequest($workflow->fresh(), $this->owner->id, 'approved', null, true);
        $this->assertSame(DocumentStatus::Cancelled, $this->order->fresh()->status);
        $this->assertDatabaseHas('document_status_histories', ['document_type' => PurchaseOrder::class, 'document_id' => $this->order->id, 'to_status' => 'cancelled']);
    }

    public function test_tutup_kurang_requires_a_receipt_and_owner_approval(): void
    {
        $service = app(ProcurementService::class);
        $this->expectException(ValidationException::class);
        $service->requestCloseShort($this->order, $this->purchaser->id, 'Supplier mengurangi jumlah');

        GoodsReceipt::create(['document_number' => 'GR/SP/2026/09/00001', 'purchase_order_id' => $this->order->id, 'warehouse_id' => $this->makeWarehouseId(), 'receiver_id' => $this->purchaser->id, 'received_date' => today(), 'status' => DocumentStatus::Approved, 'posted_at' => now(), 'posted_by' => $this->purchaser->id]);
        $workflow = $service->requestCloseShort($this->order->fresh(), $this->purchaser->id, 'Supplier mengurangi jumlah');
        $service->decideWorkflowRequest($workflow, $this->owner->id, 'approved');

        $this->assertSame(DocumentStatus::ClosedShort, $this->order->fresh()->status);
        $this->assertDatabaseHas('document_status_histories', ['to_status' => 'closed_short', 'reason' => 'Supplier mengurangi jumlah']);
    }

    public function test_csv_preview_returns_errors_and_refuses_commit_until_valid(): void
    {
        $file = UploadedFile::fake()->createWithContent('categories.csv', "name,is_active\nValid,1\n,1\n");
        $batch = app(MasterDataImportService::class)->preview('categories', $file, $this->purchaser->id);

        $this->assertCount(1, $batch->errors);
        $this->expectException(ValidationException::class);
        app(MasterDataImportService::class)->commit($batch);
    }

    public function test_notification_delivery_can_be_resent_with_lineage(): void
    {
        NotificationFacade::fake();
        $service = app(NotificationDeliveryService::class);
        $service->send([$this->owner->id], 'Tes', 'Pesan');
        $delivery = NotificationDelivery::firstOrFail();
        $delivery->update(['status' => 'failed', 'failure_message' => 'SMTP gagal']);

        $resent = $service->resend($delivery);

        $this->assertSame('sent', $resent->status);
        $this->assertSame($delivery->id, $resent->resent_from_id);
        NotificationFacade::assertSentTo($this->owner, WorkflowNotification::class);
    }

    public function test_attachment_replacement_is_versioned_and_requires_replace_permission(): void
    {
        Storage::fake();
        $uploader = User::factory()->create(['must_change_password' => false]);
        $role = Role::create(['name' => 'attachment-uploader', 'guard_name' => 'web']);
        $uploader->assignRole($role);
        $role->syncPermissions(Permission::whereIn('name', ['attachments.create'])->get());
        $this->actingAs($uploader)->post(route('attachments.store'), ['document_type' => 'purchase_order', 'document_id' => $this->order->id, 'file' => UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->actingAs($uploader)->post(route('attachments.store'), ['document_type' => 'purchase_order', 'document_id' => $this->order->id, 'file' => UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')])->assertForbidden();

        $role->syncPermissions(Permission::whereIn('name', ['attachments.create', 'attachments.replace'])->get());
        $this->actingAs($uploader)->post(route('attachments.store'), ['document_type' => 'purchase_order', 'document_id' => $this->order->id, 'file' => UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('document_attachments', ['document_id' => $this->order->id, 'version' => 2, 'original_name' => 'second.pdf']);
    }

    private function makeWarehouseId(): int
    {
        return Warehouse::create(['code' => 'WH-001', 'name' => 'Gudang'])->id;
    }
}
