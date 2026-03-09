import { apiClient } from "@/lib/api";
import type { DetailPlan } from "@/types/plan";

export async function getDetailPlans(
  planId: number
): Promise<{ data: DetailPlan[] }> {
  return apiClient<{ data: DetailPlan[] }>(`/v1/plans/${planId}/details`);
}

export async function createDetailPlan(
  planId: number,
  name: string
): Promise<{ data: DetailPlan }> {
  return apiClient<{ data: DetailPlan }>(`/v1/plans/${planId}/details`, {
    method: "POST",
    body: JSON.stringify({ name }),
  });
}

export async function updateDetailPlan(
  planId: number,
  detailId: number,
  name: string
): Promise<{ data: DetailPlan }> {
  return apiClient<{ data: DetailPlan }>(
    `/v1/plans/${planId}/details/${detailId}`,
    {
      method: "PUT",
      body: JSON.stringify({ name }),
    }
  );
}

export async function deleteDetailPlan(
  planId: number,
  detailId: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(
    `/v1/plans/${planId}/details/${detailId}`,
    {
      method: "DELETE",
    }
  );
}