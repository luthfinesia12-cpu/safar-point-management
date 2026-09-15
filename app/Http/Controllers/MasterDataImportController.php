<?php

namespace App\Http\Controllers;

use App\Models\MasterImportBatch;
use App\Services\MasterDataImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MasterDataImportController extends Controller
{
    public function template(string $type, MasterDataImportService $service): StreamedResponse
    {
        $headers = $service->template($type);

        return response()->streamDownload(function () use ($headers): void {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, $headers);
            fclose($handle);
        }, $type.'-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function preview(string $type, Request $request, MasterDataImportService $service): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $batch = $service->preview($type, $request->file('file'), $request->user()->id);

        return response()->json(['batch_id' => $batch->id, 'status' => $batch->status, 'row_count' => $batch->row_count, 'errors' => $batch->errors ?? [], 'valid_rows' => count($batch->valid_rows ?? [])]);
    }

    public function commit(MasterImportBatch $batch, MasterDataImportService $service): JsonResponse
    {
        abort_unless($batch->uploaded_by === auth()->id(), 403);

        return response()->json(['imported' => $service->commit($batch)]);
    }
}
