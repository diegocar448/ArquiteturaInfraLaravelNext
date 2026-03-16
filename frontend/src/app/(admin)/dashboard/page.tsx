"use client";

import { useEffect, useState } from "react";
import { getDashboardMetrics } from "@/services/dashboard-service";
import type { DashboardMetrics } from "@/types/dashboard";
import { Skeleton } from "@/components/ui/skeleton";
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    ShoppingBag,
    DollarSign,
    Users,
    Package,
    Star,
} from "lucide-react";
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
} from "recharts";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

const ORDER_STATUS_LABELS: Record<string, string> = {
    open: "Aberto",
    accepted: "Aceito",
    rejected: "Rejeitado",
    preparing: "Preparando",
    done: "Pronto",
    delivered: "Entregue",
};

function StarRating({ stars }: { stars: number }) {
    return (
        <div className="flex gap-0.5">
            {Array.from({ length: 5 }).map((_, i) => (
                <Star
                    key={i}
                    className={`h-3.5 w-3.5 ${
                        i < stars
                            ? "fill-yellow-400 text-yellow-400"
                            : "text-gray-300"
                    }`}
                />
            ))}
        </div>
    );
}

export default function DashboardPage() {
    const [metrics, setMetrics] = useState<DashboardMetrics | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        getDashboardMetrics()
            .then(setMetrics)
            .catch((err) => console.error("Erro ao carregar metricas:", err))
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="space-y-4">
                <Skeleton className="h-8 w-48" />
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    {Array.from({ length: 4 }).map((_, i) => (
                        <Skeleton key={i} className="h-28" />
                    ))}
                </div>
                <Skeleton className="h-72" />
            </div>
        );
    }

    if (!metrics) {
        return (
            <p className="text-muted-foreground">
                Erro ao carregar metricas.
            </p>
        );
    }

    const cards = [
        {
            title: "Pedidos Hoje",
            value: String(metrics.cards.orders_today),
            icon: ShoppingBag,
            description: "pedidos realizados hoje",
        },
        {
            title: "Faturamento Hoje",
            value: `R$ ${Number(metrics.cards.revenue_today).toLocaleString("pt-BR", { minimumFractionDigits: 2 })}`,
            icon: DollarSign,
            description: "receita do dia (excluindo rejeitados)",
        },
        {
            title: "Clientes",
            value: String(metrics.cards.total_clients),
            icon: Users,
            description: "clientes cadastrados",
        },
        {
            title: "Produtos",
            value: String(metrics.cards.total_products),
            icon: Package,
            description: "produtos no catalogo",
        },
    ];

    return (
        <div className="space-y-6">
            <TenantRequiredAlert resource="metricas" />

            <h1 className="text-2xl font-bold">Dashboard</h1>

            {/* Cards de metricas */}
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <Card key={card.title}>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium text-muted-foreground">
                                {card.title}
                            </CardTitle>
                            <card.icon className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {card.value}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {card.description}
                            </p>
                        </CardContent>
                    </Card>
                ))}
            </div>

            {/* Grafico de pedidos por dia */}
            <Card>
                <CardHeader>
                    <CardTitle>Pedidos por dia</CardTitle>
                    <CardDescription>Ultimos 7 dias</CardDescription>
                </CardHeader>
                <CardContent>
                    <ResponsiveContainer width="100%" height={300}>
                        <BarChart data={metrics.orders_per_day}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="label" />
                            <YAxis allowDecimals={false} />
                            <Tooltip
                                formatter={(value) =>
                                    [String(value ?? 0), "Pedidos"]
                                }
                                labelFormatter={(label) =>
                                    `Dia ${String(label)}`
                                }
                            />
                            <Bar
                                dataKey="total"
                                fill="#3b82f6"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ResponsiveContainer>
                </CardContent>
            </Card>

            <div className="grid gap-4 md:grid-cols-2">
                {/* Pedidos por status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Pedidos por status</CardTitle>
                        <CardDescription>
                            Distribuicao de todos os pedidos
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {Object.keys(metrics.orders_by_status).length ===
                        0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhum pedido encontrado.
                            </p>
                        ) : (
                            <div className="space-y-3">
                                {Object.entries(
                                    metrics.orders_by_status,
                                ).map(([status, count]) => (
                                    <div
                                        key={status}
                                        className="flex items-center justify-between"
                                    >
                                        <span className="text-sm">
                                            {ORDER_STATUS_LABELS[status] ||
                                                status}
                                        </span>
                                        <span className="text-sm font-bold">
                                            {count}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Avaliacoes recentes */}
                <Card>
                    <CardHeader>
                        <CardTitle>Avaliacoes recentes</CardTitle>
                        <CardDescription>
                            Ultimas 5 avaliacoes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {metrics.latest_evaluations.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                Nenhuma avaliacao encontrada.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {metrics.latest_evaluations.map((eval_) => (
                                    <div
                                        key={eval_.id}
                                        className="flex items-start justify-between gap-2"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm font-medium">
                                                    {eval_.client_name}
                                                </span>
                                                <span className="text-xs text-muted-foreground">
                                                    {eval_.order_identify}
                                                </span>
                                            </div>
                                            {eval_.comment && (
                                                <p className="text-xs text-muted-foreground">
                                                    &ldquo;{eval_.comment}
                                                    &rdquo;
                                                </p>
                                            )}
                                        </div>
                                        <StarRating
                                            stars={eval_.stars}
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}