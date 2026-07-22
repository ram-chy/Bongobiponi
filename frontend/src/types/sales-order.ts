export interface SalesOrder {
  id: number;
  document_reference_uuid: string;
  sales_order_serial: string;
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
  sales_order_source: string;
  sales_order_date: string;
  expected_delivery_date: string | null;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  grand_total: string;
  currency: string | null;
  exchange_rate: string | null;
  status: string;
  reference_notes: string | null;
  notes: string | null;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_by: { id: number; first_name: string; last_name: string; email: string } | null;
  approved_at: string | null;
  confirmed_at: string | null;
  pdf_generated_at: string | null;
  items: SalesOrderItem[];
  created_at: string;
  updated_at: string;
}

export interface SalesOrderItem {
  id: number;
  sales_order_id: number;
  order_id: number;
  order_item_id: number;
  source_type: string;
  item_no: number;
  description: string;
  unit: string;
  ordered_quantity: string;
  sales_order_quantity: string;
  remaining_sales_quantity: string;
  unit_price: string;
  discount_percentage: string;
  discount_amount: string;
  tax_percentage: string;
  tax_amount: string;
  line_total: string;
  remarks: string | null;
  sort_order: number;
}

export interface SalesOrderFormData {
  customer_id: string;
  sales_order_date: string;
  expected_delivery_date: string;
  reference_notes: string;
  notes: string;
  items: SalesOrderItemFormData[];
}

export interface SalesOrderItemFormData {
  order_item_id: string;
  order_id: string;
  source_type: string;
  description: string;
  unit: string;
  ordered_quantity: string;
  sales_order_quantity: string;
  remaining_order_quantity: string;
  unit_price: string;
  discount_percentage: string;
  tax_percentage: string;
  remarks: string;
  order_serial?: string;
  quotation_serial?: string;
  original_source_type?: string;
}

export interface SalesOrderListParams {
  search?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: "asc" | "desc";
  status?: string;
  customer_id?: string;
  date_from?: string;
  date_to?: string;
}
