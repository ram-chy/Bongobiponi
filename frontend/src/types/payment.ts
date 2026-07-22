export interface Payment {
  id: number;
  payment_no: string;
  customer: {
    id: number;
    name: string;
    company_name: string | null;
    email: string | null;
    phone: string;
    billing_address: string;
    city: string;
    state: string;
    gst_number: string | null;
  } | null;
  payment_date: string;
  payment_method: string;
  reference_no: string | null;
  remarks: string | null;
  total_amount: string;
  payment_status: string;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  updated_by: { id: number; first_name: string; last_name: string; email: string } | null;
  items: PaymentItem[];
  created_at: string;
  updated_at: string;
}

export interface PaymentItem {
  id: number;
  payment_id: number;
  invoice: {
    id: number;
    serial: string;
    grand_total: string;
  } | null;
  paid_amount: string;
  remarks: string | null;
}

export interface PaymentFormData {
  customer_id: string;
  payment_date: string;
  payment_method: string;
  reference_no: string;
  remarks: string;
  items: PaymentItemFormData[];
}

export interface PaymentItemFormData {
  invoice_id: string;
  paid_amount: string;
  remarks: string;
}

export interface DueInvoice {
  id: number;
  serial: string;
  invoice_date: string;
  grand_total: number;
  paid_amount: number;
  due_amount: number;
  payment_status: string;
}

export interface PaymentListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  payment_method?: string;
  customer_id?: string;
  date_from?: string;
  date_to?: string;
}
