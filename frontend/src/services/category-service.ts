import { apiClient } from "@/lib/api";
import type { Category } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getCategories(
  page = 1
): Promise<PaginatedResponse<Category>> {
  return apiClient<PaginatedResponse<Category>>(
    `/v1/categories?page=${page}`
  );
}

export async function getCategory(
  id: number
): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>(`/v1/categories/${id}`);
}

export async function createCategory(data: {
  name: string;
  description?: string;
}): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>("/v1/categories", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateCategory(
  id: number,
  data: { name: string; url?: string; description?: string }
): Promise<{ data: Category }> {
  return apiClient<{ data: Category }>(`/v1/categories/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteCategory(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/categories/${id}`, {
    method: "DELETE",
  });
}