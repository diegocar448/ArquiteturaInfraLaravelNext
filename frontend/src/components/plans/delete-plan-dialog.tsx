"use client";

import { useState } from "react";
import { deletePlan } from "@/services/plan-service";
import type { Plan } from "@/types/plan";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeletePlanDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
  plan: Plan;
}

export function DeletePlanDialog({
  open,
  onOpenChange,
  onDeleted,
  plan,
}: DeletePlanDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    setLoading(true);
    try {
      await deletePlan(plan.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao deletar plano:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Excluir Plano</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja excluir o plano &quot;{plan.name}&quot;? Esta
            acao nao pode ser desfeita.
          </DialogDescription>
        </DialogHeader>

        <div className="flex justify-end gap-2">
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Cancelar
          </Button>
          <Button
            variant="destructive"
            onClick={handleDelete}
            disabled={loading}
          >
            {loading ? "Excluindo..." : "Excluir"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}