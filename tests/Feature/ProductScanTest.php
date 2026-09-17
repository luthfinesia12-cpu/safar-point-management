<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductScanTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_find_active_product_by_sku_or_barcode(): void
    {
        $this->seed();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $unit = Unit::create(['code' => 'UNT-001', 'name' => 'Pcs']);
        $product = Product::create([
            'code' => 'PRD-000001',
            'sku' => 'SP-000001',
            'barcode' => '8991234567890',
            'name' => 'Tisu Safar Point',
            'unit_id' => $unit->id,
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->getJson(route('products.scan-lookup', ['code' => $product->sku]))
            ->assertOk()
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.name', 'Tisu Safar Point');

        $this->actingAs($user)
            ->getJson(route('products.scan-lookup', ['code' => $product->barcode]))
            ->assertOk()
            ->assertJsonPath('product.sku', 'SP-000001');
    }

    public function test_scan_does_not_return_inactive_or_unknown_product(): void
    {
        $this->seed();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $unit = Unit::create(['code' => 'UNT-001', 'name' => 'Pcs']);
        Product::create([
            'code' => 'PRD-000001',
            'sku' => 'SP-000001',
            'barcode' => '8990000000001',
            'name' => 'Produk Nonaktif',
            'unit_id' => $unit->id,
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->getJson(route('products.scan-lookup', ['code' => '8990000000001']))
            ->assertNotFound()
            ->assertJsonPath('message', 'SKU atau barcode tidak ditemukan pada produk aktif.');
    }
}
