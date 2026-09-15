<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\StockLedger;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', ['settings' => CompanySetting::first(), 'recentAudits' => AuditLog::latest('created_at')->limit(8)->get(), 'metrics' => [
            'submitted_requests' => PurchaseRequest::where('status', 'submitted')->count(),
            'open_orders' => PurchaseOrder::whereIn('status', ['approved', 'completed'])->count(),
            'pending_payments' => Payment::where('status', 'pending_verification')->count(),
            'stock_movements' => StockLedger::whereDate('posted_at', today())->count(),
        ]]);
    }
}
