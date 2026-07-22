export interface Customer {
  id: number;
  customer_code: string;
  name: string;
  company_name: string | null;
  email: string | null;
  phone: string;
  alternate_phone: string | null;
  gst_number: string | null;
  pan_number: string | null;
  billing_address: string;
  shipping_address: string | null;
  city: string;
  state: string;
  country: string;
  postal_code: string;
  credit_limit: string;
  opening_balance: string;
  status: "active" | "inactive";
  notes: string | null;
  created_by: {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}
