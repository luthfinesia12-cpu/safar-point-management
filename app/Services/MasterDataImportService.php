<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Brand;
use App\Models\Category;
use App\Models\MasterImportBatch;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MasterDataImportService
{
    private const MODELS = [
        'products' => Product::class,
        'categories' => Category::class,
        'brands' => Brand::class,
        'units' => Unit::class,
        'suppliers' => Supplier::class,
        'warehouses' => Warehouse::class,
        'bank-accounts' => BankAccount::class,
    ];

    public function template(string $type): array
    {
        $this->model($type);

        return match ($type) {
            'products' => ['name', 'unit_id', 'barcode', 'minimum_stock', 'specification', 'category_id', 'brand_id', 'is_active'],
            'suppliers' => ['name', 'contact', 'address', 'bank_account', 'is_active'],
            'warehouses' => ['name', 'address', 'pic', 'is_active'],
            'bank-accounts' => ['bank_name', 'account_number', 'account_holder', 'is_active'],
            default => ['name', 'is_active'],
        };
    }

    public function preview(string $type, UploadedFile $file, int $userId): MasterImportBatch
    {
        $headers = $this->template($type);
        $handle = fopen($file->getRealPath(), 'rb');
        $actualHeaders = fgetcsv($handle) ?: [];
        $errors = [];
        $validRows = [];
        $rowNumber = 1;

        if ($actualHeaders !== $headers) {
            $errors[] = ['row' => 1, 'messages' => ['Header CSV harus: '.implode(',', $headers)]];
        } else {
            while (($values = fgetcsv($handle)) !== false) {
                $rowNumber++;
                if ($values === [null] || count(array_filter($values, fn ($value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }
                if (count($values) !== count($headers)) {
                    $errors[] = ['row' => $rowNumber, 'messages' => ['Jumlah kolom CSV tidak sesuai template.']];

                    continue;
                }
                $row = array_combine($headers, array_pad($values, count($headers), null));
                $validator = Validator::make($row, $this->rules($type));
                if ($validator->fails()) {
                    $errors[] = ['row' => $rowNumber, 'messages' => $validator->errors()->all()];
                } else {
                    $validRows[] = $row;
                }
            }
        }
        fclose($handle);

        return MasterImportBatch::create(['master_type' => $type, 'uploaded_by' => $userId, 'original_name' => $file->getClientOriginalName(), 'status' => 'preview', 'row_count' => max(0, $rowNumber - 1), 'valid_rows' => $validRows, 'errors' => $errors]);
    }

    public function commit(MasterImportBatch $batch): int
    {
        if ($batch->status !== 'preview' || count($batch->errors ?? []) > 0) {
            throw ValidationException::withMessages(['batch' => 'Import hanya dapat diproses setelah preview tanpa error.']);
        }
        $model = $this->model($batch->master_type);
        $created = 0;
        foreach ($batch->valid_rows ?? [] as $row) {
            $model::create(array_merge($this->generatedCode($batch->master_type), $row));
            $created++;
        }
        $batch->update(['status' => 'imported']);

        return $created;
    }

    private function model(string $type): string
    {
        if (! isset(self::MODELS[$type])) {
            throw ValidationException::withMessages(['type' => 'Tipe master data tidak didukung.']);
        }

        return self::MODELS[$type];
    }

    private function rules(string $type): array
    {
        return match ($type) {
            'products' => ['name' => ['required', 'string', 'max:255'], 'unit_id' => ['required', 'integer', 'exists:units,id'], 'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'], 'minimum_stock' => ['required', 'numeric', 'gte:0'], 'specification' => ['nullable', 'string'], 'category_id' => ['nullable', 'integer', 'exists:categories,id'], 'brand_id' => ['nullable', 'integer', 'exists:brands,id'], 'is_active' => ['nullable', 'boolean']],
            'bank-accounts' => ['bank_name' => ['required', 'string', 'max:255'], 'account_number' => ['required', 'string', 'max:100'], 'account_holder' => ['required', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']],
            'suppliers' => ['name' => ['required', 'string', 'max:255'], 'contact' => ['nullable', 'string'], 'address' => ['nullable', 'string'], 'bank_account' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']],
            'warehouses' => ['name' => ['required', 'string', 'max:255'], 'address' => ['nullable', 'string'], 'pic' => ['nullable', 'string'], 'is_active' => ['nullable', 'boolean']],
            default => ['name' => ['required', 'string', 'max:255'], 'is_active' => ['nullable', 'boolean']],
        };
    }

    private function generatedCode(string $type): array
    {
        if ($type === 'bank-accounts') {
            return [];
        }
        $model = $this->model($type);
        $next = ((int) $model::max('id')) + 1;
        $prefix = ['products' => 'PRD-', 'categories' => 'CAT-', 'brands' => 'BRD-', 'units' => 'UNT-', 'suppliers' => 'SUP-', 'warehouses' => 'WH-'][$type];
        $width = in_array($type, ['products', 'suppliers'], true) ? ($type === 'products' ? 6 : 5) : 3;

        return $type === 'products'
            ? ['code' => $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT), 'sku' => 'SP-'.str_pad((string) $next, 6, '0', STR_PAD_LEFT)]
            : ['code' => $prefix.str_pad((string) $next, $width, '0', STR_PAD_LEFT)];
    }
}
