export interface Plan {
  id: number;
  name: string;
  url: string;
  price: string;
  description: string | null;
  details?: DetailPlan[];
  created_at: string;
  updated_at: string;
}

export interface DetailPlan {
  id: number;
  plan_id: number;
  name: string;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string;
    last: string;
    prev: string | null;
    next: string | null;
  };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}