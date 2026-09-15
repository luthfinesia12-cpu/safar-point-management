<?php

namespace App\Console\Commands;

use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockLedger;
use App\Models\User;
use App\Services\NotificationDeliveryService;
use Illuminate\Console\Command;

class NotifyWorkflowCommand extends Command
{
    protected $signature = 'workflow:notify';

    protected $description = 'Kirim pengingat workflow Release 1';

    public function handle(NotificationDeliveryService $service): int
    {
        $this->notifyRole($service, 'Owner/Direksi', Payment::where('status', 'pending_verification')->count() > 0, 'Pembayaran perlu diverifikasi', 'Ada pembayaran yang menunggu verifikasi.');
        $this->notifyRole($service, 'Purchasing', PurchaseOrder::where('status', 'approved')->whereDate('expected_arrival', '<', today())->exists(), 'PO outstanding', 'Ada PO yang melewati estimasi kedatangan.');
        $this->notifyRole($service, 'Owner/Direksi', PurchaseOrder::where('status', 'approved')->whereDate('expected_arrival', '<', today())->exists(), 'PO outstanding', 'Ada PO yang melewati estimasi kedatangan.');

        $minimum = Product::where('is_active', true)->get()->filter(function (Product $product): bool {
            $balance = (float) StockLedger::where('product_id', $product->id)->latest('id')->value('balance_after');

            return $balance <= (float) $product->minimum_stock;
        })->isNotEmpty();
        $this->notifyRole($service, 'Gudang', $minimum, 'Stok minimum', 'Ada SKU yang mencapai minimum stok.');
        $this->notifyRole($service, 'Purchasing', $minimum, 'Stok minimum', 'Ada SKU yang mencapai minimum stok.');
        $this->notifyRole($service, 'Owner/Direksi', $minimum, 'Stok minimum', 'Ada SKU yang mencapai minimum stok.');

        return self::SUCCESS;
    }

    private function notifyRole(NotificationDeliveryService $service, string $role, bool $condition, string $title, string $message): void
    {
        if (! $condition) {
            return;
        }
        $service->send(User::role($role)->where('is_active', true)->pluck('id')->all(), $title, $message);
    }
}
