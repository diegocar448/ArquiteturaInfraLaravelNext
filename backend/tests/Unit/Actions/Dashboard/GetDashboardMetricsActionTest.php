<?php

use App\Actions\Dashboard\GetDashboardMetricsAction;

describe('GetDashboardMetricsAction', function () {
    it('returns expected structure', function () {
        $action = new GetDashboardMetricsAction;
        $result = $action->execute(null);

        expect($result)->toHaveKeys([
            'cards',
            'orders_per_day',
            'orders_by_status',
            'latest_evaluations',
        ]);
    });

    it('returns 7 days in orders_per_day', function () {
        $action = new GetDashboardMetricsAction;
        $result = $action->execute(null);

        expect($result['orders_per_day'])->toHaveCount(7);
    });

    it('has correct card keys', function () {
        $action = new GetDashboardMetricsAction;
        $result = $action->execute(null);

        expect($result['cards'])->toHaveKeys([
            'orders_today',
            'revenue_today',
            'total_clients',
            'total_products',
        ]);
    });
});
