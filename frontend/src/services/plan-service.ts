import { apiClient } from "@/lib/api";
import type { Plan, PaginatedResponse } from "@/types/plan";

export async function getPlans(page = 1): Promise<PaginatedResponse<Plan>> {
  return apiClient<PaginatedResponse<Plan>>(`/v1/plans?page=${page}`);
}

export async function getPlan(id: number): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>(`/v1/plans/${id}`);
}

export async function createPlan(data: {
  name: string;
  price: number;
  description?: string;
}): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>("/v1/plans", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updatePlan(
  id: number,
  data: { name: string; price: number; url?: string; description?: string }
): Promise<{ data: Plan }> {
  return apiClient<{ data: Plan }>(`/v1/plans/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deletePlan(id: number): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/plans/${id}`, {
    method: "DELETE",
  });
}