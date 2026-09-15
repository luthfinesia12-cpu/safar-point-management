<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function next(string $type): string
    {
        $period = now()->format('Y/m');
        $sequence = DB::transaction(function () use ($type, $period) {
            $row = DB::table('document_sequences')->where(['document_type' => $type, 'period' => $period])->lockForUpdate()->first();
            if ($row === null) {
                DB::table('document_sequences')->insert(['document_type' => $type, 'period' => $period, 'last_number' => 1]);

                return 1;
            }
            $next = $row->last_number + 1;
            DB::table('document_sequences')->where('id', $row->id)->update(['last_number' => $next]);

            return $next;
        });

        return sprintf('%s/SP/%s/%05d', $type, str_replace('/', '/', $period), $sequence);
    }
}
