import { http, HttpResponse } from "msw";

const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost/api";

export const handlers = [
    // Auth
    http.post(`${API_URL}/v1/auth/login`, () => {
        return HttpResponse.json({
            access_token: "fake-jwt-token",
            token_type: "bearer",
            expires_in: 3600,
        });
    }),

    http.get(`${API_URL}/v1/auth/me`, () => {
        return HttpResponse.json({
            id: 1,
            name: "Admin",
            email: "admin@orderly.com",
            tenant_id: 1,
        });
    }),

    // Dashboard metrics
    http.get(`${API_URL}/v1/dashboard/metrics`, () => {
        return HttpResponse.json({
            data: {
                cards: {
                    orders_today: 5,
                    revenue_today: "375.90",
                    total_clients: 4,
                    total_products: 8,
                },
                orders_per_day: Array.from({ length: 7 }, (_, i) => ({
                    date: `2026-03-${String(6 + i).padStart(2, "0")}`,
                    label: `${String(6 + i).padStart(2, "0")}/03`,
                    total: Math.floor(Math.random() * 10),
                })),
                orders_by_status: {
                    open: 2,
                    delivered: 3,
                    preparing: 1,
                },
                latest_evaluations: [
                    {
                        id: 1,
                        stars: 5,
                        comment: "Otimo!",
                        client_name: "Joao",
                        order_identify: "ORD-001",
                        created_at: "2026-03-12T10:00:00Z",
                    },
                ],
            },
        });
    }),

    // Plans
    http.get(`${API_URL}/v1/plans`, () => {
        return HttpResponse.json({
            data: [
                {
                    id: 1,
                    uuid: "abc-123",
                    name: "Plano Basic",
                    price: "49.90",
                    description: "Plano basico",
                },
            ],
            meta: { current_page: 1, last_page: 1, total: 1 },
        });
    }),
];