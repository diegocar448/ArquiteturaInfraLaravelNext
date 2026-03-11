import { apiClient } from "@/lib/api";
import type { Table, TableQrCode } from "@/types/catalog";
import type { PaginatedResponse } from "@/types/plan";

export async function getTables(
  page = 1
): Promise<PaginatedResponse<Table>> {
  return apiClient<PaginatedResponse<Table>>(
    `/v1/tables?page=${page}`
  );
}

export async function getTable(
  id: number
): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>(`/v1/tables/${id}`);
}

export async function createTable(data: {
  identify: string;
  description?: string;
}): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>("/v1/tables", {
    method: "POST",
    body: JSON.stringify(data),
  });
}

export async function updateTable(
  id: number,
  data: { identify: string; description?: string }
): Promise<{ data: Table }> {
  return apiClient<{ data: Table }>(`/v1/tables/${id}`, {
    method: "PUT",
    body: JSON.stringify(data),
  });
}

export async function deleteTable(
  id: number
): Promise<{ message: string }> {
  return apiClient<{ message: string }>(`/v1/tables/${id}`, {
    method: "DELETE",
  });
}

export async function getTableQrCode(
  id: number
): Promise<{ data: TableQrCode }> {
  return apiClient<{ data: TableQrCode }>(`/v1/tables/${id}/qrcode`);
}