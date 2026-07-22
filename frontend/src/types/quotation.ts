export interface QuotationItem {
  id?: number;
  item_no: number;
  description: string;
  quantity: string;
  unit: string;
  unit_price: string;
  discount_percentage: string;
  discount_amount: string;
  tax_percentage: string;
  tax_amount: string;
  line_total: string;
  remarks: string | null;
  sort_order: number;
  is_converted: boolean;
  remaining_quantity: string;
}

export interface Quotation {
  id: number;
  quotation_serial: string;
  customer: {
    id: number;
    customer_code: string;
    name: string;
    company_name: string | null;
    email: string | null;
    phone: string;
    billing_address: string;
    city: string;
    state: string;
    gst_number: string | null;
  } | null;
  quotation_date: string;
  valid_until: string;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  grand_total: string;
  status: string;
  notes: string | null;
  created_by: { id: number; first_name: string; last_name: string } | null;
  items: QuotationItem[];
  created_at: string;
  updated_at: string;
}

export interface QuotationFormData {
  customer_id: string;
  quotation_date: string;
  valid_until: string;
  status?: string;
  notes?: string;
  items: QuotationItemFormData[];
}

export interface QuotationItemFormData {
  description: string;
  quantity: string;
  unit: string;
  unit_price: string;
  discount_percentage?: string;
  tax_percentage?: string;
  remarks?: string;
}
