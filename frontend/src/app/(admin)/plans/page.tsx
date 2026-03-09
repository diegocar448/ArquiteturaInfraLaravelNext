"use client";

import { useEffect, useState } from "react";
import { getPlans, deletePlan } from "@/services/plan-service";
import type { Plan } from "@/types/plan";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { Plus, Pencil, Trash2 } from "lucide-react";
import { PlanFormDialog } from "@/components/plans/plan-form-dialog";
import { DeletePlanDialog } from "@/components/plans/delete-plan-dialog";
import Link from "next/link";

export default function PlansPage() {
  const [plans, setPlans] = useState<Plan[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editPlan, setEditPlan] = useState<Plan | null>(null);
  const [deletePlanState, setDeletePlanState] = useState<Plan | null>(null);

  const fetchPlans = async () => {
    try {
      const response = await getPlans();
      setPlans(response.data);
    } catch (error) {
      console.error("Erro ao carregar planos:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchPlans();
  }, []);

  const handleDeleted = () => {
    setDeletePlanState(null);
    fetchPlans();
  };

  const handleSaved = () => {
    setCreateOpen(false);
    setEditPlan(null);
    fetchPlans();
  };

  const formatPrice = (price: string) => {
    return new Intl.NumberFormat("pt-BR", {
      style: "currency",
      currency: "BRL",
    }).format(Number(price));
  };

  if (loading) {
    return (
      <div className="space-y-4">
        <Skeleton className="h-8 w-48" />
        <Skeleton className="h-64 w-full" />
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold">Planos</h1>
          <p className="text-muted-foreground">
            Gerencie os planos de assinatura da plataforma.
          </p>
        </div>
        <Button onClick={() => setCreateOpen(true)}>
          <Plus className="mr-2 h-4 w-4" />
          Novo Plano
        </Button>
      </div>

      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              <TableHead>Nome</TableHead>
              <TableHead>URL</TableHead>
              <TableHead>Preco</TableHead>
              <TableHead>Descricao</TableHead>
              <TableHead className="w-[100px]">Acoes</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {plans.length === 0 ? (
              <TableRow>
                <TableCell colSpan={5} className="text-center py-8">
                  Nenhum plano cadastrado.
                </TableCell>
              </TableRow>
            ) : (
              plans.map((plan) => (
                <TableRow key={plan.id}>
                  <TableCell className="font-medium">
                    <Link href={`/plans/${plan.id}`} className="hover:underline">
                      {plan.name}
                    </Link>
                  </TableCell>
                  <TableCell>
                    <Badge variant="secondary">{plan.url}</Badge>
                  </TableCell>
                  <TableCell>{formatPrice(plan.price)}</TableCell>
                  <TableCell className="max-w-xs truncate">
                    {plan.description || "—"}
                  </TableCell>
                  <TableCell>
                    <div className="flex gap-1">
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setEditPlan(plan)}
                      >
                        <Pencil className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => setDeletePlanState(plan)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Dialog de criar */}
      <PlanFormDialog
        open={createOpen}
        onOpenChange={setCreateOpen}
        onSaved={handleSaved}
      />

      {/* Dialog de editar */}
      {editPlan && (
        <PlanFormDialog
          open={!!editPlan}
          onOpenChange={(open) => !open && setEditPlan(null)}
          onSaved={handleSaved}
          plan={editPlan}
        />
      )}

      {/* Dialog de deletar */}
      {deletePlanState && (
        <DeletePlanDialog
          open={!!deletePlanState}
          onOpenChange={(open) => !open && setDeletePlanState(null)}
          onDeleted={handleDeleted}
          plan={deletePlanState}
        />
      )}
    </div>
  );
}