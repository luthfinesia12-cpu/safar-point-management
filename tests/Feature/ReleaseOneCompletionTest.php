<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\WorkflowNotification;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseOneCompletionTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $owner;

    private Product $product;

    private Supplier $supplier;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->operator = User::factory()->create(['must_change_password' => false]);
        $this->owner = User::factory()->create(['must_change_password' => false]);
        $this->assignPermissions($this->operator, ['adjustments.create', 'master-data.manage', 'reports.view']);
        $this->owner->assignRole('Owner/Direksi');
        $this->product = Product::create(['code' => 'PRD-000001', 'sku' => 'SP-000001', 'name' => 'Kopi', 'unit_id' => Unit::create(['code' => 'UNT-001', 'name' => 'Pcs'])->id, 'minimum_stock' => 1]);
        $this->supplier = Supplier::create(['code' => 'SUP-00001', 'name' => 'Supplier Utama']);
        $this->warehouse = Warehouse::create(['code' => 'WH-001', 'name' => 'Gudang Utama']);
    }

    public function test_adjustment_requires_owner_approval_and_rejects_negative_stock(): void
    {
        $service = app(ProcurementService::class);
        $increase = $service->createAdjustment(['warehouse_id' => $this->warehouse->id, 'adjustment_type' => 'increase', 'reason' => 'Stok awal', 'items' => [['product_id' => $this->product->id, 'quantity_change' => 3]]], $this->operator->id);
        $service->submitAdjustment($increase);
        $approved = $service->decideAdjustment($increase->fresh(), $this->owner->id, 'approved');

        $this->assertSame(DocumentStatus::Approved, $approved->status);
        $this->assertDatabaseHas('stock_ledgers', ['reference_type' => 'adjustment', 'reference_id' => $approved->id, 'balance_after' => 3]);

        $decrease = $service->createAdjustment(['warehouse_id' => $this->warehouse->id, 'adjustment_type' => 'decrease', 'reason' => 'Koreksi', 'items' => [['product_id' => $this->product->id, 'quantity_change' => -4]]], $this->operator->id);
        $service->submitAdjustment($decrease);
        $this->expectException(ValidationException::class);
        $service->decideAdjustment($decrease->fresh(), $this->owner->id, 'approved');
        $this->assertDatabaseMissing('stock_ledgers', ['reference_id' => $decrease->id]);
    }

    public function test_master_data_requires_permission_generates_immutable_code_and_rejects_duplicate_barcode(): void
    {
        $guest = User::factory()->create(['must_change_password' => false]);
        $this->actingAs($guest)->get(route('master-data.index', 'products'))->assertForbidden();
        $this->actingAs($this->operator)->post(route('master-data.store', 'products'), ['name' => 'Gula', 'unit_id' => $this->product->unit_id, 'barcode' => '123456', 'minimum_stock' => 4, 'is_active' => 1])->assertSessionHasNoErrors();
        $created = Product::where('barcode', '123456')->firstOrFail();
        $this->assertSame('PRD-000002', $created->code);
        $this->actingAs($this->operator)->put(route('master-data.update', ['type' => 'products', 'id' => $created->id]), ['name' => 'Gula Baru'])->assertSessionHasNoErrors();
        $this->assertSame('PRD-000002', $created->fresh()->code);
        $this->actingAs($this->operator)->post(route('master-data.store', 'products'), ['name' => 'Gula Duplikat', 'unit_id' => $this->product->unit_id, 'barcode' => '123456', 'minimum_stock' => 0])->assertSessionHasErrors('barcode');
    }

    public function test_material_po_revision_waits_for_owner_and_updates_history(): void
    {
        $request = PurchaseRequest::create(['requester_id' => $this->operator->id, 'reason' => 'Belanja', 'status' => DocumentStatus::Approved]);
        $order = PurchaseOrder::create(['document_number' => 'PO/SP/2026/09/00001', 'purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'purchaser_id' => $this->operator->id, 'order_date' => now(), 'total' => 10, 'status' => DocumentStatus::Approved]);
        $order->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10]);
        $revision = app(ProcurementService::class)->revisePurchaseOrder($order, ['reason' => 'Jumlah berubah', 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10]]], $this->operator->id);

        $this->assertDatabaseHas('purchase_order_revisions', ['purchase_order_id' => $order->id, 'version' => 1, 'status' => 'submitted']);
        $this->assertSame('1.000', (string) $order->fresh()->items->first()->quantity);
        app(ProcurementService::class)->decidePurchaseOrderRevision($revision, $this->owner->id, 'approved');
        $this->assertSame('2.000', (string) $order->fresh()->items->first()->quantity);
        $this->assertDatabaseHas('purchase_order_revisions', ['id' => $revision->id, 'status' => 'approved']);
    }

    public function test_notifications_command_and_csv_export_are_available_with_permission(): void
    {
        Notification::fake();
        $request = PurchaseRequest::create(['requester_id' => $this->operator->id, 'reason' => 'Belanja', 'status' => DocumentStatus::Approved]);
        $order = PurchaseOrder::create(['document_number' => 'PO/SP/2026/09/00001', 'purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'purchaser_id' => $this->operator->id, 'order_date' => now(), 'total' => 10, 'status' => DocumentStatus::Approved]);
        Payment::create(['purchase_order_id' => $order->id, 'pic_id' => $this->operator->id, 'amount' => 5, 'payment_date' => today(), 'method' => 'cash', 'recipient' => 'Supplier', 'status' => 'pending_verification']);
        $this->artisan('workflow:notify')->assertExitCode(0);
        Notification::assertSentTo($this->owner, WorkflowNotification::class);
        $this->actingAs($this->operator)->get(route('exports.csv', 'products'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->assertSee('PRD-000001');
    }

    private function assignPermissions(User $user, array $permissions): void
    {
        $role = Role::create(['name' => 'completion-'.$user->id, 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());
        $user->assignRole($role);
    }
}
