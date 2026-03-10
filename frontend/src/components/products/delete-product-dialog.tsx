"use client";

import { useState } from "react";
import { deleteProduct } from "@/services/product-service";
import type { Product } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogDescription,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";

interface DeleteProductDialogProps {
  product: Product | null;
  onOpenChange: (open: boolean) => void;
  onDeleted: () => void;
}

export function DeleteProductDialog({
  product,
  onOpenChange,
  onDeleted,
}: DeleteProductDialogProps) {
  const [loading, setLoading] = useState(false);

  const handleDelete = async () => {
    if (!product) return;

    setLoading(true);
    try {
      await deleteProduct(product.id);
      onDeleted();
    } catch (error) {
      console.error("Erro ao remover produto:", error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <Dialog open={!!product} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remover Produto</DialogTitle>
          <DialogDescription>
            Tem certeza que deseja remover o produto &quot;{product?.title}
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