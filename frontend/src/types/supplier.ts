export interface Supplier {
  id: number;
  name: string;
  company_name: string;
  phone: string;
  email: string | null;
  gst_number: string | null;
  address: string;
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
