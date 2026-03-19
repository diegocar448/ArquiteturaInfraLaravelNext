<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function liveness(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function readiness(): JsonResponse
    {
        $checks = [];

        // Verificar banco de dados
        try {
            DB::connection()->getPdo();
            $checks['database'] = 'ok';
        } catch (\Exception $e) {
            $checks['database'] = 'failed: ' . $e->getMessage();
        }

        // Verificar Redis
        try {
            Cache::store('redis')->put('health_check', true, 10);
            $checks['redis'] = Cache::store('redis')->get('health_check') ? 'ok' : 'failed';
        } catch (\Exception $e) {
            $checks['redis'] = 'failed: ' . $e->getMessage();
        }

        $allHealthy = collect($checks)->every(fn ($status) => $status === 'ok');

        return response()->json([
            'status' => $allHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => now()->toISOString(),
        ], $allHealthy ? 200 : 503);
    }
}