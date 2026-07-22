export interface Invoice {
  id: number;
  serial: string;
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
  invoice_date: string;
  due_date: string;
  billing_address: string;
  subtotal: string;
  discount_amount: string;
  tax_amount: string;
  round_off: string;
  grand_total: string;
  status: string;
  remarks: string | null;
  created_by: { id: number; first_name: string; last_name: string; email: string } | null;
  updated_by: { id: number; first_name: string; last_name: string; email: string } | null;
  items: InvoiceItem[];
  created_at: string;
  updated_at: string;
}

export interface InvoiceItem {
  id: number;
  invoice_id: number;
  delivery_challan_id: number;
  delivery_challan_item_id: number;
  sales_order_id: number;
  sales_order_item_id: number;
  order_booking_id: number;
  order_booking_item_id: number;
  quotation_id: number | null;
  quotation_item_id: number | null;
  item_description: string;
  unit: string;
  delivered_quantity: string;
  invoiced_quantity: string;
  remaining_invoice_quantity: string;
  unit_price: string;
  line_total: string;
  remarks: string | null;
  delivery_challan_serial?: string;
  sales_order_serial?: string;
  order_serial?: string;
  quotation_serial?: string | null;
}

export interface InvoiceFormData {
  customer_id: string;
  invoice_date: string;
  due_date: string;
  billing_address: string;
  remarks: string;
  items: InvoiceItemFormData[];
}

export interface InvoiceItemFormData {
  delivery_challan_item_id: string;
  invoiced_quantity: string;
  unit_price: string;
  item_description: string;
  unit: string;
  remarks: string;
}

export interface InvoiceableItem {
  delivery_challan_item_id: number;
  delivery_challan_id: number;
  delivery_challan_serial: string;
  sales_order_id: number;
  sales_order_item_id: number;
  order_booking_id: number;
  order_booking_item_id: number;
  quotation_id: number | null;
  quotation_item_id: number | null;
  item_description: string;
  unit: string;
  delivered_quantity: number;
  already_invoiced: number;
  available_for_invoicing: number;
  unit_price: number;
}

export interface InvoiceListParams {
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
