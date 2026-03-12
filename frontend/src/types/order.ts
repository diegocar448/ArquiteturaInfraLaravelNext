import type { Table } from "./catalog";

export type OrderStatus = "open" | "accepted" | "rejected" | "preparing" | "done" | "delivered";

export interface OrderProduct {
  id: number;
  title: string;
  price: string;
  qty: number;
  subtotal: string;
}

export interface Order {
  id: number;
  uuid: string;
  identify: string;
  status: OrderStatus;
  total: string;
  comment: string | null;
  table: Table | null;
  products: OrderProduct[];
  created_at: string;
  updated_at: string;
}

export const ORDER_STATUS_LABELS: Record<OrderStatus, string> = {
  open: "Aberto",
  accepted: "Aceito",
  rejected: "Rejeitado",
  preparing: "Preparando",
  done: "Pronto",
  delivered: "Entregue",
};

export const ORDER_STATUS_COLORS: Record<OrderStatus, string> = {
  open: "bg-blue-100 text-blue-800",
  accepted: "bg-green-100 text-green-800",
  rejected: "bg-red-100 text-red-800",
  preparing: "bg-yellow-100 text-yellow-800",
  done: "bg-purple-100 text-purple-800",
  delivered: "bg-gray-100 text-gray-800",
};

export const VALID_TRANSITIONS: Record<string, OrderStatus[]> = {
  open: ["accepted", "rejected"],
  accepted: ["preparing", "rejected"],
  preparing: ["done"],
  done: ["delivered"],
};