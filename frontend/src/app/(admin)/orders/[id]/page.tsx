"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { getOrder } from "@/services/order-service";
import type { Order } from "@/types/order";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, ArrowRightLeft } from "lucide-react";
import { OrderStatusBadge } from "@/components/orders/order-status-badge";
import { UpdateStatusDialog } from "@/components/orders/update-status-dialog";
import Link from "next/link";

export default function OrderDetailPage() {
  const params = useParams();
  const router = useRouter();
  const [order, setOrder] = useState<Order | null>(null);
  const [loading, setLoading] = useState(true);
  const [statusOpen, setStatusOpen] = useState(false);

  const fetchOrder = async () => {
    try {
      const response = await getOrder(Number(params.id));
      setOrder(response.data);
    } catch (error) {
      console.error("Erro ao carregar pedido:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrder();
  }, [params.id]);

  const handleStatusUpdated = () => {
    setStatusOpen(false);
    fetchOrder();
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  if (!order) {
    return <p className="text-muted-foreground">Pedido nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" asChild>
          <Link href="/orders">
            <ArrowLeft className="h-4 w-4" />
          </Link>
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{order.identify}</h1>
          <p className="text-sm text-muted-foreground">
            {order.table ? `Mesa: ${order.table.identify}` : "Sem mesa (delivery/retirada)"}
            {order.comment && ` — "${order.comment}"`}
          </p>
        </div>
        <div className="ml-auto flex items-center gap-2">
          <OrderStatusBadge status={order.status} />
          <Button
            variant="outline"
            size="sm"
            onClick={() => setStatusOpen(true)}
          >
            <ArrowRightLeft className="mr-2 h-4 w-4" />
            Alterar Status
          </Button>
        </div>
      </div>

      <Table>
        <TableHeader>
          <TableRow>
            <TableHead>Produto</TableHead>
            <TableHead className="text-right">Preco Unit.</TableHead>
            <TableHead className="text-right">Qtd</TableHead>
            <TableHead className="text-right">Subtotal</TableHead>
          </TableRow>
        </TableHeader>
        <TableBody>
          {order.products.map((product) => (
            <TableRow key={product.id}>
              <TableCell className="font-medium">{product.title}</TableCell>
              <TableCell className="text-right">R$ {product.price}</TableCell>
              <TableCell className="text-right">{product.qty}</TableCell>
              <TableCell className="text-right font-medium">R$ {product.subtotal}</TableCell>
            </TableRow>
          ))}
          <TableRow>
            <TableCell colSpan={3} className="text-right font-bold">
              Total
            </TableCell>
            <TableCell className="text-right font-bold text-lg">
              R$ {order.total}
            </TableCell>
          </TableRow>
        </TableBody>
      </Table>

      <p className="text-sm text-muted-foreground">
        Criado em: {new Date(order.created_at).toLocaleString("pt-BR")}
      </p>

      <UpdateStatusDialog
        order={statusOpen ? order : null}
        onOpenChange={() => setStatusOpen(false)}
        onUpdated={handleStatusUpdated}
      />
    </div>
  );
}