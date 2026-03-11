"use client";

import { useState } from "react";
import { deleteTable } from "@/services/table-service";
import type { Table } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteTableDialogProps {
  table: Table | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteTableDialog({
  table,
  onOpenChange,
  onDeleted,
}: DeleteTableDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!table) return;

    setLoading(true);
    try {
      await deleteTable(table.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover mesa:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!table} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Mesa</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover a mesa &quot;{table?.identify}
            &quot;? Esta acao nao pode ser desfeita.
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
            {loading ? "Removendo..." : "Remover"}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}