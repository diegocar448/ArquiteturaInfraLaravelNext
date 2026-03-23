"use client";

import { useEffect } from "react";
import { useForm } from "react-hook-form";
import { standardSchemaResolver } from "@hookform/resolvers/standard-schema";
import { z } from "zod";
import { createTable, updateTable } from "@/services/table-service";
import type { Table } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";
import { ApiError } from "@/lib/api";

const tableSchema = z.object({
  identify: z.string().min(1, "O identificador e obrigatorio"),
  description: z.string().optional(),
});

type TableFormData = z.infer<typeof tableSchema>;

interface TableFormDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onSaved: () => void;
  table?: Table;
}

export function TableFormDialog({
  open,
  onOpenChange,
  onSaved,
  table,
}: TableFormDialogProps) {
  const isEditing = !!table;

  const {
    register,
    handleSubmit,
    reset,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<TableFormData>({
    resolver: standardSchemaResolver(tableSchema),
    defaultValues: {
      identify: table?.identify || "",
      description: table?.description || "",
    },
  });

  useEffect(() => {
    if (open) {
      reset({
        identify: table?.identify || "",
        description: table?.description || "",
      });
    }
  }, [open, table, reset]);

  const onSubmit = async (data: TableFormData) => {
    try {
      if (isEditing) {
        await updateTable(table.id, data);
      } else {
        await createTable(data);
      }
      onSaved();
    } catch (error) {
      if (error instanceof ApiError) {
        setError("root", { message: error.message });
      }
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            {isEditing ? "Editar Mesa" : "Nova Mesa"}
          </DialogTitle>
        </DialogHeader>

        <form onSubmit={handleSubmit(onSubmit)} className="space-y-4">
          {errors.root && (
            <p className="text-sm text-destructive">{errors.root.message}</p>
          )}

          <div className="space-y-2">
            <Label htmlFor="identify">Identificador</Label>
            <Input id="identify" placeholder="Mesa 01, VIP-03..." {...register("identify")} />
            {errors.identify && (
              <p className="text-sm text-destructive">{errors.identify.message}</p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="description">Descricao</Label>
            <Textarea id="description" placeholder="Area interna, 4 lugares..." {...register("description")} />
          </div>

          <div className="flex justify-end gap-2">
            <Button
              type="button"
              variant="outline"
              onClick={() => onOpenChange(false)}
            >
              Cancelar
            </Button>
            <Button type="submit" disabled={isSubmitting}>
              {isSubmitting ? "Salvando..." : "Salvar"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}