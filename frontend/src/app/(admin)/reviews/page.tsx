"use client";

import { useEffect, useState } from "react";
import { getEvaluations, deleteEvaluation } from "@/services/evaluation-service";
import type { Evaluation } from "@/types/evaluation";
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
import { Star, Trash2 } from "lucide-react";
import { TenantRequiredAlert } from "@/components/tenant-required-alert";

function StarRating({ stars }: { stars: number }) {
  return (
    <div className="flex gap-0.5">
      {Array.from({ length: 5 }).map((_, i) => (
        <Star
          key={i}
          className={`h-4 w-4 ${
            i < stars
              ? "fill-yellow-400 text-yellow-400"
              : "text-gray-300"
          }`}
        />
      ))}
    </div>
  );
}

export default function ReviewsPage() {
  const [evaluations, setEvaluations] = useState<Evaluation[]>([]);
  const [loading, setLoading] = useState(true);

  const fetchEvaluations = async () => {
    try {
      const response = await getEvaluations();
      setEvaluations(response.data);
    } catch (error) {
      console.error("Erro ao carregar avaliacoes:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchEvaluations();
  }, []);

  const handleDelete = async (id: number) => {
    if (!confirm("Tem certeza que deseja remover esta avaliacao?")) return;

    try {
      await deleteEvaluation(id);
      fetchEvaluations();
    } catch (error) {
      console.error("Erro ao remover avaliacao:", error);
    }
  };

  return (
    <div className="space-y-4">
      <TenantRequiredAlert resource="avaliacoes" />

      <h1 className="text-2xl font-bold">Avaliacoes</h1>

      {loading ? (
        <div className="space-y-2">
          {Array.from({ length: 5 }).map((_, i) => (
            <Skeleton key={i} className="h-12 w-full" />
          ))}
        </div>
      ) : (
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Pedido</TableHead>
              <TableHead>Cliente</TableHead>
              <TableHead>Nota</TableHead>
              <TableHead>Comentario</TableHead>
              <TableHead>Data</TableHead>
              <TableHead className="w-[60px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {evaluations.length === 0 ? (
              <TableRow>
                <TableCell colSpan={6} className="text-center text-muted-foreground">
                  Nenhuma avaliacao encontrada.
                </TableCell>
              </TableRow>
            ) : (
              evaluations.map((evaluation) => (
                <TableRow key={evaluation.id}>
                  <TableCell className="font-mono font-medium">
                    {evaluation.order.identify}
                  </TableCell>
                  <TableCell>{evaluation.client.name}</TableCell>
                  <TableCell>
                    <StarRating stars={evaluation.stars} />
                  </TableCell>
                  <TableCell className="max-w-xs truncate text-muted-foreground">
                    {evaluation.comment || "—"}
                  </TableCell>
                  <TableCell className="text-muted-foreground text-sm">
                    {new Date(evaluation.created_at).toLocaleDateString("pt-BR")}
                  </TableCell>
                  <TableCell>
                    <Button
                      size="icon"
                      variant="ghost"
                      title="Remover"
                      onClick={() => handleDelete(evaluation.id)}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      )}
    </div>
  );
}