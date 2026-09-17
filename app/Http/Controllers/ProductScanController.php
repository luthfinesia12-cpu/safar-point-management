<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductScanController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:255'],
        ]);

        $code = trim($data['code']);
        $product = Product::query()
            ->with(['category:id,name', 'brand:id,name', 'unit:id,name'])
            ->where('is_active', true)
            ->where(function ($query) use ($code): void {
                $query->where('sku', $code)->orWhere('barcode', $code);
            })
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'SKU atau barcode tidak ditemukan pada produk aktif.',
            ], 404);
        }

        return response()->json([
            'product' => [
                'id' => $product->id,
                'code' => $product->code,
                'sku' => $product->sku,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category' => $product->category?->name,
                'brand' => $product->brand?->name,
                'unit' => $product->unit?->name,
            ],
        ]);
    }
}
