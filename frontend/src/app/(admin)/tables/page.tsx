"use client";

import { useEffect, useState } from "react";
import { getTables } from "@/services/table-service";
import type { Table } from "@/types/catalog";
import {
  Table as UITable,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2, QrCode } from "lucide-react";
import { TableFormDialog } from "@/components/tables/table-form-dialog";
import { DeleteTableDialog } from "@/components/tables/delete-table-dialog";
import { QrCodeDialog } from "@/components/tables/qrcode-dialog";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

export default function TablesPage() {
  const [tables, setTables] = useState<Table[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editTable, setEditTable] = useState<Table | null>(null);
  const [deleteState, setDeleteState] = useState<Table | null>(null);
  const [qrTable, setQrTable] = useState<Table | null>(null);

  const fetchTables = async () => {
    try {
      const response = await getTables();
      setTables(response.data);
    } catch (error) {
      console.error("Erro ao carregar mesas:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchTables();
  }, []);

  const handleSaved = () => {
    setCreateOpen(false);
    setEditTable(null);
    fetchTables();
  };

  const handleDeleted = () => {
    setDeleteState(null);
    fetchTables();
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="mesas" />

      <div className="flex items-center justify-between">
        <h1 className="text-2xl font-bold">Mesas</h1>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Nova Mesa
        </Button>
      </div>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <UITable>
          <TableHeader>
            <TableRow>
              <TableHead>Identificador</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead>UUID</TableHead>
              <TableHead className="w-[140px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {tables.length === 0 ? (
              <TableRow>
                <TableCell colSpan={4} className="text-center text-muted-foreground">
                  Nenhuma mesa cadastrada.
                </TableCell>
              </TableRow>
            ) : (
              tables.map((table) => (
                <TableRow key={table.id}>
                  <TableCell className="font-medium">{table.identify}</TableCell>
                  <TableCell>{table.description || "—"}</TableCell>
                  <TableCell className="text-muted-foreground text-xs font-mono">
                    {table.uuid.substring(0, 8)}...
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        size="icon"
                        variant="ghost"
                        title="QR Code"
                        onClick={() => setQrTable(table)}
                      >
                        <QrCode className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Editar"
                        onClick={() => setEditTable(table)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        size="icon"
                        variant="ghost"
                        title="Remover"
                        onClick={() => setDeleteState(table)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </UITable>
      )}

      <TableFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {editTable && (
        <TableFormDialog
          open={!!editTable}
          onOpenChange={() => setEditTable(null)}
          onSaved={handleSaved}
          table={editTable}
        />
      )}

      <DeleteTableDialog
        table={deleteState}
        onOpenChange={() => setDeleteState(null)}
        onDeleted={handleDeleted}
      />

      <QrCodeDialog
        table={qrTable}
        onOpenChange={() => setQrTable(null)}
      />
    </div>
  );
}