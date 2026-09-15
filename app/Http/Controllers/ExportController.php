<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

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

    public function xlsx(string $type, Request $request): BinaryFileResponse
    {
        [$title, $headers, $rows, $total] = $this->data($type, $request);
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', CompanySetting::query()->value('name') ?? 'Safar Point Management');
        $sheet->setCellValue('A2', $title);
        $sheet->setCellValue('A3', 'Dicetak WIB');
        $sheet->setCellValue('B3', now()->timezone('Asia/Jakarta')->format('d/m/Y H:i:s'));
        $sheet->setCellValue('A4', 'Pembuat');
        $sheet->setCellValue('B4', $request->user()->name);
        $sheet->fromArray([$headers], null, 'A6');
        $sheet->fromArray($rows, null, 'A7');
        $sheet->setCellValue('A'.(7 + count($rows)), 'Total');
        $sheet->setCellValue('B'.(7 + count($rows)), $total);
        foreach (range('A', chr(64 + count($headers))) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $path = tempnam(storage_path('app'), 'safar_export_');
        (new Xlsx($spreadsheet))->save($path);

        return response()->download($path, str($title)->slug('_').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    public function pdf(string $type, Request $request): Response
    {
        [$title, $headers, $rows, $total] = $this->data($type, $request);
        $company = CompanySetting::first();

        return Pdf::loadView('exports.table', [
            'title' => $title,
            'headers' => $headers,
            'rows' => $rows,
            'total' => $total,
            'company' => $company,
            'logoDataUri' => $this->logoDataUri($company?->logo_path),
            'printedAt' => now()->timezone('Asia/Jakarta'),
            'printedBy' => $request->user()->name,
            'filters' => $request->except(['page']),
        ])->download(str($title)->slug('_').'.pdf');
    }

    private function data(string $type, Request $request): array
    {
        return match ($type) {
            'purchase-orders' => $this->purchaseOrders($request),
            'payments' => $this->payments($request),
            'products' => $this->products($request),
            default => abort(404),
        };
    }

    private function purchaseOrders(Request $request): array
    {
        $rows = PurchaseOrder::with('supplier')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('order_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('order_date', '<=', $request->date('to')))
            ->latest()->get();

        return ['Purchase Orders', ['Nomor', 'Supplier', 'Total', 'Status'], $rows->map(fn ($order): array => [$order->document_number, $order->supplier->name, $order->total, $order->status->value])->all(), $rows->sum('total')];
    }

    private function payments(Request $request): array
    {
        $rows = Payment::with('purchaseOrder')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('from'), fn ($query) => $query->whereDate('payment_date', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($query) => $query->whereDate('payment_date', '<=', $request->date('to')))
            ->latest()->get();

        return ['Payments', ['PO', 'Tanggal', 'Nominal', 'Status'], $rows->map(fn ($payment): array => [$payment->purchaseOrder->document_number, $payment->payment_date->toDateString(), $payment->amount, $payment->status->value])->all(), $rows->sum('amount')];
    }

    private function products(Request $request): array
    {
        $rows = Product::query()
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest()->get();

        return ['Products', ['Kode', 'SKU', 'Nama', 'Barcode', 'Minimum Stok'], $rows->map(fn ($product): array => [$product->code, $product->sku, $product->name, $product->barcode, $product->minimum_stock])->all(), $rows->sum('minimum_stock')];
    }

    private function logoDataUri(?string $logoPath): ?string
    {
        if (blank($logoPath)) {
            return null;
        }

        $path = storage_path('app/public/'.$logoPath);
        if (! is_file($path)) {
            return null;
        }

        $mimeType = mime_content_type($path) ?: 'image/png';

        return 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($path));
    }
}
