export interface Category {
  id: number;
  uuid: string;
  name: string;
  url: string;
  description: string | null;
  products?: Product[];
  created_at: string;
  updated_at: string;
}

export interface Product {
  id: number;
  uuid: string;
  title: string;
  url: string;
  flag: "active" | "inactive" | "featured";
  image: string | null;
  price: string;
  description: string | null;
  categories?: Category[];
  created_at: string;
  updated_at: string;
}

export interface Table {
  id: number;
  uuid: string;
  identify: string;
  description: string | null;
  created_at: string;
  updated_at: string;
}

export interface TableQrCode {
  table: Table;
  qrcode: string;
  url: string;
}