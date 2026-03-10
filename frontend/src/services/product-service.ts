import { apiClient } from "@/lib/api";
import type { Product } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getProducts(
  page = 1
): Promise<PaginatedResponse<Product>> {
  return apiClient<PaginatedResponse<Product>>(
    `/v1/products?page=${page}`
  );
}

export async function getProduct(
  id: number
): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>(`/v1/products/${id}`);
}

export async function createProduct(data: {
  title: string;
  price: number;
  flag?: string;
  image?: string;
  description?: string;
}): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>("/v1/products", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateProduct(
  id: number,
  data: {
    title: string;
    price: number;
    url?: string;
    flag?: string;
    image?: string;
    description?: string;
  }
): Promise<{ data: Product }> {
  return apiClient<{ data: Product }>(`/v1/products/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteProduct(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/products/${id}`, {
    method: "DELETE",
  });
}