"use client";

import { useState } from "react";
import { updateOrderStatus } from "@/services/order-service";
import type { Order, OrderStatus } from "@/types/order";
import { VALID_TRANSITIONS, ORDER_STATUS_LABELS } from "@/types/order";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface UpdateStatusDialogProps {
  order: Order | null;
  onOpenChange: (open: boolean) => void;
  onUpdated: () => void;
}

export function UpdateStatusDialog({
  order,
  onOpenChange,
  onUpdated,
}: UpdateStatusDialogProps) {
  const [loading, setLoading] = useState(false);

  const transitions = order ? (VALID_TRANSITIONS[order.status] ?? []) : [];

  const handleTransition = async (newStatus: OrderStatus) => {
    if (!order) return;

    setLoading(true);
    try {
      await updateOrderStatus(order.id, newStatus);
      onUpdated();
    } catch (error) {
      console.error("Erro ao atualizar status:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!order} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Atualizar Status — {order?.identify}</DialogTitle>
          <DialogDescription>
            Status atual:{" "}
            <strong>{order ? ORDER_STATUS_LABELS[order.status] : ""}</strong>.
            Selecione o novo status:
          </DialogDescription>
        </DialogHeader>

        {transitions.length === 0 ? (
          <p className="text-sm text-muted-foreground">
            Nenhuma transicao disponivel para este status.
          </p>
        ) : (
          <div className="flex flex-col gap-2">
            {transitions.map((status) => (
              <Button
                key={status}
                variant={status === "rejected" ? "destructive" : "default"}
                onClick={() => handleTransition(status)}
                disabled={loading}
              >
                {ORDER_STATUS_LABELS[status]}
              </Button>
            ))}
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
