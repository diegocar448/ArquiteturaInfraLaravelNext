<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Dashboard\GetDashboardMetricsAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * @tags Dashboard
 */
class DashboardController extends Controller
{
    /**
     * Metricas do dashboard
     *
     * Retorna metricas agregadas: pedidos do dia, faturamento, pedidos por dia
     * (ultimos 7 dias), pedidos por status e avaliacoes recentes.
     * Para super-admin, retorna metricas globais de todos os tenants.
     */
    public function metrics(GetDashboardMetricsAction $action): JsonResponse
    {
        $user = auth('api')->user();
        $tenantId = $user->tenant_id;

        // Super-admin ve metricas globais
        if ($user->isSuperAdmin()) {
            $tenantId = null;
        }

        return response()->json([
            'data' => $action->execute($tenantId),
        ]);
    }
}
