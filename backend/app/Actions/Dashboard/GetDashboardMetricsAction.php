<?php

namespace App\Actions\Dashboard;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderEvaluation;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GetDashboardMetricsAction
{
    /**
     * @param  int|null  $tenantId  null = super-admin (metricas globais)
     */
    public function execute(?int $tenantId): array
    {
        return [
            'cards' => $this->getCards($tenantId),
            'orders_per_day' => $this->getOrdersPerDay($tenantId),
            'orders_by_status' => $this->getOrdersByStatus($tenantId),
            'latest_evaluations' => $this->getLatestEvaluations($tenantId),
        ];
    }

    private function getCards(?int $tenantId): array
    {
        $orderQuery = Order::query()->withoutGlobalScopes();
        $productQuery = Product::query()->withoutGlobalScopes();

        if ($tenantId) {
            $orderQuery->where('tenant_id', $tenantId);
            $productQuery->where('tenant_id', $tenantId);
        }

        $ordersToday = (clone $orderQuery)
            ->whereDate('created_at', Carbon::today())
            ->count();

        $revenueToday = (clone $orderQuery)
            ->whereDate('created_at', Carbon::today())
            ->where('status', '!=', Order::STATUS_REJECTED)
            ->sum('total');

        $totalClients = Client::count();

        $totalProducts = (clone $productQuery)->count();

        return [
            'orders_today' => $ordersToday,
            'revenue_today' => number_format($revenueToday, 2, '.', ''),
            'total_clients' => $totalClients,
            'total_products' => $totalProducts,
        ];
    }

    private function getOrdersPerDay(?int $tenantId): array
    {
        $query = Order::query()->withoutGlobalScopes()
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', Carbon::today()->subDays(6))
            ->groupBy('date')
            ->orderBy('date');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $results = $query->get();

        // Preencher dias sem pedidos com 0
        $days = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->toDateString();
            $found = $results->firstWhere('date', $date);
            $days[] = [
                'date' => $date,
                'label' => Carbon::parse($date)->format('d/m'),
                'total' => $found ? (int) $found->total : 0,
            ];
        }

        return $days;
    }

    private function getOrdersByStatus(?int $tenantId): array
    {
        $query = Order::query()->withoutGlobalScopes()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status');

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->get()
            ->pluck('total', 'status')
            ->toArray();
    }

    private function getLatestEvaluations(?int $tenantId): array
    {
        $query = OrderEvaluation::with(['client', 'order'])
            ->latest()
            ->limit(5);

        if ($tenantId) {
            $query->whereHas('order', fn ($q) => $q->where('tenant_id', $tenantId));
        }

        return $query->get()->map(fn ($eval) => [
            'id' => $eval->id,
            'stars' => $eval->stars,
            'comment' => $eval->comment,
            'client_name' => $eval->client->name,
            'order_identify' => $eval->order->identify,
            'created_at' => $eval->created_at->toISOString(),
        ])->toArray();
    }
}