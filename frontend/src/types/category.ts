export interface Category {
  id: number;
  parent_id: number | null;
  name: string;
  description: string | null;
  status: boolean;
  parent?: Category | null;
  children?: Category[];
  created_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  updated_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}
