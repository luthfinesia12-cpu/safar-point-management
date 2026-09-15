<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * CSV is the machine-readable export in Release 1. The PDF endpoint deliberately
 * returns printable HTML because no PDF/XLSX package is installed or added here.
 */
class ExportController extends Controller
{
    public function csv(string $type, Request $request): Response
    {
        [$title, $headers, $rows] = $this->data($type, $request);
        $content = implode(',', $headers)."\n";
        foreach ($rows as $row) {
            $content .= implode(',', array_map(fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"', $row))."\n";
        }

        return response($content, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="'.str_replace(' ', '-', strtolower($title)).'.csv"']);
    }

    public function pdf(string $type, Request $request): View
    {
        [$title, $headers, $rows] = $this->data($type, $request);

        return view('exports.table', ['title' => $title, 'headers' => $headers, 'rows' => $rows, 'printedAt' => now()]);
    }

    private function data(string $type, Request $request): array
    {
        return match ($type) {
            'purchase-orders' => ['Purchase Orders', ['Nomor', 'Supplier', 'Total', 'Status'], PurchaseOrder::with('supplier')->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))->latest()->get()->map(fn ($order): array => [$order->document_number, $order->supplier->name, $order->total, $order->status->value])->all()],
            'payments' => ['Payments', ['PO', 'Tanggal', 'Nominal', 'Status'], Payment::with('purchaseOrder')->latest()->get()->map(fn ($payment): array => [$payment->purchaseOrder->document_number, $payment->payment_date->toDateString(), $payment->amount, $payment->status->value])->all()],
            'products' => ['Products', ['Kode', 'SKU', 'Nama', 'Barcode', 'Minimum Stok'], Product::query()->latest()->get()->map(fn ($product): array => [$product->code, $product->sku, $product->name, $product->barcode, $product->minimum_stock])->all()],
            default => abort(404),
        };
    }
}
