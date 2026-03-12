import { apiClient } from "@/lib/api";
import type { Evaluation } from "@/types/evaluation";
import type { PaginatedResponse } from "@/types/plan";

export async function getEvaluations(
  page = 1
): Promise<PaginatedResponse<Evaluation>> {
  return apiClient<PaginatedResponse<Evaluation>>(
    `/v1/evaluations?page=${page}`
  );
}

export async function deleteEvaluation(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/evaluations/${id}`, {
    method: "DELETE",
  });
}