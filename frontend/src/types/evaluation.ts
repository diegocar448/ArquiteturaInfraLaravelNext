export interface Evaluation {
  id: number;
  stars: number;
  comment: string | null;
  client: {
    id: number;
    uuid: string;
    name: string;
    email: string;
  };
  order: {
    id: number;
    identify: string;
  };
  created_at: string;
}