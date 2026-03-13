"use client";

import { useEffect, useState } from "react";
import { getTableQrCode } from "@/services/table-service";
import type { Table, TableQrCode } from "@/types/catalog";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Skeleton } from "@/components/ui/skeleton";
import { Download } from "lucide-react";

interface QrCodeDialogProps {
  table: Table | null;
  onOpenChange: (open: boolean) => void;
}

export function QrCodeDialog({ table, onOpenChange }: QrCodeDialogProps) {
  const [data, setData] = useState<TableQrCode | null>(null);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (!table) {
      setData(null);
      return;
    }

    setLoading(true);
    getTableQrCode(table.id)
      .then((res) => setData(res.data))
      .catch((err) => console.error("Erro ao carregar QR Code:", err))
      .finally(() => setLoading(false));
  }, [table]);

  const handleDownload = () => {
    if (!data) return;

    const link = document.createElement("a");
    link.href = data.qrcode;
    link.download = `qrcode-${table?.identify?.replace(/\s+/g, "-").toLowerCase()}.png`;
    link.click();
  };

  return (
    <Dialog open={!!table} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-sm">
        <DialogHeader>
          <DialogTitle>QR Code — {table?.identify}</DialogTitle>
        </DialogHeader>

        {loading ? (
          <Skeleton className="h-64 w-64 mx-auto" />
        ) : data ? (
          <div className="flex flex-col items-center gap-4">
            {/* eslint-disable-next-line @next/next/no-img-element */}
            <img
              src={data.qrcode}
              alt={`QR Code para ${table?.identify}`}
              className="w-64 h-64"
            />
            <p className="text-sm text-muted-foreground text-center break-all">
              {data.url}
            </p>
            <Button onClick={handleDownload} variant="outline" className="w-full">
              <Download className="mr-2 h-4 w-4" />
              Baixar QR Code
            </Button>
          </div>
        ) : (
          <p className="text-sm text-muted-foreground text-center">
            Erro ao carregar QR Code.
          </p>
        )}
      </DialogContent>
    </Dialog>
  );
}