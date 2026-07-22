export interface Author {
  id: number;
  name: string;
  biography: string | null;
  country: string | null;
  remarks: string | null;
  status: boolean;
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
