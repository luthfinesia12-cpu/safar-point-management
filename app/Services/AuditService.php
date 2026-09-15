<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditService
{
    public function record(Request $request, string $action, string $module, string $description, ?array $before = null, ?array $after = null, ?int $userId = null): void
    {
        $redact = static function (?array $data): ?array {
            if ($data === null) {
                return null;
            }
            unset($data['password'], $data['password_confirmation'], $data['token'], $data['remember_token']);

            return $data;
        };

        AuditLog::create([
            'user_id' => $userId ?? $request->user()?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'before_data' => $redact($before),
            'after_data' => $redact($after),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
