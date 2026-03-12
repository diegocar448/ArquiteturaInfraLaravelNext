export interface DashboardCards {
    orders_today: number;
    revenue_today: string;
    total_clients: number;
    total_products: number;
}

export interface OrdersPerDay {
    date: string;
    label: string;
    total: number;
}

export interface LatestEvaluation {
    id: number;
    stars: number;
    comment: string | null;
    client_name: string;
    order_identify: string;
    created_at: string;
}

export interface DashboardMetrics {
    cards: DashboardCards;
    orders_per_day: OrdersPerDay[];
    orders_by_status: Record<string, number>;
    latest_evaluations: LatestEvaluation[];
}