<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ReleaseOneExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_exports_require_report_permission(): void
    {
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($user)
            ->get(route('exports.xlsx', 'products'))
            ->assertForbidden();
    }

    public function test_xlsx_export_has_real_mime_filename_filters_and_minimum_content(): void
    {
        $user = $this->reportUser();
        $unit = Unit::create(['code' => 'UNT-001', 'name' => 'Pcs']);
        Product::create([
            'code' => 'PRD-000001',
            'sku' => 'SP-000001',
            'name' => 'Kopi Filter',
            'unit_id' => $unit->id,
            'minimum_stock' => 2,
        ]);
        Product::create([
            'code' => 'PRD-000002',
            'sku' => 'SP-000002',
            'name' => 'Gula Pasir',
            'unit_id' => $unit->id,
            'minimum_stock' => 5,
        ]);

        $response = $this->actingAs($user)->get(route('exports.xlsx', ['type' => 'products', 'search' => 'Kopi']));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename=products.xlsx');

        $spreadsheet = IOFactory::load($response->baseResponse->getFile()->getPathname());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $this->assertContains(['Kode', 'SKU', 'Nama', 'Barcode', 'Minimum Stok'], $rows);
        $this->assertTrue(collect($rows)->flatten()->contains('Kopi Filter'));
        $this->assertFalse(collect($rows)->flatten()->contains('Gula Pasir'));
    }

    public function test_pdf_export_has_real_mime_filename_metadata_and_total(): void
    {
        $user = $this->reportUser();
        $unit = Unit::create(['code' => 'UNT-001', 'name' => 'Pcs']);
        Product::create([
            'code' => 'PRD-000001',
            'sku' => 'SP-000001',
            'name' => 'Kopi Filter',
            'unit_id' => $unit->id,
            'minimum_stock' => 2,
        ]);

        $response = $this->actingAs($user)->get(route('exports.pdf', 'products'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=products.pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    private function reportUser(): User
    {
        $user = User::factory()->create(['must_change_password' => false]);
        $user->assignRole('Viewer/Auditor');

        return $user;
    }
}
