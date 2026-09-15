<?php

namespace Tests\Feature;

use App\Enums\DocumentStatus;
use App\Enums\PaymentTransactionStatus;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProcurementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReleaseOneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $purchaser;

    private User $owner;

    private Product $product;

    private Supplier $supplier;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->purchaser = User::factory()->create(['must_change_password' => false]);
        $this->owner = User::factory()->create(['must_change_password' => false]);
        $this->assignPermissions($this->purchaser, ['purchase-requests.create', 'purchase-requests.submit', 'purchase-orders.create', 'goods-receipts.create', 'payments.create']);
        $this->assignPermissions($this->owner, ['purchase-requests.approve', 'payments.verify']);
        $this->product = Product::create(['code' => 'PRD-000001', 'sku' => 'SP-000001', 'name' => 'Kopi', 'unit_id' => Unit::create(['code' => 'UNT-001', 'name' => 'Pcs'])->id]);
        $this->supplier = Supplier::create(['code' => 'SUP-00001', 'name' => 'Supplier Utama']);
        $this->warehouse = Warehouse::create(['code' => 'WH-001', 'name' => 'Gudang Utama']);
    }

    public function test_purchase_request_requires_owner_approval_before_po_and_numbers_are_monthly(): void
    {
        $service = app(ProcurementService::class);
        $request = $service->createPurchaseRequest(['reason' => 'Stok awal', 'priority' => 'normal', 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'estimated_price' => 10]]], $this->purchaser->id);
        $service->submitPurchaseRequest($request);
        $this->expectException(ValidationException::class);
        $service->createPurchaseOrder(['purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10]]], $this->purchaser->id);
    }

    public function test_full_flow_posts_stock_and_requires_distinct_payment_verifier(): void
    {
        $service = app(ProcurementService::class);
        $request = $service->createPurchaseRequest(['reason' => 'Stok awal', 'priority' => 'normal', 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'estimated_price' => 10]]], $this->purchaser->id);
        $service->submitPurchaseRequest($request);
        $service->decidePurchaseRequest($request->fresh(), $this->owner->id, 'approved');
        $order = $service->createPurchaseOrder(['purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'items' => [['product_id' => $this->product->id, 'quantity' => 2, 'unit_price' => 10]]], $this->purchaser->id);
        $receipt = $service->postGoodsReceipt(['purchase_order_id' => $order->id, 'warehouse_id' => $this->warehouse->id, 'items' => [['product_id' => $this->product->id, 'quantity' => 2]]], $this->purchaser->id);
        $this->assertSame('GR/SP/'.now()->format('Y/m').'/00001', $receipt->document_number);
        $this->assertDatabaseHas('stock_ledgers', ['reference_id' => $receipt->id, 'balance_after' => 2]);
        $payment = $service->createPayment(['purchase_order_id' => $order->id, 'amount' => 20, 'payment_date' => now()->toDateString(), 'payment_type' => 'settlement', 'method' => 'cash', 'recipient' => 'Supplier'], $this->purchaser->id);
        $this->expectException(ValidationException::class);
        $service->verifyPayment($payment, $this->purchaser->id, 'verified');
        $verified = $service->verifyPayment($payment, $this->owner->id, 'verified');
        $this->assertSame(PaymentTransactionStatus::Verified, $verified->status);
        $this->assertSame('paid', $order->fresh()->payment_status->value);
    }

    public function test_partial_receipts_cannot_exceed_po_and_transfer_requires_proof(): void
    {
        $service = app(ProcurementService::class);
        $request = PurchaseRequest::create(['requester_id' => $this->purchaser->id, 'reason' => 'Stok', 'status' => DocumentStatus::Approved]);
        $order = PurchaseOrder::create(['document_number' => 'PO/SP/'.now()->format('Y/m').'/00001', 'purchase_request_id' => $request->id, 'supplier_id' => $this->supplier->id, 'purchaser_id' => $this->purchaser->id, 'order_date' => now(), 'total' => 10, 'status' => DocumentStatus::Approved]);
        $order->items()->create(['product_id' => $this->product->id, 'quantity' => 1, 'unit_price' => 10]);
        $service->postGoodsReceipt(['purchase_order_id' => $order->id, 'warehouse_id' => $this->warehouse->id, 'items' => [['product_id' => $this->product->id, 'quantity' => 1]]], $this->purchaser->id);
        $this->expectException(ValidationException::class);
        $service->postGoodsReceipt(['purchase_order_id' => $order->id, 'warehouse_id' => $this->warehouse->id, 'items' => [['product_id' => $this->product->id, 'quantity' => 1]]], $this->purchaser->id);
    }

    private function assignPermissions(User $user, array $permissions): void
    {
        $role = Role::create(['name' => 'test-'.$user->id, 'guard_name' => 'web']);
        $role->syncPermissions(Permission::whereIn('name', $permissions)->get());
        $user->assignRole($role);
    }
}
