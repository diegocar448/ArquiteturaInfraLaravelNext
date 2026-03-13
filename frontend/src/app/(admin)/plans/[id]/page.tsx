"use client";

import { useEffect, useState } from "react";
import { useParams, useRouter } from "next/navigation";
import { getPlan } from "@/services/plan-service";
import {
  getDetailPlans,
  createDetailPlan,
  deleteDetailPlan,
} from "@/services/detail-plan-service";
import type { Plan, DetailPlan } from "@/types/plan";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Skeleton } from "@/components/ui/skeleton";
import { ArrowLeft, Plus, Trash2 } from "lucide-react";

export default function PlanDetailsPage() {
  const params = useParams();
  const router = useRouter();
  const planId = Number(params.id);

  const [plan, setPlan] = useState<Plan | null>(null);
  const [details, setDetails] = useState<DetailPlan[]>([]);
  const [loading, setLoading] = useState(true);
  const [newDetail, setNewDetail] = useState("");
  const [adding, setAdding] = useState(false);

  const fetchData = async () => {
    try {
      const [planRes, detailsRes] = await Promise.all([
        getPlan(planId),
        getDetailPlans(planId),
      ]);
      setPlan(planRes.data);
      setDetails(detailsRes.data);
    } catch (error) {
      console.error("Erro ao carregar plano:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchData();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [planId]);

  const handleAddDetail = async () => {
    if (!newDetail.trim()) return;

    setAdding(true);
    try {
      await createDetailPlan(planId, newDetail.trim());
      setNewDetail("");
      fetchData();
    } catch (error) {
      console.error("Erro ao adicionar detalhe:", error);
    } finally {
      setAdding(false);
    }
  };

  const handleDeleteDetail = async (detailId: number) => {
    try {
      await deleteDetailPlan(planId, detailId);
      fetchData();
    } catch (error) {
      console.error("Erro ao remover detalhe:", error);
    }
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
        <Skeleton className="h-48 w-full" />
      </div>
    );
  }

  if (!plan) {
    return <p>Plano nao encontrado.</p>;
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-4">
        <Button variant="ghost" size="icon" onClick={() => router.push("/plans")}>
          <ArrowLeft className="h-4 w-4" />
        </Button>
        <div>
          <h1 className="text-2xl font-bold">{plan.name}</h1>
          <p className="text-muted-foreground">
            {formatPrice(plan.price)} &middot;{" "}
            <Badge variant="secondary">{plan.url}</Badge>
          </p>
        </div>
      </div>

      {plan.description && (
        <p className="text-muted-foreground">{plan.description}</p>
      )}

      <Card>
        <CardHeader>
          <CardTitle>Detalhes do Plano</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex gap-2">
            <Input
              placeholder="Ex: Ate 50 produtos"
              value={newDetail}
              onChange={(e) => setNewDetail(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && handleAddDetail()}
            />
            <Button onClick={handleAddDetail} disabled={adding}>
              <Plus className="mr-2 h-4 w-4" />
              Adicionar
            </Button>
          </div>

          {details.length === 0 ? (
            <p className="text-sm text-muted-foreground py-4 text-center">
              Nenhum detalhe cadastrado. Adicione as features deste plano.
            </p>
          ) : (
            <ul className="space-y-2">
              {details.map((detail) => (
                <li
                  key={detail.id}
                  className="flex items-center justify-between rounded-md border px-4 py-2"
                >
                  <span>{detail.name}</span>
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => handleDeleteDetail(detail.id)}
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </li>
              ))}
            </ul>
          )}
        </CardContent>
      </Card>
    </div>
  );
}