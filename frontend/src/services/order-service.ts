import { apiClient } from "@/lib/api";
import type { Order } from "@/types/order";
import type { PaginatedResponse } from "@/types/plan";

export async function getOrders(
  page = 1,
  status?: string
): Promise<PaginatedResponse<Order>> {
  const params = new URLSearchParams({ page: String(page) });
  if (status) params.set("status", status);

  return apiClient<PaginatedResponse<Order>>(
    `/v1/orders?${params.toString()}`
  );
}

export async function getOrder(
  id: number
): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>(`/v1/orders/${id}`);
}

export async function createOrder(data: {
  table_id?: number | null;
  comment?: string;
  products: { product_id: number; qty: number }[];
}): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>("/v1/orders", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateOrderStatus(
  id: number,
  status: string
): Promise<{ data: Order }> {
  return apiClient<{ data: Order }>(`/v1/orders/${id}`, {
    method: "PUT",
    body: JSON.stringify({ status }),
  });
}

export async function deleteOrder(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/orders/${id}`, {
    method: "DELETE",
  });
}